<?php

declare(strict_types=1);

namespace HM\State;

use Psl\Str;
use Psl\Type;
use RuntimeException;

final readonly class UserMap
{
    public function __construct(
        public string $name,
        public int $harvest,
        public int|null $kimai,
    ) {
    }

    /** @throws RuntimeException */
    public function requireKimai(): int
    {
        if ($this->kimai === null) {
            throw new RuntimeException(Str\format(
                'Harvest user "%s" has not yet been mapped to a Kimai user',
                $this->name,
            ));
        }

        return Type\uint()->assert($this->kimai);
    }
}
