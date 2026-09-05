<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->foreignId('image_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('predicted_class');
            $table->float('confidence_score');
            $table->json('probabilities');
            $table->boolean('referral_flag')->default(false);
            $table->string('gradcam_path')->nullable();
            $table->string('model_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropColumn([
                'image_id',
                'predicted_class',
                'confidence_score',
                'probabilities',
                'referral_flag',
                'gradcam_path',
                'model_version',
            ]);
        });
    }
};