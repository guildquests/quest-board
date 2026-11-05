<?php

declare(strict_types=1);

use App\Enums\QuestStatus;
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
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poster_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 60);
            $table->string('summary', 90);
            $table->string('category');
            $table->string('difficulty');
            $table->text('brief');
            $table->json('objectives');
            $table->string('proof', 500);
            $table->string('status')->default(QuestStatus::Open->value)->index();
            $table->foreignId('adventurer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('result_url', 2048)->nullable();
            $table->text('result_note')->nullable();
            $table->timestamp('turned_in_at')->nullable();
            $table->unsignedInteger('xp')->nullable();
            $table->unsignedInteger('poster_xp')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quests');
    }
};
