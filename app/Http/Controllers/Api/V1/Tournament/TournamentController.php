<?php

namespace App\Http\Controllers\Api\V1\Tournament;

use App\Http\Controllers\Controller;
use App\Application\Tournament\UseCases\CreateTournamentUseCase;
use App\Application\Tournament\UseCases\GetTournamentListUseCase;
use App\Application\Tournament\UseCases\RegisterPlayerUseCase;
use App\Application\Tournament\DTOs\CreateTournamentDTO;
use App\Application\Tournament\DTOs\TournamentFilterDTO;
use App\Application\Tournament\DTOs\RegisterPlayerDTO;
use App\Http\Requests\Tournament\CreateTournamentRequest;
use App\Http\Requests\Tournament\TournamentFilterRequest;
use App\Http\Requests\Tournament\RegisterPlayerRequest;
use App\Http\Resources\Tournament\TournamentResource;
use App\Http\Resources\Tournament\TournamentCollection;
use App\Http\Resources\Tournament\TournamentParticipantResource;
use App\Domain\Tournament\Repositories\TournamentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function __construct(
        private CreateTournamentUseCase $createTournamentUseCase,
        private GetTournamentListUseCase $getTournamentListUseCase,
        private RegisterPlayerUseCase $registerPlayerUseCase,
        private TournamentRepositoryInterface $tournamentRepository
    ) {}

    /**
     * Display a listing of tournaments
     */
    public function index(TournamentFilterRequest $request): JsonResponse
    {
        $filterDTO = TournamentFilterDTO::fromArray($request->validated());
        $tournaments = $this->getTournamentListUseCase->execute($filterDTO);

        return response()->json(new TournamentCollection($tournaments));
    }

    /**
     * Store a newly created tournament
     */
    public function store(CreateTournamentRequest $request): JsonResponse
    {
        $dto = CreateTournamentDTO::fromArray($request->validated());
        $tournament = $this->createTournamentUseCase->execute($dto);

        return response()->json([
            'message' => 'Tournament created successfully',
            'data' => new TournamentResource($tournament)
        ], 201);
    }

    /**
     * Display the specified tournament
     */
    public function show(string $slug): JsonResponse
    {
        $tournament = $this->tournamentRepository->findBySlug($slug, [
            'organizer',
            'participants.player',
            'participants.team',
            'matches'
        ]);

        if (!$tournament) {
            return response()->json([
                'message' => 'Tournament not found'
            ], 404);
        }

        return response()->json([
            'data' => new TournamentResource($tournament)
        ]);
    }

    /**
     * Update the specified tournament
     */
    public function update(CreateTournamentRequest $request, string $slug): JsonResponse
    {
        $tournament = $this->tournamentRepository->findBySlug($slug);
        
        if (!$tournament) {
            return response()->json([
                'message' => 'Tournament not found'
            ], 404);
        }

        // Check if user can update this tournament (organizer only)
        if ($tournament->organizer_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized to update this tournament'
            ], 403);
        }

        $updatedTournament = $this->tournamentRepository->update(
            $tournament, 
            $request->validated()
        );

        return response()->json([
            'message' => 'Tournament updated successfully',
            'data' => new TournamentResource($updatedTournament)
        ]);
    }

    /**
     * Remove the specified tournament
     */
    public function destroy(string $slug): JsonResponse
    {
        $tournament = $this->tournamentRepository->findBySlug($slug);
        
        if (!$tournament) {
            return response()->json([
                'message' => 'Tournament not found'
            ], 404);
        }

        // Check if user can delete this tournament (organizer only)
        if ($tournament->organizer_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized to delete this tournament'
            ], 403);
        }

        // Check if tournament can be deleted (no participants or matches)
        if ($tournament->participants()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete tournament with participants'
            ], 422);
        }

        $this->tournamentRepository->delete($tournament);

        return response()->json([
            'message' => 'Tournament deleted successfully'
        ]);
    }

    /**
     * Register a player for the tournament
     */
    public function registerPlayer(RegisterPlayerRequest $request, string $slug): JsonResponse
    {
        $tournament = $this->tournamentRepository->findBySlug($slug);
        
        if (!$tournament) {
            return response()->json([
                'message' => 'Tournament not found'
            ], 404);
        }

        $data = $request->validated();
        $data['tournament_id'] = $tournament->id;

        $dto = RegisterPlayerDTO::fromArray($data);
        $participant = $this->registerPlayerUseCase->execute($dto);

        return response()->json([
            'message' => 'Player registered successfully',
            'data' => new TournamentParticipantResource($participant)
        ], 201);
    }

    /**
     * Get tournament statistics
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->tournamentRepository->getStatistics();

        return response()->json([
            'data' => $statistics
        ]);
    }

    /**
     * Get featured tournaments
     */
    public function featured(): JsonResponse
    {
        $tournaments = $this->tournamentRepository->getFeatured();

        return response()->json([
            'data' => TournamentResource::collection($tournaments)
        ]);
    }

    /**
     * Get upcoming tournaments
     */
    public function upcoming(): JsonResponse
    {
        $tournaments = $this->tournamentRepository->getUpcoming(['organizer']);

        return response()->json([
            'data' => TournamentResource::collection($tournaments)
        ]);
    }
} 