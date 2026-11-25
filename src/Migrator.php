<?php

declare(strict_types=1);

namespace HM;

use HM\Process\CopyActivities;
use HM\Process\CopyCustomers;
use HM\Process\CopyProjects;
use HM\Process\CopyTimesheets;
use HM\Process\HideCustomers;
use HM\Process\HideProjects;
use HM\Process\Process;
use HM\Process\ProcessManager;
use HM\Process\SeedUserMap;
use Throwable;

final readonly class Migrator
{
    /** @var list<class-string<Process>> */
    private array $processes;

    public function __construct(
        private StateManager $state,
        private ProcessManager $processManager,
    ) {
        $this->processes = [
            SeedUserMap::class,
            CopyActivities::class,
            CopyCustomers::class,
            CopyProjects::class,
            CopyTimesheets::class,
            HideProjects::class,
            HideCustomers::class,
        ];
    }

    /** @throws Throwable */
    public function migrate(): void
    {
        foreach ($this->processes as $processType) {
            $process = $this->processManager->get($processType);
            try {
                $process->run();
            } finally {
                $this->state->persist();
            }
        }

        $this->state->persist();
    }
}
