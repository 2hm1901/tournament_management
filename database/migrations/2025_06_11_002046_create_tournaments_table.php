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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            
            // Tournament Type & Format
            $table->enum('type', [
                'men_singles', 
                'women_singles', 
                'men_doubles', 
                'women_doubles', 
                'mixed_doubles'
            ]);
            $table->enum('format', [
                'single_elimination', 
                'double_elimination', 
                'round_robin',
                'swiss'
            ])->default('single_elimination');
            
            // Tournament Status
            $table->enum('status', [
                'draft',
                'registration_open', 
                'registration_closed',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('draft');
            
            // Capacity & Limits
            $table->integer('max_participants')->default(32);
            $table->integer('current_participants')->default(0);
            $table->integer('min_participants')->default(4);
            
            // Tournament Dates
            $table->datetime('registration_start_date')->nullable();
            $table->datetime('registration_end_date')->nullable();
            $table->datetime('tournament_start_date')->nullable();
            $table->datetime('tournament_end_date')->nullable();
            
            // Tournament Settings
            $table->json('settings')->nullable(); // Store additional settings as JSON
            $table->decimal('entry_fee', 8, 2)->default(0.00);
            $table->text('rules')->nullable();
            $table->string('venue')->nullable();
            $table->text('prizes')->nullable();
            
            // Organizer Information
            $table->foreignId('organizer_id')->constrained('users')->onDelete('cascade');
            
            // SEO & Meta
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            // Tournament Results
            $table->json('bracket_data')->nullable(); // Store bracket structure
            $table->json('results')->nullable(); // Store final results
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes(); // For soft delete functionality
            
            // Indexes for performance
            $table->index(['status', 'type']);
            $table->index(['tournament_start_date', 'status']);
            $table->index('organizer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
