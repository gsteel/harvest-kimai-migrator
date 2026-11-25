<?php

declare(strict_types=1);

namespace HM\Kimai\Model;

final class CreateProject
{
    public string|null $number;
    public string|null $start;
    public string|null $end;

    public function __construct(
        public string $name,
        public readonly int $customer,
        public bool $visible = true,
        public bool $billable = true,
    ) {
    }
}
