<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->double('master12_score')->nullable();
            $table->double('master12_threshold')->nullable();
            $table->boolean('dr_scope_passed')->nullable();
            $table->boolean('scope_warning')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn([
                'master12_score',
                'master12_threshold',
                'dr_scope_passed',
                'scope_warning',
            ]);
        });
    }
};