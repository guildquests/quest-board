<?php

declare(strict_types=1);

use App\Http\Controllers\ComponentLibraryController;
use App\Http\Controllers\HallOfFameController;
use App\Http\Controllers\MemberSharingController;
use App\Http\Controllers\ProfilePageController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\PublicQuestController;
use App\Http\Controllers\QuestActionController;
use App\Http\Controllers\QuestBoardController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RewardController;
use Illuminate\Support\Facades\Route;

Route::get('guild/m/{publicId}', PublicProfileController::class)
    ->middleware('throttle:60,1')
    ->name('guild.member');

Route::get('q/{publicId}', PublicQuestController::class)
    ->middleware('throttle:60,1')
    ->name('quests.public');

Route::middleware(['auth'])->group(function () {
    Route::get('/', QuestBoardController::class)->name('board');

    Route::get('hall-of-fame', HallOfFameController::class)->name('hall-of-fame');

    Route::get('me', ProfilePageController::class)->name('me');
    Route::patch('me/sharing', [MemberSharingController::class, 'update'])->name('me.sharing.update');
    Route::post('me/sharing/regenerate', [MemberSharingController::class, 'regenerate'])->name('me.sharing.regenerate');
    Route::get('reviews', ReviewController::class)->middleware('can:review-queue')->name('reviews');

    Route::get('quests/create', [QuestController::class, 'create'])->name('quests.create');
    Route::post('quests', [QuestController::class, 'store'])->name('quests.store');
    Route::get('quests/{quest}', [QuestController::class, 'show'])->name('quests.show');
    Route::get('quests/{quest}/edit', [QuestController::class, 'edit'])->name('quests.edit');
    Route::put('quests/{quest}', [QuestController::class, 'update'])->name('quests.update');
    Route::delete('quests/{quest}', [QuestController::class, 'destroy'])->name('quests.destroy');

    Route::post('quests/{quest}/accept', [QuestActionController::class, 'accept'])->name('quests.accept');
    Route::post('quests/{quest}/drop', [QuestActionController::class, 'drop'])->name('quests.drop');
    Route::post('quests/{quest}/turn-in', [QuestActionController::class, 'turnIn'])->name('quests.turn-in');
    Route::post('quests/{quest}/send-back', [QuestActionController::class, 'sendBack'])->name('quests.send-back');
    Route::post('quests/{quest}/approve', [QuestActionController::class, 'approve'])->name('quests.approve');
    Route::post('rewards/seen', [RewardController::class, 'seen'])->name('rewards.seen');

    // Component library for the team; not in the app nav.
    Route::get('docs/library', ComponentLibraryController::class)->name('docs.library');
});

require __DIR__.'/settings.php';
