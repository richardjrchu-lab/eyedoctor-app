<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->float('referable_probability')->nullable()->after('confidence_score');
            $table->boolean('flagged_for_review')->default(false)->after('referral_flag');
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn(['referable_probability', 'flagged_for_review']);
        });
    }
};