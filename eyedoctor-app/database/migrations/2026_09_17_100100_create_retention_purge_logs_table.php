<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retention_purge_logs', function (Blueprint $table) {
            $table->id();

            // Intentionally no foreign key:
            // retention audit records must survive independently.
            $table->unsignedBigInteger('image_id')->index();

            $table->string('mode', 32);
            $table->string('outcome', 32);
            $table->text('message')->nullable();
            $table->timestamp('attempted_at');

            $table->timestamps();

            $table->index(
                ['outcome', 'attempted_at'],
                'retention_purge_outcome_time_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_purge_logs');
    }
};
