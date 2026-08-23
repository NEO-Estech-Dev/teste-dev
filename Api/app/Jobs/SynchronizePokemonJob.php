<?php

namespace App\Jobs;

use App\Services\SynchronizeService\SynchronizePokemonService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

#[Timeout(160)]
#[Tries(3)]
class SynchronizePokemonJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SynchronizePokemonService $service): void
    {
        $service->execute();
    }

    public function failed(Throwable $exception): void
    {
        logger()->error('Falha ao sincronizar pokémons', [
            'message' => $exception->getMessage(),
        ]);
    }
}
