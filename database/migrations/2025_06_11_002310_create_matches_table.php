<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            
            // Tournament Reference
            $table->foreignId('tournament_id')->constrained('tournaments')->onDelete('cascade');
            
            // Match Participants (Support both individual and team matches)
            $table->foreignId('participant1_id')->constrained('tournament_participants')->onDelete('cascade');
            $table->foreignId('participant2_id')->constrained('tournament_participants')->onDelete('cascade');
            
            // Match Information
            $table->string('match_number')->nullable(); // Match identifier (e.g., "QF1", "SF2", "F1")
            $table->integer('round_number'); // 1, 2, 3... (1 = first round, higher = later rounds)
            $table->string('round_name')->nullable(); // "First Round", "Quarterfinals", "Semifinals", "Final"
            
            // Match Scheduling
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->string('court_number')->nullable();
            $table->string('venue')->nullable();
            
            // Match Status
            $table->enum('status', [
                'scheduled',
                'ready_to_start',
                'in_progress',
                'completed',
                'cancelled',
                'postponed',
                'walkover',
                'no_show'
            ])->default('scheduled');
            
            // Match Results
            $table->foreignId('winner_id')->nullable()->constrained('tournament_participants')->onDelete('set null');
            $table->foreignId('loser_id')->nullable()->constrained('tournament_participants')->onDelete('set null');
            
            // Scoring System (flexible JSON structure)
            $table->json('score_data')->nullable(); // Stores sets, games, points
            $table->string('final_score')->nullable(); // Human readable score (e.g., "6-4, 6-2")
            $table->integer('sets_won_participant1')->default(0);
            $table->integer('sets_won_participant2')->default(0);
            $table->integer('games_won_participant1')->default(0);
            $table->integer('games_won_participant2')->default(0);
            
            // Match Format
            $table->enum('match_format', [
                'best_of_1',
                'best_of_3', 
                'best_of_5'
            ])->default('best_of_3');
            
            // Match Statistics
            $table->integer('duration_minutes')->nullable(); // Match duration
            $table->json('statistics')->nullable(); // Detailed match stats
            
            // Officials
            $table->string('referee_name')->nullable();
            $table->string('umpire_name')->nullable();
            $table->json('officials')->nullable(); // Additional officials data
            
            // Match Notes & Events
            $table->text('notes')->nullable();
            $table->json('match_events')->nullable(); // Timeline of events
            $table->json('incidents')->nullable(); // Penalties, warnings, etc.
            
            // Broadcast & Media
            $table->boolean('is_featured_match')->default(false);
            $table->string('stream_url')->nullable();
            $table->json('media_links')->nullable(); // Photos, videos, etc.
            
            // Next Match Information (for bracket progression)
            $table->foreignId('next_match_id')->nullable()->constrained('matches')->onDelete('set null');
            $table->enum('next_match_position', ['participant1', 'participant2'])->nullable();
            
            // Weather & Conditions (for outdoor matches)
            $table->json('weather_conditions')->nullable();
            $table->text('special_conditions')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['tournament_id', 'round_number']);
            $table->index(['tournament_id', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['participant1_id', 'participant2_id']);
            $table->index('winner_id');
            $table->index('court_number');
            
            // Note: Participant uniqueness will be handled at application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
