<?php

namespace App\Providers;

use App\Domain\Tournament\Repositories\TournamentRepositoryInterface;
use App\Infrastructure\Repositories\EloquentTournamentRepository;
use Illuminate\Support\ServiceProvider;

class TournamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Repository Interface to Implementation
        $this->app->bind(
            TournamentRepositoryInterface::class,
            EloquentTournamentRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
