<?php

declare(strict_types=1);

namespace HM\Process;

use HM\Kimai\KimaiClient;
use HM\State\Customers;
use Override;

final readonly class HideCustomers implements Process
{
    public function __construct(
        private Customers $state,
        private KimaiClient $kimaiClient,
    ) {
    }

    /** @inheritDoc */
    #[Override]
    public function run(): void
    {
        foreach ($this->state->archivedCustomers() as $id) {
            $this->kimaiClient->hideCustomer($id);
        }
    }
}
