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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            
            // Link to User
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Player Profile
            $table->string('player_name')->nullable(); // Different from user name if needed
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            
            // Skill Rating System
            $table->integer('skill_rating')->default(1000); // ELO-like rating
            $table->enum('skill_level', [
                'beginner',
                'intermediate', 
                'advanced',
                'expert',
                'professional'
            ])->default('beginner');
            
            // Player Statistics
            $table->integer('total_matches')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('draws')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0.00); // Percentage
            
            // Tournament Statistics
            $table->integer('tournaments_played')->default(0);
            $table->integer('tournaments_won')->default(0);
            $table->integer('best_tournament_finish')->nullable(); // Position in best tournament
            
            // Player Preferences
            $table->json('preferred_tournament_types')->nullable(); // Array of preferred types
            $table->boolean('available_for_tournaments')->default(true);
            $table->text('notes')->nullable(); // Admin notes
            
            // Location Information
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('timezone')->nullable();
            
            // Verification & Status
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->datetime('last_active_at')->nullable();
            
            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique('user_id');
            $table->index(['skill_rating', 'skill_level']);
            $table->index(['is_active', 'available_for_tournaments']);
            $table->index('tournaments_played');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
