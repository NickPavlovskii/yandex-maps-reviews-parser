<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organizations')) {
            return;
        }

        DB::table('organizations')->whereNull('yandex_business_id')->delete();
        DB::statement('ALTER TABLE organizations ALTER COLUMN yandex_business_id SET NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE organizations ALTER COLUMN yandex_business_id DROP NOT NULL');
    }
};
