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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('public_id', 24)->nullable()->unique()->after('id');
            $table->boolean('public_profile_enabled')->default(false)->after('public_id');
        });

        DB::table('users')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (mixed $userId): void {
                do {
                    $publicId = bin2hex(random_bytes(12));
                } while (DB::table('users')->where('public_id', $publicId)->exists());

                DB::table('users')
                    ->where('id', $userId)
                    ->update(['public_id' => $publicId]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn(['public_id', 'public_profile_enabled']);
        });
    }
};
