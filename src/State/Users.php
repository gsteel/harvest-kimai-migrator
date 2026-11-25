<?php

declare(strict_types=1);

namespace HM\State;

use Override;
use Psl\Iter;
use Psl\Json;
use Psl\Type;
use Psl\Vec;
use RuntimeException;

final class Users extends StateFile
{
    /** @var list<UserMap> */
    private array $map = [];

    public function seed(string $name, int $harvest): void
    {
        $map = $this->getMap($harvest);
        if ($map) {
            return;
        }

        $this->map[] = new UserMap($name, $harvest, null);
    }

    public function getMap(int $harvestId): UserMap|null
    {
        return Iter\search($this->map, static fn (UserMap $map): bool => $map->harvest === $harvestId);
    }

    /** @throws RuntimeException */
    public function getKimaiId(int $harvestId): int
    {
        $map = Type\instance_of(UserMap::class)->assert($this->getMap($harvestId));

        return $map->requireKimai();
    }

    /** @return array<string, mixed> */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'map' => $this->map,
        ];
    }

    #[Override]
    protected function hydrate(string $content): void
    {
        $shape = Type\shape([
            'map' => Type\vec(Type\shape([
                'name' => Type\string(),
                'harvest' => Type\uint(),
                'kimai' => Type\union(Type\uint(), Type\null()),
            ])),
        ]);

        $data = Json\typed($content, $shape);

        $this->map = Vec\map($data['map'], static fn (array $row): UserMap => new UserMap(
            $row['name'],
            $row['harvest'],
            $row['kimai'],
        ));
    }
}
