<?php

use App\Models\User;
use App\Support\DefaultUser;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DefaultUser::ensure();
    }

    public function down(): void
    {
        User::query()->where('email', DefaultUser::EMAIL)->delete();
    }
};
