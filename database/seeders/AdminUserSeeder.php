<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
                'is_admin' => true,
                'approved' => true,
            ]
        );
    }
}
