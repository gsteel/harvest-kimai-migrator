<?php

declare(strict_types=1);

namespace HM;

use HM\State\Activities;
use HM\State\Customers;
use HM\State\Projects;
use HM\State\StateFile;
use HM\State\Times;
use HM\State\Users;
use Psl\Filesystem;
use Psl\Str;
use Psl\Type;

final class StateManager
{
    /** @var array<class-string<StateFile>, non-empty-string> */
    private array $stateFiles = [
        Activities::class => 'activities.json',
        Customers::class => 'customers.json',
        Projects::class => 'projects.json',
        Times::class => 'time-entries.json',
        Users::class => 'users.json',
    ];

    /** @var array<class-string<StateFile>, StateFile> */
    private array $instances = [];

    /** @param non-empty-string $stateDir */
    public function __construct(
        private readonly string $stateDir,
    ) {
        if (Filesystem\exists($this->stateDir)) {
            return;
        }

        Filesystem\create_directory($this->stateDir);
    }

    /**
     * @param non-empty-string $file
     * @return non-empty-string
     */
    private function mkPath(string $file): string
    {
        return Str\format(
            '%s/%s',
            Str\trim_right($this->stateDir, '/'),
            Str\trim_left($file, '/'),
        );
    }

    public function persist(): void
    {
        foreach ($this->instances as $instance) {
            $instance->write();
        }
    }

    /**
     * @template T of StateFile
     * @param class-string<T> $type
     * @return T
     */
    public function get(string $type): StateFile
    {
        $instance = $this->instances[$type] ?? null;
        if ($instance instanceof $type) {
            return $instance;
        }

        $fileName = Type\non_empty_string()->assert($this->stateFiles[$type] ?? null);
        $instance = new $type($this->mkPath($fileName));
        $this->instances[$type] = $instance;

        return $instance;
    }
}
