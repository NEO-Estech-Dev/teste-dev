<?php

namespace App\Console\Commands;

use App\Jobs\SynchronizePokemonJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pokemon:sincronizar')]
#[Description('Sincroniza os pokémons a partir da PokeAPI')]
class SynchronizePokemonCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        SynchronizePokemonJob::dispatch();
        $this->info('Job de sincronização de pokémons despachada!');
    }
}
