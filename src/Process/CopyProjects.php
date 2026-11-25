<?php

declare(strict_types=1);

namespace HM\Process;

use DateTimeInterface;
use HM\Kimai\DateTimeFormat;
use HM\Kimai\KimaiClient;
use HM\Kimai\Model\CreateProject;
use HM\State\Customers;
use HM\State\Projects;
use JoliCode\Harvest\Api\Client as HarvestClient;
use JoliCode\Harvest\Api\Model\Project;
use JoliCode\Harvest\Api\Model\Projects as HarvestProjects;
use Override;
use Psl\Type;
use Throwable;

final readonly class CopyProjects implements Process
{
    public function __construct(
        private Projects $state,
        private Customers $customerMap,
        private HarvestClient $harvestClient,
        private KimaiClient $kimaiClient,
    ) {
    }

    /** @inheritDoc */
    #[Override]
    public function run(): void
    {
        $response = Type\instance_of(HarvestProjects::class)->assert($this->harvestClient->listProjects());
        foreach ($response->getProjects() as $project) {
            $this->processProject($project);
        }
    }

    /** @throws Throwable */
    private function processProject(Project $project): void
    {
        $id = (int) Type\numeric()->assert($project->getId());
        if ($this->state->getKimaiId($id) !== null) {
            return;
        }

        $harvestCustomerId = (int) Type\numeric()->assert($project->getClient()?->getId());
        $kimaiCustomer = Type\positive_int()->assert(
            $this->customerMap->getKimaiId($harvestCustomerId),
        );

        $isActive = $project->getIsActive() ?? true;

        $copy = new CreateProject(
            (string) $project->getName(),
            $kimaiCustomer,
            true,
            $project->getIsBillable() ?? true,
        );

        $start = $project->getStartsOn();
        if ($start instanceof DateTimeInterface) {
            $copy->start = DateTimeFormat::date($start);
        }

        /**
         * Skip "Ending" projects.
         *
         * If the project has an end date, you cannot log times against it via the Kimai api
         */
        //$end = $project->getEndsOn();
        //if ($end instanceof DateTimeInterface) {
        //    $copy->end = DateTimeFormat::date($end);
        //}

        if ($project->getCode() !== null) {
            $copy->number = $project->getCode();
        }

        $kimai = $this->kimaiClient->createProject($copy);
        $this->state->map($copy->name, $kimai, $id);
        if (! $isActive) {
            $this->state->archive($kimai);
        }
    }
}
