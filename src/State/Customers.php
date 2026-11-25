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

final class Customers extends StateFile
{
    /** @var list<CustomerMap> */
    private array $map = [];
    /** @var list<int> */
    private array $archived = [];

    /** @throws RuntimeException */
    public function map(string $name, int $kimai, int $harvest): void
    {
        $mappedHarvest = $this->getHarvestId($kimai);
        $mappedKimai = $this->getKimaiId($harvest);
        $sum = ($mappedKimai ?? 0) + ($mappedHarvest ?? 0);
        if ($sum > 0 && $sum !== ($kimai + $harvest)) {
            throw new RuntimeException(Str\format(
                'Customer "%s" has already been mapped but there is an identifier mismatch',
                $name,
            ));
        }

        if ($sum > 0) {
            return;
        }

        $this->map[] = new CustomerMap($name, $kimai, $harvest);
    }

    public function archive(int $kimaiId): void
    {
        if (Iter\contains($this->archived, $kimaiId)) {
            return;
        }

        $this->archived[] = $kimaiId;
    }

    /** @return list<int> */
    public function archivedCustomers(): array
    {
        return $this->archived;
    }

    public function getKimaiId(int $harvestId): int|null
    {
        $item = Iter\search($this->map, static fn (CustomerMap $map): bool => $map->harvest === $harvestId);

        return $item?->kimai;
    }

    public function getHarvestId(int $kimai): int|null
    {
        $item = Iter\search($this->map, static fn (CustomerMap $map): bool => $map->kimai === $kimai);

        return $item?->harvest;
    }

    /** @return array<string, mixed> */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'map' => $this->map,
            'archived' => $this->archived,
        ];
    }

    #[Override]
    protected function hydrate(string $content): void
    {
        $shape = Type\shape([
            'map' => Type\vec(Type\shape([
                'name' => Type\string(),
                'kimai' => Type\uint(),
                'harvest' => Type\uint(),
            ])),
            'archived' => Type\vec(Type\int()),
        ]);

        $data = Json\typed($content, $shape);

        $this->map = Vec\map($data['map'], static fn (array $row): CustomerMap => new CustomerMap(
            $row['name'],
            $row['kimai'],
            $row['harvest'],
        ));

        $this->archived = $data['archived'];
    }
}
