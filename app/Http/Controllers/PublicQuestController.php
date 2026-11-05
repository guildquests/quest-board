<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Models\Setting;
use App\Support\PublicQuest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PublicQuestController extends Controller
{
    public function __invoke(Request $request, string $publicId): InertiaResponse|SymfonyResponse
    {
        if (! preg_match('/^[0-9a-f]{24}$/', $publicId)) {
            return $this->unavailable($request);
        }

        $quest = Quest::query()->where('public_id', $publicId)->first();

        if ($quest === null) {
            return $this->unavailable($request);
        }

        if ($request->user() !== null) {
            return redirect()->route('quests.show', $quest);
        }

        $summary = PublicQuest::for($quest)->toArray();
        $setting = Setting::current();

        return Inertia::render('public/quest', [
            'quest' => $summary,
        ])->withViewData([
            'og' => [
                'title' => $quest->title,
                'description' => $quest->summary,
                'url' => route('quests.public', ['publicId' => $quest->public_id]),
                'site_name' => $setting->app_name,
                'image' => url($setting->headerUrl()),
            ],
        ]);
    }

    private function unavailable(Request $request): SymfonyResponse
    {
        return Inertia::render('public/quest-unavailable')
            ->toResponse($request)
            ->setStatusCode(404);
    }
}
