<?php

declare(strict_types=1);

namespace HM\Process;

use Throwable;

interface Process
{
    /** @throws Throwable */
    public function run(): void;
}
