<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateVotingSessionStatus extends Command
{
    protected $signature   = 'sessions:update-status';
    protected $description = 'Auto-sync voting session status from start/end dates';

    public function handle(): void
    {
        // Call the Postgres function directly — single round-trip, no PHP logic
        $changed = DB::selectOne(
            'SELECT "VoteCast".sync_all_session_statuses() AS updated'
        )->updated;

        $this->info("Synced {$changed} session(s).");
    }
}
