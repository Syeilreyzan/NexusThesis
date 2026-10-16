<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('Password123!');

        // Create 1 Admin
        User::updateOrCreate(
            ['email' => 'admin@nexusthesis.com'],
            [
                'name' => 'System Admin',
                'password' => $password,
            ]
        )->syncRoles(['admin']);

        // Create 20 Lecturers
        for ($i = 1; $i <= 20; $i++) {
            User::updateOrCreate(
                ['email' => sprintf('lecturer%02d@nexusthesis.com', $i)],
                [
                    'name' => sprintf('Lecturer %02d', $i),
                    'password' => $password,
                ]
            )->syncRoles(['lecturer']);
        }

        // Create 10 Students
        for ($i = 1; $i <= 10; $i++) {
            User::updateOrCreate(
                ['email' => sprintf('student%02d@nexusthesis.com', $i)],
                [
                    'name' => sprintf('Student %02d', $i),
                    'password' => $password,
                ]
            )->syncRoles(['student']);
        }
    }
}
