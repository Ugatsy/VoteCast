<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('manifesto')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();

            $table->unique(['position_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
