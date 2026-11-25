<?php

declare(strict_types=1);

namespace HM\Process;

use Psl\Type;
use Psr\Container\ContainerInterface;

final readonly class ProcessManager
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    /**
     * @template T of Process
     * @param class-string<T> $processType
     * @return T
     */
    public function get(string $processType): Process
    {
        return Type\instance_of($processType)->assert($this->container->get($processType));
    }
}
