<?php

declare(strict_types=1);

namespace HM\State;

use Override;
use Psl\Iter;
use Psl\Json;
use Psl\Str;
use Psl\Type;
use Psl\Vec;
use RuntimeException;

final class Activities extends StateFile
{
    /** @var list<ActivityMap> */
    private array $map = [];

    /** @throws RuntimeException */
    public function map(string $name, int $kimai, int $harvest): void
    {
        $mappedHarvest = $this->getHarvestId($kimai);
        $mappedKimai = $this->getKimaiId($harvest);
        $sum = ($mappedKimai ?? 0) + ($mappedHarvest ?? 0);
        if ($sum > 0 && $sum !== ($kimai + $harvest)) {
            throw new RuntimeException(Str\format(
                'Activity/Task "%s" has already been mapped but there is an identifier mismatch',
                $name,
            ));
        }

        if ($sum > 0) {
            return;
        }

        $this->map[] = new ActivityMap($name, $kimai, $harvest);
    }

    public function getKimaiId(int $harvestId): int|null
    {
        $item = Iter\search($this->map, static fn (ActivityMap $map): bool => $map->harvest === $harvestId);

        return $item?->kimai;
    }

    public function getHarvestId(int $kimai): int|null
    {
        $item = Iter\search($this->map, static fn (ActivityMap $map): bool => $map->kimai === $kimai);

        return $item?->harvest;
    }

    /** @return list<ActivityMap> */
    #[Override]
    public function jsonSerialize(): array
    {
        return $this->map;
    }

    #[Override]
    protected function hydrate(string $content): void
    {
        $data = Json\typed(
            $content,
            Type\vec(Type\shape([
                'name' => Type\string(),
                'kimai' => Type\uint(),
                'harvest' => Type\uint(),
            ])),
        );

        $this->map = Vec\map($data, static fn (array $row): ActivityMap => new ActivityMap(
            $row['name'],
            $row['kimai'],
            $row['harvest'],
        ));
    }
}
