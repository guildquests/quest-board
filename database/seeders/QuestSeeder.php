<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Category;
use App\Enums\Difficulty;
use App\Models\Quest;
use App\Models\User;
use App\Services\QuestService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

/**
 * Local sample quests, one or more for every status and category. They go
 * through QuestService so the activity logs match what the app would write,
 * then get explicit times so the board order and the logs look realistic.
 *
 * Run after the users from DatabaseSeeder exist.
 */
class QuestSeeder extends Seeder
{
    public function run(QuestService $quests): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $lead = User::where('email', 'grace.marlow@example.com')->firstOrFail();
        $member = User::where('email', 'kit.thornton@example.com')->firstOrFail();
        $alan = User::where('email', 'alan.brooks@example.com')->firstOrFail();
        $lin = User::where('email', 'lin.torres@example.com')->firstOrFail();

        // Done, approved by the lead at a higher difficulty than posted.
        $doneA = $this->post($quests, $admin, [
            'title' => 'Write the Dokploy deploy checklist',
            'summary' => 'Ship the same way every time, even in a hurry',
            'category' => Category::Docs,
            'difficulty' => Difficulty::Medium,
            'brief' => 'The deploy steps live in a few heads and one old chat thread. Write one checklist for the CRM and the SOP site, from merge to smoke test.',
            'objectives' => ['List every step from merge to live', 'Add the smoke test checks', 'Have a teammate follow it once'],
            'proof' => 'The checklist on the SOP site, followed by a teammate without help.',
        ]);
        $quests->accept($doneA, $lin);
        $quests->turnIn($doneA, $lin, 'https://example.com/sop/dokploy-deploy', 'Followed it twice and added the queue restart step we kept forgetting.');
        $quests->approve($doneA, $lead, Difficulty::Hard);
        $this->retime($doneA, [
            now()->subDays(12), now()->subDays(11), now()->subDays(9), now()->subDays(8),
        ], [
            'claimed_at' => now()->subDays(11),
            'due_at' => now()->subDays(4),
            'turned_in_at' => now()->subDays(9),
            'approved_at' => now()->subDays(8),
        ]);

        // Done, the newest one: feeds the banner.
        $doneB = $this->post($quests, $lin, [
            'title' => 'Script the weekly database backup check',
            'summary' => 'Catch a missing backup before anyone needs it',
            'category' => Category::Tooling,
            'difficulty' => Difficulty::Easy,
            'brief' => 'Every Monday someone opens the backup folder by hand. A small script can check the newest file per database and post the result.',
            'objectives' => ['Check the newest file for each database', 'Flag anything older than seven days', 'Post the result to the team chat'],
            'proof' => 'The script in the team repo and one sample run.',
        ]);
        $quests->accept($doneB, $member);
        $quests->turnIn($doneB, $member, 'https://example.com/repo/backup-check', null);
        $quests->approve($doneB, $lead, null);
        $this->retime($doneB, [
            now()->subDays(7), now()->subHours(150), now()->subDays(5), now()->subDays(4),
        ], [
            'claimed_at' => now()->subHours(150),
            'due_at' => now()->subHours(150)->addDays(7),
            'turned_in_at' => now()->subDays(5),
            'approved_at' => now()->subDays(4),
        ]);

        // Open again after the claim ran out.
        $expired = $this->post($quests, $lin, [
            'title' => 'Load test the CRM with a 2 GB import',
            'summary' => 'Find out where the import gives up',
            'category' => Category::Experiment,
            'difficulty' => Difficulty::Medium,
            'brief' => 'The import works on small files. Try the full CRM export and note when it slows down, uses too much memory, or fails.',
            'objectives' => ['Run the full export', 'Note time and memory', 'Write down what failed'],
            'proof' => 'A short note with the numbers and the file size.',
        ]);
        $quests->accept($expired, $admin);
        Quest::whereKey($expired->id)->update(['due_at' => now()->subMinute()]);
        $quests->expireOverdueClaims();
        $this->retime($expired, [now()->subDays(10), now()->subDays(9), now()->subDays(2)]);

