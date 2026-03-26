<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'markosavicle@gmail.com'],
            [
                'name' => 'Marko',
                'password' => Hash::make('qwe123qwe'),
            ]
        );
    }
}
