<?php

namespace App\Console\Commands;

use App\Contracts\NotificationProvider;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Console\Command;

/**
 * Completes account-deletion requests after the documented grace period.
 *
 * Runs as a scheduled job so a student who requested deletion and then changed their
 * mind has time to cancel, and so the destructive step never happens inside a web
 * request without an explicit password confirmation.
 */
class ProcessAccountDeletions extends Command
{
    protected $signature = 'accounts:process-deletions {--dry-run : List accounts that would be anonymised}';

    protected $description = 'Anonymise accounts whose deletion grace period has expired';

    public function handle(AccountDeletionService $deletion, NotificationProvider $notifications): int
    {
        $graceDays = (int) config('exam_travel.retention.deletion_grace_days', 30);
        $dryRun = (bool) $this->option('dry-run');

        $users = User::query()
            ->whereNotNull('deletion_requested_at')
            ->where('deletion_requested_at', '<=', now()->subDays($graceDays))
            ->where('account_status', '!=', 'deleted')
            ->get();

        $this->info(sprintf('Accounts past the %d-day grace period: %d', $graceDays, $users->count()));

        foreach ($users as $user) {
            if ($dryRun) {
                $this->line('  would anonymise user #'.$user->id);

                continue;
            }

            if (! $deletion->canSelfDelete($user)) {
                $this->warn('  skipping administrator account #'.$user->id.' — needs manual review');

                continue;
            }

            $email = $user->email;
            $result = $deletion->anonymise($user);

            AuditLog::record('account_deleted', User::class, $user->id, 'scheduled after grace period');

            $notifications->send(
                new User(['email' => $email, 'name' => 'Deleted user']),
                'account_deleted',
                ['title' => 'Your account has been deleted', 'body' => 'Your Exam Travel Assist account and personal data have been removed.'],
            );

            $this->info('  anonymised user #'.$user->id.' (removed '.json_encode($result['removed']).')');
        }

        return self::SUCCESS;
    }
}
