<?php

declare(strict_types=1);

namespace HM\Process;

use HM\Kimai\KimaiClient;
use HM\Kimai\Model\CreateActivity;
use HM\State\Activities;
use JoliCode\Harvest\Api\Client as HarvestClient;
use JoliCode\Harvest\Api\Model\Task;
use JoliCode\Harvest\Api\Model\Tasks;
use Override;
use Psl\Type;
use Throwable;

final readonly class CopyActivities implements Process
{
    public function __construct(
        private Activities $activities,
        private HarvestClient $harvestClient,
        private KimaiClient $kimaiClient,
    ) {
    }

    /** @inheritDoc */
    #[Override]
    public function run(): void
    {
        $response = Type\instance_of(Tasks::class)->assert($this->harvestClient->listTasks());
        foreach ($response->getTasks() as $task) {
            $this->processTask($task);
        }
    }

    /** @throws Throwable */
    private function processTask(Task $task): void
    {
        $id = (int) Type\numeric()->assert($task->getId());
        if ($this->activities->getKimaiId($id) !== null) {
            return;
        }

        $copy = new CreateActivity((string) $task->getName());
        $copy->visible = $task->getIsActive() ?? true;
        $copy->billable = $task->getBillableByDefault() ?? true;

        $kimai = $this->kimaiClient->createActivity($copy);

        $this->activities->map($copy->name, $kimai, $id);
    }
}
