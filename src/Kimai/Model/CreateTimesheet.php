<?php

declare(strict_types=1);

namespace HM\Kimai\Model;

final class CreateTimesheet
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public int $activity,
        public int $project,
        public int $user,
        public string|null $description = null,
        public string|null $begin = null,
        public string|null $end = null,
        public bool $exported = false,
        public bool $billable = true,
        public int|float|null $fixedRate = null,
        public int|float|null $hourlyRate = null,
        public string|null $tags = null,
    ) {
    }
}
