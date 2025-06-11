<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Tournament\Repositories\TournamentRepositoryInterface;
use App\Infrastructure\Repositories\EloquentTournamentRepository;
use App\Domain\Player\Repositories\PlayerRepositoryInterface;
use App\Infrastructure\Repositories\EloquentPlayerRepository;
use App\Domain\Team\Repositories\TeamRepositoryInterface;
use App\Infrastructure\Repositories\EloquentTeamRepository;
use App\Infrastructure\Repositories\TournamentParticipantRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Tournament Repository
        $this->app->bind(
            TournamentRepositoryInterface::class,
            EloquentTournamentRepository::class
        );

        // Player Repository
        $this->app->bind(
            PlayerRepositoryInterface::class,
            EloquentPlayerRepository::class
        );

        // Team Repository
        $this->app->bind(
            TeamRepositoryInterface::class,
            EloquentTeamRepository::class
        );

        // Tournament Participant Repository (concrete class, no interface)
        $this->app->singleton(
            TournamentParticipantRepository::class,
            TournamentParticipantRepository::class
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