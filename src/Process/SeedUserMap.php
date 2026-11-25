<?php

declare(strict_types=1);

namespace HM\Process;

use HM\State\Users;
use JoliCode\Harvest\Api\Client as HarvestClient;
use JoliCode\Harvest\Api\Model\User;
use JoliCode\Harvest\Api\Model\Users as HarvestUsers;
use Override;
use Psl\Str;
use Psl\Type;

final readonly class SeedUserMap implements Process
{
    public function __construct(
        private Users $users,
        private HarvestClient $harvestClient,
    ) {
    }

    /** @inheritDoc */
    #[Override]
    public function run(): void
    {
        $response = Type\instance_of(HarvestUsers::class)->assert($this->harvestClient->listUsers());
        foreach ($response->getUsers() as $user) {
            $this->processUser($user);
        }
    }

    private function processUser(User $user): void
    {
        $this->users->seed(
            Str\trim(Str\format(
                '%s %s',
                (string) $user->getFirstName(),
                (string) $user->getLastName(),
            )),
            Type\uint()->assert($user->getId()),
        );
    }
}
