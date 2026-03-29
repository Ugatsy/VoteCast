<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old category constraint
        DB::statement("ALTER TABLE voting_sessions DROP CONSTRAINT voting_sessions_category_check");

        // Add new constraint with 'section' included
        DB::statement("ALTER TABLE voting_sessions ADD CONSTRAINT voting_sessions_category_check
            CHECK (category IN ('department', 'course', 'section', 'manual'))");

        // Add target_section column
        Schema::table('voting_sessions', function (Blueprint $table) {
            $table->string('target_section', 20)->nullable()->after('target_course');
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE voting_sessions DROP CONSTRAINT voting_sessions_category_check");
        DB::statement("ALTER TABLE voting_sessions ADD CONSTRAINT voting_sessions_category_check
            CHECK (category IN ('department', 'course', 'manual'))");

        Schema::table('voting_sessions', function (Blueprint $table) {
            $table->dropColumn('target_section');
        });
    }
};
