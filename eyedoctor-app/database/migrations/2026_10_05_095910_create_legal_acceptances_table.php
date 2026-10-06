<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_acceptances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('document_type');
            $table->string('document_version');

            $table->timestamp('accepted_at');

            $table->timestamps();

            $table->unique([
                'user_id',
                'document_type',
                'document_version'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_acceptances');
    }
};