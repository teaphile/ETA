<?php

namespace App\Console\Commands;

use App\Integrations\Support\DataStatus;
use App\Models\ExamCentre;
use App\Models\LocalService;
use Illuminate\Console\Command;

/**
 * Marks records whose verification has expired as `stale` so the UI can say so and the
 * admin panel can queue them for re-verification.
 *
 * Deliberately conservative:
 *  - records that were never verified keep their existing status (they are already
 *    displayed as unverified, and "stale" would imply they were once checked),
 *  - only verified records with an expired `valid_until` or an ageing
 *    `last_verified_at` are flagged.
 */
class FlagStaleData extends Command
{
    protected $signature = 'data:flag-stale {--dry-run : Report what would change without writing}';

    protected $description = 'Flag verified centres and listings whose verification has expired as stale';

    public function handle(): int
    {
        $centreMonths = (int) config('exam_travel.freshness.centre_months', 6);
        $serviceMonths = (int) config('exam_travel.freshness.service_months', 6);
        $dryRun = (bool) $this->option('dry-run');

        $verified = [DataStatus::OfficiallyVerified->value, DataStatus::AdminVerified->value, DataStatus::ThirdParty->value];

        $centres = ExamCentre::query()
            ->whereIn('data_status', $verified)
            ->where(function ($q) use ($centreMonths) {
                $q->where(fn ($x) => $x->whereNotNull('valid_until')->where('valid_until', '<', now()->toDateString()))
                    ->orWhere('last_verified_at', '<', now()->subMonths($centreMonths))
                    ->orWhereNull('last_verified_at');
            })
            ->get();

        $this->info(sprintf('Centres to flag stale: %d', $centres->count()));

        if (! $dryRun && $centres->isNotEmpty()) {
            ExamCentre::whereIn('id', $centres->pluck('id'))->update([
                'data_status' => DataStatus::Stale->value,
                'error_or_limitation' => 'Verification expired; awaiting re-check by an administrator.',
            ]);
        }

        $services = LocalService::query()
            ->whereIn('data_status', $verified)
            ->where(function ($q) use ($serviceMonths) {
                $q->where(fn ($x) => $x->whereNotNull('valid_until')->where('valid_until', '<', now()->toDateString()))
                    ->orWhere('last_verified_at', '<', now()->subMonths($serviceMonths))
                    ->orWhereNull('last_verified_at');
            })
            ->get();

        $this->info(sprintf('Local listings to flag stale: %d', $services->count()));

        if (! $dryRun && $services->isNotEmpty()) {
            LocalService::whereIn('id', $services->pluck('id'))->update([
                'data_status' => DataStatus::Stale->value,
                'error_or_limitation' => 'Listing not re-checked recently; confirm by phone before relying on it.',
            ]);
        }

        if ($dryRun) {
            $this->comment('Dry run — nothing was written.');
        }

        return self::SUCCESS;
    }
}
