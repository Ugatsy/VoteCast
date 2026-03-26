<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 50)->unique()->nullable()->comment('Only for students');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('full_name');
            $table->string('photo')->nullable();
            $table->string('department', 100)->nullable()->index();
            $table->integer('year_level')->nullable();
            $table->string('section', 20)->nullable();
            $table->enum('role', ['student','super_admin','election_admin','department_admin','view_only'])
                  ->default('student');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
