<?php

declare(strict_types=1);

namespace HM\Kimai;

use DateTimeInterface;

final readonly class DateTimeFormat
{
    public static function date(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public static function dateTime(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:s');
    }
}
