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

final class Times extends StateFile
{
    /** @var list<TimeMap> */
    private array $map = [];

    /** @throws RuntimeException */
    public function map(int $kimai, int $harvest): void
    {
        $mappedHarvest = $this->getHarvestId($kimai);
        $mappedKimai = $this->getKimaiId($harvest);
        $sum = ($mappedKimai ?? 0) + ($mappedHarvest ?? 0);
        if ($sum > 0 && $sum !== ($kimai + $harvest)) {
            throw new RuntimeException(Str\format(
                'Time Entry #%d has already been mapped but there is an identifier mismatch',
                $harvest,
            ));
        }

        if ($sum > 0) {
            return;
        }

        $this->map[] = new TimeMap($kimai, $harvest);
    }

    public function getKimaiId(int $harvestId): int|null
    {
        $item = Iter\search($this->map, static fn (TimeMap $map): bool => $map->harvest === $harvestId);

        return $item?->kimai;
    }

    public function getHarvestId(int $kimai): int|null
    {
        $item = Iter\search($this->map, static fn (TimeMap $map): bool => $map->kimai === $kimai);

        return $item?->harvest;
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
                'kimai' => Type\uint(),
                'harvest' => Type\uint(),
            ])),
        ]);

        $data = Json\typed($content, $shape);

        $this->map = Vec\map($data['map'], static fn (array $row): TimeMap => new TimeMap(
            $row['kimai'],
            $row['harvest'],
        ));
    }
}
