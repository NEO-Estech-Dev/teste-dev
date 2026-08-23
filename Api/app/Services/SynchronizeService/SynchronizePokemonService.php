<?php

namespace App\Services\SynchronizeService;

use App\Exception\CustomException;
use App\Services\GameIndice\SaveGameIndice;
use App\Services\Pokemon\SavePokemonService;
use App\Services\Stat\SaveStatService;
use Illuminate\Support\Facades\DB;

class SynchronizePokemonService {

    private const URL_BASE = "https://pokeapi.co/api/v2/pokemon";
    private ConectPokemonApiService $conectPokemonApiService;
    private SavePokemonService $savePokemonService;
    private SaveStatService $saveStatService;
    private SaveGameIndice $saveGameIndice;

    public function __construct(
        ConectPokemonApiService $conectPokemonApiService,
        SavePokemonService $savePokemonService,
        SaveStatService $saveStatService,
        SaveGameIndice $saveGameIndice
    ){
        $this->conectPokemonApiService = $conectPokemonApiService;
        $this->savePokemonService = $savePokemonService;
        $this->saveStatService = $saveStatService;
        $this->saveGameIndice = $saveGameIndice;
    }

    public function execute()
    {
        try {
            DB::beginTransaction();    
            $limit = 100;
            $offset = 0;
            
            do {
                $url = self::URL_BASE . "?limit={$limit}&offset={$offset}";
                $data = $this->conectPokemonApiService->execute($url);
                $this->processarLote($data['results']);
                $offset += $limit;
            } while (!empty($data['next']));
            DB::commit();
        } catch (\Throwable $error) {
            DB::rollBack();
            dd($error);
            CustomException::exception($error);
        }
    }

    private function processarLote(?array $data) 
    {
        $urls = array_map(function($data){
            return $data['url'] ?? null;
        }, $data);

        $detalhes = $this->conectPokemonApiService->executePool($urls);
        $this->savePokemonService->execute($detalhes);
        $this->saveGameIndice->execute($detalhes);
        $this->saveStatService->execute($detalhes);
    }
}