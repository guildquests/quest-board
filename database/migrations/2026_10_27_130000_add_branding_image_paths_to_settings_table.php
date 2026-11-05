<?php

declare(strict_types=1);

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
        Schema::table('settings', function (Blueprint $table): void {
            $table->string('header_path')->nullable()->after('favicon_path');
            $table->string('mascot_login_path')->nullable()->after('header_path');
            $table->string('mascot_empty_path')->nullable()->after('mascot_login_path');
            $table->string('mascot_reward_path')->nullable()->after('mascot_empty_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn([
                'header_path',
                'mascot_login_path',
                'mascot_empty_path',
                'mascot_reward_path',
            ]);
        });
    }
};
