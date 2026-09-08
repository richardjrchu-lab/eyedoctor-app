<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->boolean('atypical_fundus_image')->default(false)->after('flagged_for_review');
            $table->float('fundus_signature_score')->nullable()->after('atypical_fundus_image');
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn(['atypical_fundus_image', 'fundus_signature_score']);
        });
    }
};