<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('storage_path')->nullable()->change();

            $table->timestamp('retention_purged_at')
                ->nullable();

            $table->index(
                ['retention_purged_at', 'created_at'],
                'images_retention_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex('images_retention_lookup_idx');

            $table->dropColumn('retention_purged_at');

            $table->string('storage_path')
                ->nullable(false)
                ->change();
        });
    }
};