        // In progress after a send-back.
        $sentBack = $this->post($quests, $admin, [
            'title' => 'Prototype a Laravel log dashboard',
            'summary' => 'One page that shows which jobs are failing',
            'category' => Category::Experiment,
            'difficulty' => Difficulty::Hard,
            'brief' => 'Pull recent errors from the Laravel logs and show them per app, newest first, so we stop grepping on the server.',
            'objectives' => ['Collect errors per app', 'Show the newest ten', 'Link it from the team page'],
            'proof' => 'A link to the running dashboard and a short note on the data source.',
        ]);
        $quests->accept($sentBack, $lin);
        $quests->turnIn($sentBack, $lin, 'https://example.com/screens/log-dashboard.png', 'First version.');
        $quests->sendBack($sentBack, $lead, 'Please link the running dashboard, not the screenshot.');
        $this->retime($sentBack, [
            now()->subDays(6), now()->subDays(5), now()->subDays(3), now()->subDays(2),
        ], [
            'claimed_at' => now()->subDays(5),
            'due_at' => now()->subDays(2)->addDays(7),
            'turned_in_at' => null,
        ]);

        // In review, waiting for the lead.
        $review = $this->post($quests, $lead, [
            'title' => "Rank last quarter's top support issues",
            'summary' => 'Fix the tickets that keep coming back',
            'category' => Category::Research,
            'difficulty' => Difficulty::Medium,
            'brief' => "Read last quarter's support tickets and rank the issues by how often they repeat and how long they take to resolve.",
            'objectives' => ["Read the quarter's tickets", 'Group them by root cause', 'Rank the top five'],
            'proof' => 'A ranked list in a shared doc.',
        ]);
        $quests->accept($review, $alan);
        $quests->turnIn($review, $alan, 'https://example.com/docs/support-rank', 'Ranked by repeats; the top five are marked.');
        $this->retime($review, [
            now()->subDays(5), now()->subDays(3), now()->subDay(),
        ], [
            'claimed_at' => now()->subDays(3),
            'due_at' => now()->subDays(3)->addDays(7),
            'turned_in_at' => now()->subDay(),
        ]);

        // Open, newest last.
        $open = [
            [$member, 3, [
                'title' => 'Clean up stale branches in our GitLab group',
                'summary' => 'Make the repo list easy to scan again',
                'category' => Category::Tooling,
                'difficulty' => Difficulty::Easy,
                'brief' => 'Find branches with no commits in six months, check they are merged, and remove the safe ones. Leave a short note on what was kept.',
                'objectives' => ['List branches older than six months', 'Check which are merged', 'Remove the safe ones'],
                'proof' => 'A short note with the branches removed and the ones kept.',
            ]],
            [$lead, 2, [
                'title' => 'Compare three form backends for static sites',
                'summary' => 'Pick one form setup instead of three',
                'category' => Category::Research,
                'difficulty' => Difficulty::Medium,
                'brief' => 'We use a different form backend on every static site. Compare three on price, spam handling, and setup effort, then recommend one.',
                'objectives' => ['Pick three backends', 'Send a test form through each', 'Compare price and spam handling'],
                'proof' => 'A one-page comparison with a recommendation.',
            ]],
            [$admin, 1, [
                'title' => 'Write the onboarding guide for new developers',
                'summary' => 'Week one without asking the same ten questions',
                'category' => Category::Docs,
                'difficulty' => Difficulty::Medium,
                'brief' => 'Collect what a new developer needs in their first week: accounts, local setup, deploy access, and who to ask.',
                'objectives' => ['List the accounts to request', 'Describe the local setup', 'Link the deploy and on-call docs'],
                'proof' => 'A guide a new developer can follow on day one.',
            ]],
        ];

        foreach ($open as [$poster, $daysAgo, $data]) {
            $quest = $this->post($quests, $poster, $data);
            $this->retime($quest, [now()->subDays($daysAgo)]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function post(QuestService $quests, User $poster, array $data): Quest
    {
        return $quests->post($poster, $data);
    }

    /**
     * Give the quest's events these times, oldest first, and start the quest
     * at the first one. $columns overrides other timestamps on the quest.
     *
     * @param  list<CarbonInterface>  $eventTimes
     * @param  array<string, CarbonInterface|null>  $columns
     */
    private function retime(Quest $quest, array $eventTimes, array $columns = []): void
    {
        foreach ($quest->events()->get() as $index => $event) {
            $event->forceFill(['created_at' => $eventTimes[$index]])->save();
        }

        $quest->forceFill([
            'created_at' => $eventTimes[0],
            'updated_at' => end($eventTimes),
            ...$columns,
        ])->save();
    }
}
