<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('student_code', 50);
            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->enum('sex', ['M', 'F']);
            $table->string('course', 50);
            $table->integer('year_level');
            $table->string('section', 20);
            $table->integer('units')->default(0);
            $table->string('semester', 50);
            $table->string('academic_year', 20);
            $table->boolean('is_active')->default(true);
            $table->foreignId('upload_batch_id')->nullable()
                  ->constrained('upload_batches')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_code', 'semester', 'academic_year'], 'unique_enrollment');
            $table->index(['semester', 'academic_year', 'is_active'], 'idx_current_semester');
            $table->index(['course', 'year_level', 'section'], 'idx_course_year_section');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
