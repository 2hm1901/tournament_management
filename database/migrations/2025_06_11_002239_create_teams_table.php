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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            
            // Team Basic Information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Team Type
            $table->enum('type', [
                'men_doubles',
                'women_doubles', 
                'mixed_doubles'
            ]);
            
            // Team Members
            $table->foreignId('player1_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('player2_id')->constrained('players')->onDelete('cascade');
            
            // Team Captain (one of the players)
            $table->foreignId('captain_id')->constrained('players')->onDelete('cascade');
            
            // Team Statistics
            $table->integer('total_matches')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('draws')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0.00);
            
            // Tournament History
            $table->integer('tournaments_played')->default(0);
            $table->integer('tournaments_won')->default(0);
            $table->integer('best_finish')->nullable(); // Best tournament position
            
            // Team Rating (combined/average)
            $table->integer('team_rating')->default(1000);
            $table->integer('average_player_rating')->default(1000);
            
            // Team Status
            $table->boolean('is_active')->default(true);
            $table->enum('status', [
                'active',
                'inactive', 
                'disbanded',
                'suspended'
            ])->default('active');
            
            // Partnership Information
            $table->date('partnership_start_date');
            $table->date('partnership_end_date')->nullable();
            $table->text('partnership_notes')->nullable();
            
            // Contact & Communication
            $table->string('preferred_communication')->nullable(); // email, phone, etc.
            $table->json('communication_preferences')->nullable();
            
            // Team Settings
            $table->boolean('accepting_tournaments')->default(true);
            $table->json('preferred_tournament_types')->nullable();
            $table->text('special_requirements')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['type', 'is_active']);
            $table->index(['team_rating', 'status']);
            $table->index(['player1_id', 'player2_id']);
            $table->index('captain_id');
            
            // Unique constraint to prevent duplicate partnerships
            $table->unique(['player1_id', 'player2_id'], 'unique_partnership');
            
            // Note: Business logic constraints (player uniqueness, captain validation)
            // will be handled at application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
