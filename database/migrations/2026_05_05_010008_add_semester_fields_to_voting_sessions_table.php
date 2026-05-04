<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('voting_sessions', function (Blueprint $table) {
            $table->string('semester', 50)->nullable()->after('target_section');
            $table->string('academic_year', 20)->nullable()->after('semester');

            // Add index for faster queries
            $table->index(['semester', 'academic_year']);
        });
    }

    public function down()
    {
        Schema::table('voting_sessions', function (Blueprint $table) {
            $table->dropColumn(['semester', 'academic_year']);
        });
    }
};
