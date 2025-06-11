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
        Schema::create('tournament_participants', function (Blueprint $table) {
            $table->id();
            
            // Tournament Reference
            $table->foreignId('tournament_id')->constrained('tournaments')->onDelete('cascade');
            
            // Participant Information  
            $table->foreignId('player_id')->nullable()->constrained('players')->onDelete('cascade');
            $table->unsignedBigInteger('team_id')->nullable(); // Will add foreign key after teams table is created
            
            // Registration Information
            $table->enum('registration_status', [
                'pending',
                'confirmed', 
                'waitlisted',
                'rejected',
                'withdrawn',
                'disqualified'
            ])->default('pending');
            
            $table->datetime('registered_at');
            $table->datetime('confirmed_at')->nullable();
            
            // Tournament Specific Data
            $table->integer('seed_number')->nullable(); // Tournament seeding
            $table->integer('current_round')->default(0);
            $table->enum('tournament_status', [
                'active',
                'eliminated', 
                'withdrawn',
                'bye',
                'champion',
                'finalist',
                'semifinalist'
            ])->default('active');
            
            // Performance Tracking
            $table->integer('matches_played')->default(0);
            $table->integer('matches_won')->default(0);
            $table->integer('matches_lost')->default(0);
            $table->integer('sets_won')->default(0);
            $table->integer('sets_lost')->default(0);
            $table->integer('games_won')->default(0);
            $table->integer('games_lost')->default(0);
            
            // Finals Position
            $table->integer('final_position')->nullable(); // 1st, 2nd, 3rd, etc.
            $table->decimal('prize_money', 8, 2)->default(0.00);
            
            // Payment Information
            $table->boolean('entry_fee_paid')->default(false);
            $table->datetime('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            
            // Additional Information
            $table->text('special_requirements')->nullable(); // Dietary, accessibility, etc.
            $table->json('emergency_contact')->nullable();
            $table->text('notes')->nullable(); // Admin notes
            
            $table->timestamps();
            
            // Constraints
            $table->unique(['tournament_id', 'player_id'], 'unique_player_tournament');
            $table->unique(['tournament_id', 'team_id'], 'unique_team_tournament');
            
            // Indexes
            $table->index(['tournament_id', 'registration_status']);
            $table->index(['tournament_id', 'tournament_status']);
            $table->index('seed_number');
            $table->index('final_position');
            
            // Note: Check constraint for player_id/team_id mutual exclusivity 
            // will be handled at application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_participants');
    }
};
