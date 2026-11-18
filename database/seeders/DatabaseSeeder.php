<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Runs on every container start (see Dockerfile), so it only does things that are safe to repeat.
     * Demo data is separate: php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}
