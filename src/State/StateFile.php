<?php

declare(strict_types=1);

namespace HM\State;

use JsonSerializable;
use Psl\File;
use Psl\File\WriteMode;
use Psl\Filesystem;
use Psl\Json;

abstract class StateFile implements JsonSerializable
{
    /** @param non-empty-string $path */
    final public function __construct(
        public readonly string $path,
    ) {
        $this->load();
    }

    final public function write(): void
    {
        File\write(
            $this->path,
            Json\encode($this, true),
            WriteMode::Truncate,
        );
    }

    private function load(): void
    {
        if (! Filesystem\exists($this->path)) {
            return;
        }

        $this->hydrate(File\read(
            $this->path,
        ));
    }

    abstract protected function hydrate(string $content): void;
}
