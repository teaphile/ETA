<?php

use App\Console\Commands\FlagStaleData;
use App\Console\Commands\ProcessAccountDeletions;
use App\Console\Commands\PruneData;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled maintenance
|--------------------------------------------------------------------------
| Requires a single cron entry in production:
|   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
| All three commands are safe to run repeatedly and support --dry-run.
*/
Schedule::command(PruneData::class)->dailyAt('02:15')->withoutOverlapping();
Schedule::command(FlagStaleData::class)->dailyAt('02:45')->withoutOverlapping();
Schedule::command(ProcessAccountDeletions::class)->dailyAt('03:15')->withoutOverlapping();
