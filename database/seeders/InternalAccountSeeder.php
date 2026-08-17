<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class InternalAccountSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@bozonknet.id'],
            [
                'name' => 'Admin BozonkNet',
                'password' => 'admin123',
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'teknisi@bozonknet.id'],
            [
                'name' => 'Teknisi BozonkNet',
                'password' => 'teknisi123',
                'role' => 'teknisi',
                'status' => 'active',
            ]
        );
    }
}
