<?php

declare(strict_types=1);

namespace HM\State;

final readonly class ActivityMap
{
    public function __construct(
        public string $name,
        public int $kimai,
        public int $harvest,
    ) {
    }
}
