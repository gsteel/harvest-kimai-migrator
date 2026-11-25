<?php

declare(strict_types=1);

namespace HM\State;

final readonly class TimeMap
{
    public function __construct(
        public int $kimai,
        public int $harvest,
    ) {
    }
}
