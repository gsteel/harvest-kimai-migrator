<?php

declare(strict_types=1);

namespace HM;

use Closure;
use HM\Kimai\KimaiClient;
use Http\Client\Curl\Client;
use JoliCode\Harvest\Api\Client as HarvestApiClient;
use JoliCode\Harvest\ClientFactory;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use Override;
use Psl\Iter;
use Psl\Str;
use Psl\Type;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function assert;
use function getenv;

final class Container implements ContainerInterface
{
    /** @var array<string, (Closure(ContainerInterface=): mixed)> */
    private array $factories;
    /** @var array<string, mixed> */
    private array $instances;

    public function __construct()
    {
        /** @mago-expect analysis:unhandled-thrown-type */
        $this->factories = [
            Options::class => static fn (): Options => new Options(
                Type\non_empty_string()->assert(getenv('HARVEST_ACCOUNT_ID')),
                Type\non_empty_string()->assert(getenv('HARVEST_PAT')),
                Type\non_empty_string()->assert(getenv('KIMAI_HOST')),
                Type\non_empty_string()->assert(getenv('KIMAI_TOKEN')),
                Type\non_empty_string()->assert(getenv('TZ')),
                Type\non_empty_string()->assert(getenv('DEFAULT_LANG')),
                Type\non_empty_string()->assert(getenv('DEFAULT_CURRENCY')),
                Type\non_empty_string()->assert(getenv('DEFAULT_COUNTRY')),
            ),
            HarvestApiClient::class => function (): HarvestApiClient {
                $options = $this->get(Options::class);

                return ClientFactory::create(
                    $options->harvestPat,
                    $options->harvestAccountId,
                );
            },
            KimaiClient::class => function (): KimaiClient {
                $streamFactory = new StreamFactory();
                $options = $this->get(Options::class);

                return new KimaiClient(
                    new RequestFactory(),
                    $streamFactory,
                    new UriFactory(),
                    new Client(
                        new ResponseFactory(),
                        $streamFactory,
                    ),
                    $options->kimaiToken,
                    $options->kimaiHost,
                );
            },
            StateManager::class => static fn (): StateManager => new StateManager(__DIR__ . '/../state'),
            Migrator::class => fn (): Migrator => new Migrator(
                $this->get(StateManager::class),
                $this->get(Process\ProcessManager::class),
            ),
            Process\ProcessManager::class => fn (): Process\ProcessManager => new Process\ProcessManager($this),
            Process\SeedUserMap::class => fn (): Process\SeedUserMap => new Process\SeedUserMap(
                $this->get(StateManager::class)->get(State\Users::class),
                $this->get(HarvestApiClient::class),
            ),
            Process\CopyActivities::class => fn (): Process\CopyActivities => new Process\CopyActivities(
                $this->get(StateManager::class)->get(State\Activities::class),
                $this->get(HarvestApiClient::class),
                $this->get(KimaiClient::class),
            ),
            Process\CopyCustomers::class => fn (): Process\CopyCustomers => new Process\CopyCustomers(
                $this->get(StateManager::class)->get(State\Customers::class),
                $this->get(HarvestApiClient::class),
                $this->get(KimaiClient::class),
                $this->get(Options::class),
            ),
            Process\CopyProjects::class => fn (): Process\CopyProjects => new Process\CopyProjects(
                $this->get(StateManager::class)->get(State\Projects::class),
                $this->get(StateManager::class)->get(State\Customers::class),
                $this->get(HarvestApiClient::class),
                $this->get(KimaiClient::class),
            ),
            Process\CopyTimesheets::class => fn (): Process\CopyTimesheets => new Process\CopyTimesheets(
                $this->get(StateManager::class)->get(State\Times::class),
                $this->get(StateManager::class)->get(State\Activities::class),
                $this->get(StateManager::class)->get(State\Projects::class),
                $this->get(StateManager::class)->get(State\Users::class),
                $this->get(HarvestApiClient::class),
                $this->get(KimaiClient::class),
            ),
            Process\HideProjects::class => fn (): Process\HideProjects => new Process\HideProjects(
                $this->get(StateManager::class)->get(State\Projects::class),
                $this->get(KimaiClient::class),
            ),
            Process\HideCustomers::class => fn (): Process\HideCustomers => new Process\HideCustomers(
                $this->get(StateManager::class)->get(State\Customers::class),
                $this->get(KimaiClient::class),
            ),
        ];
    }

    /**
     * @param string|class-string $id
     * @return bool
     */
    #[Override]
    public function has(string $id): bool
    {
        return Iter\contains_key($this->factories, $id);
    }

    /**
     * @template T
     * @param string|class-string<T> $id
     * @return ($id is class-string ? T : mixed)
     * @throws NotFoundExceptionInterface
     */
    #[Override]
    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw new class(Str\format('%s not found', $id)) extends RuntimeException implements
                NotFoundExceptionInterface {
            };
        }

        assert(Iter\contains_key($this->factories, $id));

        /** @var mixed $instance */
        $instance = $this->instances[$id] ?? null;
        if ($instance !== null) {
            return $instance;
        }

        $factory = Type\instance_of(Closure::class)->assert($this->factories[$id] ?? null);

        /** @var mixed $instance */
        $instance = $factory($this);
        $this->instances[$id] = $instance;

        return $instance;
    }
}
