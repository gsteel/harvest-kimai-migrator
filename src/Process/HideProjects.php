<?php

declare(strict_types=1);

namespace HM\Process;

use HM\Kimai\KimaiClient;
use HM\State\Projects;
use Override;

final readonly class HideProjects implements Process
{
    public function __construct(
        private Projects $state,
        private KimaiClient $kimaiClient,
    ) {
    }

    /** @inheritDoc */
    #[Override]
    public function run(): void
    {
        foreach ($this->state->archivedProjects() as $id) {
            $this->kimaiClient->hideProject($id);
        }
    }
}
