<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = Schema::getIndexes('reviews');
        $exists = collect($indexes)->contains(
            static fn (array $index): bool => $index['name'] === 'reviews_organization_id_published_at_index',
        );

        if ($exists) {
            return;
        }

        Schema::table('reviews', function (Blueprint $table): void {
            $table->index(['organization_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'published_at']);
        });
    }
};
