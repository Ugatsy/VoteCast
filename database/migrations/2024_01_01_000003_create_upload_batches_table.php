<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('upload_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('semester', 50);
            $table->string('academic_year', 20);
            $table->integer('total_records')->default(0);
            $table->integer('imported_records')->default(0);
            $table->integer('skipped_records')->default(0);
            $table->json('statistics')->nullable();
            $table->json('errors')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['semester', 'academic_year'], 'idx_batch_semester');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_batches');
    }
};
