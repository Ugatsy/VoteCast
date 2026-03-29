<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixVotesUniqueConstraintForMultiWinners extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing unique constraint
        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique('votes_voter_id_position_id_unique');
        });

        // Add new unique constraint that includes candidate_id
        // This allows a voter to vote for multiple candidates in the same position
        // but prevents voting for the same candidate twice
        Schema::table('votes', function (Blueprint $table) {
            $table->unique(['voter_id', 'position_id', 'candidate_id'], 'votes_voter_position_candidate_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique('votes_voter_position_candidate_unique');
            $table->unique(['voter_id', 'position_id'], 'votes_voter_id_position_id_unique');
        });
    }
}
