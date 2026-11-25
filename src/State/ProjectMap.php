<?php

declare(strict_types=1);

namespace HM\State;

final readonly class ProjectMap
{
    public function __construct(
        public string $name,
        public int $kimai,
        public int $harvest,
    ) {
    }
}
