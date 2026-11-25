<?php

declare(strict_types=1);

namespace HM\Process;

use HM\Kimai\KimaiClient;
use HM\Kimai\Model\CreateCustomer;
use HM\Options;
use HM\State\Customers;
use JoliCode\Harvest\Api\Client as HarvestClient;
use JoliCode\Harvest\Api\Model\Client;
use JoliCode\Harvest\Api\Model\Clients;
use Override;
use Psl\Type;
use Throwable;

final readonly class CopyCustomers implements Process
{
    public function __construct(
        private Customers $state,
        private HarvestClient $harvestClient,
        private KimaiClient $kimaiClient,
        private Options $options,
    ) {
    }

    /** @inheritDoc */
    #[Override]
    public function run(): void
    {
        $response = Type\instance_of(Clients::class)->assert($this->harvestClient->listClients());
        foreach ($response->getClients() as $client) {
            $this->processClient($client);
        }
    }

    /** @throws Throwable */
    private function processClient(Client $client): void
    {
        $id = (int) Type\numeric()->assert($client->getId());
        if ($this->state->getKimaiId($id) !== null) {
            return;
        }

        $copy = new CreateCustomer(
            (string) $client->getName(),
            $this->options->defaultCountryCode,
            $client->getCurrency() ?? $this->options->defaultCurrencyCode,
            $this->options->defaultTimezone,
            $this->options->defaultLanguageCode,
        );

        $kimai = $this->kimaiClient->createCustomer($copy);
        $this->state->map($copy->name, $kimai, $id);

        $active = $client->getIsActive() ?? true;
        if (! $active) {
            $this->state->archive($kimai);
        }
    }
}
