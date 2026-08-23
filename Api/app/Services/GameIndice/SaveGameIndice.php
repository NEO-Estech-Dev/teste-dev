<?php

namespace App\Services\GameIndice;

use App\Repositories\GameIndice\GameIndiceRepository;

class SaveGameIndice {

    private GameIndiceRepository $gameIndiceRepository;

    public function __construct(GameIndiceRepository $gameIndiceRepository)
    {
        $this->gameIndiceRepository = $gameIndiceRepository;
    }
    
    public function execute(array $data): void
    {

        $game_indices = collect($data)
            ->flatMap(fn ($item)  => collect($item['game_indices'])
            ->map(fn ($gameIndex) => [
                    'version_name' => $gameIndex['version']['name'],
                    'game_index' => $gameIndex['game_index'],
                    'pokemon_id' => $item['pokemon_id'],
                ])
            )->all();

        $this->gameIndiceRepository->newQuery()->upsert(
            $game_indices,
            ['pokemon_id','version_name'],
            ['game_index']
        );
    }
}