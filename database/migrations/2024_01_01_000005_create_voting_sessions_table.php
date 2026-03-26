<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voting_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['department', 'course', 'manual'])->default('course');
            $table->string('target_department', 100)->nullable();
            $table->string('target_course', 50)->nullable();
            $table->enum('status', ['scheduled', 'active', 'paused', 'completed', 'cancelled'])
                  ->default('scheduled');
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->boolean('allow_vote_changes')->default(false);
            $table->boolean('requires_release_code')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('status', 'idx_status');
            $table->index(['start_date', 'end_date'], 'idx_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_sessions');
    }
};
