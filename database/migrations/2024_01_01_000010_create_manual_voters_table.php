<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manual_voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_session_id')->constrained('voting_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('added_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['voting_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_voters');
    }
};
