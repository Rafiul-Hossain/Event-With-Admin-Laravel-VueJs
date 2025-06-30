<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersSeederTable extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'role' => 'admin',
            'name' => 'Admin',
            'email'=>'appadmin@examole.com',
            'password' => Hash::make('password')
        ]);

        User::create([
            'role' => 'event_admin',
            'name' => 'Event Admin',
            'email'=>'event_admin@examole.com',
            'password' => Hash::make('password')
        ]);

        User::create([
            'role' => 'user',
            'name' => 'Mridha',
            'email'=>'mridhan1920@gmail.com',
            'password' => Hash::make('password789')

        ]);

        User::create([
            'role' => 'user',
            'name' => 'Fahim',
            'email' => 'fahim@example.com',
            'password' => Hash::make('password')
        ]);

        User::create([
            'role' => 'user',
            'name' => 'Apu',
            'email' => 'apu@example.com',
            'password' => Hash::make('password')
        ]);
    }
}
