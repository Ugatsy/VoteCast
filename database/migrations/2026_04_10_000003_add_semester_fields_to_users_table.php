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
        Schema::table('users', function (Blueprint $table) {
            $table->string('semester', 50)->nullable()->after('section');
            $table->string('academic_year', 20)->nullable()->after('semester');
            $table->index(['semester', 'academic_year'], 'idx_users_semester');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_semester');
            $table->dropColumn(['semester', 'academic_year']);
        });
    }
};

