<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::firstOrCreate(
            ['email' => 'admin@votecast.edu'],
            [
                'password'  => Hash::make('admin123'),
                'full_name' => 'System Administrator',
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        $this->command->info('');
        $this->command->info('✅  Admin account ready:');
        $this->command->info('   Super Admin    →  admin@votecast.edu     /  admin123');
        $this->command->info('');
    }
}
