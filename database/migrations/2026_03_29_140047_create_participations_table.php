<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('receipt_id')->unique();
            $table->boolean('has_votes')->default(true);
            $table->timestamp('voted_at')->useCurrent();
            $table->timestamps();

            $table->unique(['voting_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participations');
    }
};
