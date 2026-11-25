<?php

declare(strict_types=1);

namespace HM\Kimai;

use Fig\Http\Message\RequestMethodInterface;
use HM\Kimai\Model\CreateActivity;
use HM\Kimai\Model\CreateCustomer;
use HM\Kimai\Model\CreateProject;
use HM\Kimai\Model\CreateTimesheet;
use Psl\Json;
use Psl\Str;
use Psl\Type;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use SensitiveParameter;
use Throwable;

use function http_build_query;
use function json_validate;

final readonly class KimaiClient
{
    private UriInterface $baseUri;

    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private UriFactoryInterface $uriFactory,
        private ClientInterface $client,
        #[SensitiveParameter]
        private string $token,
        private string $host,
    ) {
        $this->baseUri = $this->uriFactory
            ->createUri('/')
            ->withScheme('https')
            ->withHost($this->host);
    }

    /**
     * Create an 'Activity'
     *
     * @return int The identifier of the activity
     * @throws Throwable
     */
    public function createActivity(CreateActivity $payload): int
    {
        return $this->postJsonPayloadAndRetrieveNumericId('/api/activities', $payload, 'id');
    }

    /**
     * Create a Customer
     *
     * @return int The identifier of the customer
     * @throws Throwable
     */
    public function createCustomer(CreateCustomer $payload): int
    {
        return $this->postJsonPayloadAndRetrieveNumericId('/api/customers', $payload, 'id');
    }

    /** @throws Throwable */
    public function hideCustomer(int $id): void
    {
        $request = $this->createRequest(
            RequestMethodInterface::METHOD_PATCH,
            Str\format('/api/customers/%d', $id),
            Json\encode(['visible' => false]),
        );

        $this->send($request);
    }

    /**
     * Create a Project
     *
     * @return int The identifier of the project
     * @throws Throwable
     */
    public function createProject(CreateProject $payload): int
    {
        return $this->postJsonPayloadAndRetrieveNumericId('/api/projects', $payload, 'id');
    }

    /** @throws Throwable */
    public function hideProject(int $id): void
    {
        $request = $this->createRequest(
            RequestMethodInterface::METHOD_PATCH,
            Str\format('/api/projects/%d', $id),
            Json\encode(['visible' => false]),
        );

        $this->send($request);
    }

    /**
     * Create a Timesheet entry
     *
     * @return int The identifier of the timesheet
     * @throws Throwable
     */
    public function createTimesheet(CreateTimesheet $payload): int
    {
        return $this->postJsonPayloadAndRetrieveNumericId('/api/timesheets', $payload, 'id');
    }

    /** @throws Throwable */
    private function postJsonPayloadAndRetrieveNumericId(string $path, mixed $payload, string $identifierKey): int
    {
        $request = $this->createRequest(
            RequestMethodInterface::METHOD_POST,
            $path,
            Json\encode($payload),
        );

        $response = $this->send($request);
        $data = Json\typed(
            Type\non_empty_string()->assert((string) $response->getBody()),
            Type\shape([
                $identifierKey => Type\uint(),
            ], true),
        );

        /** @mago-expect analysis:invalid-return-statement,nullable-return-statement,possibly-undefined-string-array-index Cannot infer parameterised key via shape() */
        return $data[$identifierKey];
    }

    /** @throws Throwable */
    private function send(RequestInterface $request): ResponseInterface
    {
        $response = $this->client->sendRequest($request);
        if ($response->getStatusCode() > 299) {
            $responseBody = (string) $response->getBody();
            if (json_validate($responseBody)) {
                $responseBody = Json\encode(Json\decode($responseBody, true), true);
            }

            $requestBody = (string) $request->getBody();
            if (json_validate($responseBody)) {
                $requestBody = Json\encode(Json\decode($requestBody, true), true);
            }

            throw new RuntimeException(Str\format(
                <<<'TXT'
                    %s to %s Failed:

                    Expected a status code in 2xx region but got %d.

                    Response body, if any, was:
                    %s
                     
                    Request body, if any, was:
                    %s
                    TXT,
                $request->getMethod(),
                (string) $request->getUri(),
                $response->getStatusCode(),
                $responseBody,
                $requestBody,
            ));
        }

        return $response;
    }

    /** @param array<string, int|float|string> $query */
    private function createRequest(
        string $method,
        string $path,
        string|null $body = null,
        array $query = [],
    ): RequestInterface {
        $uri = $this->baseUri->withPath($path);
        if ($query !== []) {
            $uri = $uri->withQuery(http_build_query($query));
        }

        $request = $this->requestFactory->createRequest($method, $uri);
        $request = $request
            ->withAddedHeader('Authorization', Str\format('Bearer %s', $this->token));
        if (Type\non_empty_string()->matches($body)) {
            $request = $request->withBody(
                $this->streamFactory->createStream($body),
            )->withAddedHeader('Content-Type', 'application/json');
        }

        return $request;
    }
}
