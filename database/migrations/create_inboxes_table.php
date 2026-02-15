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
        Schema::create('inboxes', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->foreignId('creator_id')->constrained('users');
            $table->foreignId('recipient_id')->nullable()->constrained('users');
            $table->foreignId('patient_id')->nullable()->constrained('avad_patients');
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->string('patient_name')->nullable();
            $table->string('patient_birthday')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inboxes');
    }
};
