<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\ExamCentre;
use App\Models\ExamSession;
use App\Models\LocalService;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Removes demonstration data so a production deployment starts clean.
 *
 * Demo rows are those seeded by Database\Seeders\DemoDataSeeder: centres and
 * listings flagged `is_demo`, the demo examination session, and the three demo
 * accounts. Real, imported data is never touched.
 *
 * Safe to run repeatedly. Reads nothing destructively unless --force is passed
 * (or you confirm at the prompt). `--dry-run` reports what would be deleted.
 */
class PurgeDemoData extends Command
{
    protected $signature = 'data:purge-demo
        {--dry-run : Report what would be deleted without changing anything}
        {--force : Skip the confirmation prompt}
        {--keep-users : Keep the demo accounts and delete only demo centres/listings}';

    protected $description = 'Delete demonstration centres, listings, sessions and accounts before launch';

    /** The exact demo accounts created by DemoDataSeeder. */
    private const DEMO_EMAILS = [
        'admin@example.com',
        'moderator@example.com',
        'student@example.com',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $keepUsers = (bool) $this->option('keep-users');

        $centres = ExamCentre::where('is_demo', true)->count();
        $listings = LocalService::where('is_demo', true)->count();
        $sessions = ExamSession::where('name', 'Demo Session 2026')->count();
        $users = $keepUsers ? 0 : User::whereIn('email', self::DEMO_EMAILS)->count();

        $this->table(['Demo data', 'Rows found'], [
            ['Exam centres (is_demo)', $centres],
            ['Local listings (is_demo)', $listings],
            ['Demo exam session', $sessions],
            ['Demo accounts', $users],
        ]);

        if ($centres + $listings + $sessions + $users === 0) {
            $this->info('No demo data found. Nothing to do.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Dry run: nothing was deleted.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Delete the demo data listed above?', false)) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        // Warn rather than silently lock everyone out of the admin panel.
        if ($users > 0) {
            $realAdmins = User::where('role', User::ROLE_ADMIN)
                ->whereNotIn('email', self::DEMO_EMAILS)
                ->count();

            if ($realAdmins === 0) {
                $this->warn('No non-demo administrator exists yet. Run `php artisan app:create-admin you@example.com` first,');
                $this->warn('otherwise you will have no way to sign in to the admin panel.');
            }
        }

        $deleted = DB::transaction(function () use ($keepUsers): array {
            // Listings first; centres cascade to any stragglers anyway.
            $listings = LocalService::where('is_demo', true)->delete();
            // Nulls itinerary references (nullOnDelete) and cascades remaining listings.
            $centres = ExamCentre::where('is_demo', true)->delete();
            $sessions = ExamSession::where('name', 'Demo Session 2026')->delete();

            $users = 0;
            if (! $keepUsers) {
                $users = User::whereIn('email', self::DEMO_EMAILS)->delete();
            }

            return compact('listings', 'centres', 'sessions', 'users');
        });

        AuditLog::record(
            'demo_data_purged',
            null,
            null,
            sprintf(
                'listings=%d centres=%d sessions=%d users=%d',
                $deleted['listings'],
                $deleted['centres'],
                $deleted['sessions'],
                $deleted['users'],
            ),
        );

        $this->info(sprintf(
            'Removed %d listings, %d centres, %d sessions and %d demo accounts.',
            $deleted['listings'],
            $deleted['centres'],
            $deleted['sessions'],
            $deleted['users'],
        ));

        $this->newLine();
        $this->line('Next: import real, lawfully-obtained centre data via Admin → CSV import.');

        return self::SUCCESS;
    }
}
