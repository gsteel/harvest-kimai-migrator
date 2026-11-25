<?php

declare(strict_types=1);

namespace HM\Process;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use HM\Kimai\DateTimeFormat;
use HM\Kimai\KimaiClient;
use HM\Kimai\Model\CreateTimesheet;
use HM\State\Activities;
use HM\State\Projects;
use HM\State\Times;
use HM\State\Users;
use JoliCode\Harvest\Api\Client as HarvestClient;
use JoliCode\Harvest\Api\Model\TimeEntries;
use JoliCode\Harvest\Api\Model\TimeEntry;
use Override;
use Psl;
use Psl\Iter;
use Psl\Math;
use Psl\Regex;
use Psl\Str;
use Psl\Type;
use RuntimeException;
use Throwable;

/** @mago-expect lint:cyclomatic-complexity */
final readonly class CopyTimesheets implements Process
{
    public function __construct(
        private Times $state,
        private Activities $activities,
        private Projects $projects,
        private Users $users,
        private HarvestClient $harvestClient,
        private KimaiClient $kimaiClient,
    ) {
    }

    /** @inheritDoc */
    #[Override]
    public function run(): void
    {
        foreach ($this->yieldTimes() as $entry) {
            $this->processEntry($entry);
        }
    }

    /** @return Generator<int, TimeEntry> */
    private function yieldTimes(): Generator
    {
        $page = 1;
        while (true) {
            $response = Type\instance_of(TimeEntries::class)
                ->assert(
                    $this->harvestClient->listTimeEntries(['page' => $page]),
                );

            $nextPage = $response->getNextPage();
            Psl\invariant(
                $nextPage === ($page + 1) || $nextPage === null,
                'The "Next Page" variable returned by the Harvest API is wonky',
            );
            $page = $nextPage;

            $entries = $response->getTimeEntries();

            yield from $entries;

            if ($page === null || Iter\count($entries) === 0) {
                break;
            }
        }
    }

    /** @throws Throwable */
    private function processEntry(TimeEntry $entry): void
    {
        // Skip any running timers
        if ($entry->getIsRunning() === true) {
            return;
        }

        // Skip already mapped timers
        $id = (int) Type\numeric()->assert($entry->getId());
        if ($this->state->getKimaiId($id) !== null) {
            return;
        }

        $activityId = Type\uint()->assert($this->activities->getKimaiId(
            Type\uint()->assert($entry->getTask()?->getId()),
        ));
        $projectId = Type\uint()->assert($this->projects->getKimaiId(
            Type\uint()->assert($entry->getProject()?->getId()),
        ));

        $times = $this->determineStartAndEndTimes($entry);

        $billed = $entry->getIsBilled() ?? false;
        $harvestUser = Type\uint()->assert($entry->getUser()?->getId());
        $userMap = $this->users->getMap($harvestUser);
        if ($userMap === null || $userMap->kimai === null) {
            throw new RuntimeException(Str\format(
                'The Harvest user "%d" (Name "%s") is not mapped to a Kimai user. You need to do this manually',
                $harvestUser,
                $userMap->name ?? 'Unknown',
            ));
        }

        $sheet = new CreateTimesheet(
            $activityId,
            $projectId,
            $this->users->getKimaiId($harvestUser),
            $entry->getNotes(),
            $times['begin'],
            $times['end'],
            $billed,
            $entry->getBillable() ?? $entry->getBillableRate() !== null,
            null,
            $entry->getBillableRate(),
        );

        $kimaiEntryId = $this->kimaiClient->createTimesheet($sheet);
        $this->state->map($kimaiEntryId, $id);
    }

    /**
     * @return array{
     *     begin: string,
     *     end: string,
     * }
     *
     * @throws Throwable
     */
    private function determineStartAndEndTimes(TimeEntry $entry): array
    {
        // Ensure we have some kind of date for this entry
        $date = Type\instance_of(DateTimeInterface::class)->assert($entry->getSpentDate() ?? $entry->getCreatedAt());
        // The total duration should always be non-null
        $durationInMinutes = (int) Math\ceil((float) Type\numeric()->assert($entry->getHours()) * 60);

        // Times are one of: "14:59" || "2:59pm" || null
        $start = self::parseTime($entry->getStartedTime()) ?? ['hour' => 12, 'minute' => 0];

        $utc = new DateTimeZone('UTC');
        $startTime = DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i:s',
            Str\format('%sT%02d:%02d:00', $date->format('Y-m-d'), $start['hour'], $start['minute']),
            $utc,
        );
        Psl\invariant($startTime !== false, 'Date parsing went tits up');

        $endTime = $startTime->add(new DateInterval(Str\format('PT%dM', $durationInMinutes)));

        return [
            'begin' => DateTimeFormat::dateTime($startTime),
            'end' => DateTimeFormat::dateTime($endTime),
        ];
    }

    /** @return array{hour: int, minute: int}|null */
    private static function parseTime(string|null $time): array|null
    {
        if ($time === null) {
            return null;
        }

        if (Str\ends_with_ci($time, 'm')) {
            $parts = Type\shape([
                0 => Type\string(),
                1 => Type\string(),
                2 => Type\string(),
                3 => Type\string(),
            ])->assert(Regex\first_match($time, '/^([0-9]+):([0-9]+)(am|pm)$/i'));
            $hour = (int) $parts[1];
            $minute = (int) $parts[2];
            $meridiem = Str\lowercase($parts[3]);
            $hour += $meridiem === 'pm' && $hour < 12 ? 12 : 0;

            return ['hour' => $hour, 'minute' => $minute];
        }

        $parts = Type\shape([
            0 => Type\string(),
            1 => Type\string(),
            2 => Type\string(),
        ])->assert(Regex\first_match($time, '/^([0-9]+):([0-9]+)$/'));

        return [
            'hour' => (int) $parts[1],
            'minute' => (int) $parts[2],
        ];
    }
}
