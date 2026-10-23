<?php

namespace App\Console\Commands;

use App\Models\ChatConversation;
use App\Models\ImportRow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Enforces the documented retention windows.
 *
 * Nothing here deletes user content that the student can still see: it prunes
 * operational telemetry (search logs, integration events, chat transcripts and
 * import row payloads) whose purpose has expired. Account deletion is handled by
 * `accounts:process-deletions`.
 */
class PruneData extends Command
{
    protected $signature = 'data:prune {--dry-run : Report counts without deleting}';

    protected $description = 'Delete telemetry and transcripts older than the documented retention windows';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $targets = [
            'chat_conversations' => [
                'days' => (int) config('exam_travel.retention.chat_days', 90),
                'query' => fn ($cutoff) => ChatConversation::where('updated_at', '<', $cutoff),
            ],
            'search_logs' => [
                'days' => (int) config('exam_travel.retention.search_log_days', 180),
                'query' => fn ($cutoff) => DB::table('search_logs')->where('created_at', '<', $cutoff),
            ],
            'integration_events' => [
                'days' => (int) config('exam_travel.retention.integration_event_days', 90),
                'query' => fn ($cutoff) => DB::table('integration_events')->where('created_at', '<', $cutoff),
            ],
            'import_rows' => [
                'days' => (int) config('exam_travel.retention.import_row_days', 365),
                'query' => fn ($cutoff) => ImportRow::where('created_at', '<', $cutoff),
            ],
        ];

        foreach ($targets as $table => $config) {
            $cutoff = now()->subDays($config['days']);
            $count = $config['query']($cutoff)->count();

            if ($dryRun) {
                $this->info(sprintf('[dry-run] %s: %d row(s) older than %d days', $table, $count, $config['days']));

                continue;
            }

            $deleted = $config['query']($cutoff)->delete();
            $this->info(sprintf('%s: deleted %d row(s) older than %d days', $table, $deleted, $config['days']));
        }

        return self::SUCCESS;
    }
}
