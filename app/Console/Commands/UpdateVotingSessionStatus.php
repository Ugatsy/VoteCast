<?php

namespace App\Console\Commands;

use App\Models\VotingSession;
use Illuminate\Console\Command;

class UpdateVotingSessionStatus extends Command
{
    protected $signature = 'sessions:update-status';
    protected $description = 'Auto update voting session status based on dates';

    public function handle()
    {
        $now = now();

        // Activate scheduled sessions that have started
        VotingSession::where('status', 'scheduled')
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->update(['status' => 'active']);

        // Complete active sessions that have ended
        VotingSession::where('status', 'active')
            ->where('end_date', '<', $now)
            ->update(['status' => 'completed']);

        $this->info('Voting session statuses updated.');
    }
}
