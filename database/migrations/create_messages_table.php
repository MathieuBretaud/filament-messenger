<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MathieuBretaud\FilamentMessenger\Models\Inbox;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Inbox::class)->constrained('inboxes')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->json('read_by')->nullable();
            $table->json('read_at')->nullable();
            $table->json('notified')->nullable();
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
