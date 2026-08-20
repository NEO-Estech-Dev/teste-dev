<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupApplicationCommand extends Command
{
    protected $signature = 'app:setup {--ingest : Executa a ingestão padrão após o setup}';

    protected $description = 'Gera a key, roda migrations e cria o usuário de demonstração';

    public function handle(): int
    {
        if (blank(config('app.key'))) {
            $this->call('key:generate', ['--force' => true]);
        }

        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--force' => true]);

        if ($this->option('ingest')) {
            $this->call('pokemons:ingest');
        }

        $this->info('Ambiente pronto.');

        return self::SUCCESS;
    }
}
