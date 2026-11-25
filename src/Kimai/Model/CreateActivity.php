<?php

declare(strict_types=1);

namespace HM\Kimai\Model;

final class CreateActivity
{
    public string|null $number;
    public string|null $comment;
    public string|null $invoiceText;
    public int|null $project;
    /** @var list<int> */
    public array $teams = [];
    public string|null $color;
    public bool $visible = true;
    public bool $billable = true;

    public function __construct(
        public string $name,
    ) {
    }
}
