<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quests', function (Blueprint $table): void {
            $table->string('public_id', 24)->nullable()->unique()->after('id');
        });

        DB::table('quests')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (mixed $questId): void {
                do {
                    $publicId = bin2hex(random_bytes(12));
                } while (DB::table('quests')->where('public_id', $publicId)->exists());

                DB::table('quests')
                    ->where('id', $questId)
                    ->update(['public_id' => $publicId]);
            });

        Schema::table('quests', function (Blueprint $table): void {
            $table->string('public_id', 24)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
