<?php

use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\User::firstOrCreate(
            ['email' => 'admin@alnes.com'],
            [
                'name' => 'AL-Nes',
                'password' => \Hash::make('password'),
                'role' => 'admin',
            ]
        );
    }
}
