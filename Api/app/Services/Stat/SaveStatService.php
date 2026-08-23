<?php

namespace App\Services\Stat;

use App\Repositories\Stat\StatRepository;

class SaveStatService {

    private StatRepository $statRepository;

    public function __construct(StatRepository $statRepository)
    {
        $this->statRepository = $statRepository;
    }
    
    public function execute(array $data): void
    {
        $stats = collect($data)
            ->flatMap(fn ($item)  => collect($item['stats'])
            ->map(fn ($stats) => [
                    'base_stat' => $stats['base_stat'],
                    'effort' => $stats['effort'],
                    'stat_name' => $stats['stat']['name'],
                    'pokemon_id' => $item['pokemon_id'],
                ])
            )->all();

        $this->statRepository->newQuery()->upsert(
            $stats,
            ['pokemon_id','stat_name'],
            ['base_stat', 'effort']
        );
    }
}