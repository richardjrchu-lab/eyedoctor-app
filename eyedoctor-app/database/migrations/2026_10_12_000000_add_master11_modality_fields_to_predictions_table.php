<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master 11 (advisory modality check) result for archived predictions.
 *
 * All columns are nullable: predictions made before Master 11 have no
 * modality check and must read as "not recorded", never as supported.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->double('master11_score')->nullable();
            $table->string('master11_state', 32)->nullable();
            $table->boolean('modality_warning')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn([
                'master11_score',
                'master11_state',
                'modality_warning',
            ]);
        });
    }
};
