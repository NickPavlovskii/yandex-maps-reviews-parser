<?php

namespace Database\Seeders;

use App\Support\DefaultUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DefaultUser::ensure();
    }
}
