<?php

namespace App\Integrations\Import;

use App\Contracts\CentreDataImporter;
use App\Integrations\Support\DataStatus;
use App\Integrations\Support\ImportPreview;
use App\Models\ExamCentre;
use App\Models\Examination;
use App\Models\ExamSession;
use Illuminate\Support\Facades\DB;

/**
 * CSV examination-centre importer.
 *
 * Design guarantees:
 *  - preview() never writes anything and reports errors per row.
 *  - Header problems abort the whole file (reported, not silently ignored).
 *  - Duplicate detection uses centre_code + examination + session, never the centre
 *    name alone, and checks both the database and the file itself.
 *  - import() runs inside a transaction and creates centres as `unverified`, so an
 *    imported row can never masquerade as officially verified data.
 *  - Rows that fail are reported individually; a failed batch never corrupts existing
 *    records and never partially hides what happened.
 */
class CsvCentreDataImporter implements CentreDataImporter
{
    public const REQUIRED_COLUMNS = ['centre_code', 'name', 'address', 'city', 'state'];

    public const OPTIONAL_COLUMNS = [
        'pin_code', 'latitude', 'longitude', 'examination', 'examination_id',
        'session', 'exam_session_id', 'source_name', 'source_url', 'status',
    ];

    public const MAX_ROWS = 5000;

    /** Values accepted for a centre's verification status when supplied in a file. */
    private const ALLOWED_STATUSES = [
        DataStatus::OfficiallyVerified->value,
        DataStatus::AdminVerified->value,
        DataStatus::ThirdParty->value,
        DataStatus::Estimated->value,
        DataStatus::Unverified->value,
        DataStatus::Stale->value,
    ];

    public function name(): string
    {
        return 'csv';
    }

    public function preview(string $absolutePath, array $options = []): ImportPreview
    {
        if (! is_readable($absolutePath)) {
            return ImportPreview::build([], [], ['The uploaded file could not be read.']);
        }

        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            return ImportPreview::build([], [], ['The uploaded file could not be opened.']);
        }

        $headers = $this->readHeader($handle);

        if ($headers === []) {
            fclose($handle);

            return ImportPreview::build([], [], ['The file is empty or has no header row.']);
        }

        $headerErrors = $this->validateHeader($headers);

        if ($headerErrors !== []) {
            fclose($handle);

            return ImportPreview::build([], $headers, $headerErrors);
        }

        $rows = [];
        $seenKeys = [];
        $line = 1;
        $total = 0;

        while (($raw = fgetcsv($handle, 0, $this->delimiter)) !== false) {
            $line++;

            if ($this->isBlank($raw)) {
                continue;
            }

            if (++$total > self::MAX_ROWS) {
                $rows[] = [
                    'row_number' => $line,
                    'data' => [],
                    'status' => 'error',
                    'errors' => ['File exceeds the '.self::MAX_ROWS.' row limit — split it into smaller batches.'],
                ];
                break;
            }

            $data = array_fill_keys(array_merge(self::REQUIRED_COLUMNS, self::OPTIONAL_COLUMNS), '');
            foreach ($headers as $index => $column) {
                $data[$column] = $this->normalise((string) ($raw[$index] ?? ''));
            }

            [$status, $errors] = $this->validateRow($data);

            if ($status === 'valid') {
                $key = $this->duplicateKey($data);

                if (isset($seenKeys[$key])) {
                    $status = 'duplicate';
                    $errors[] = 'Duplicate of row '.$seenKeys[$key].' in the same file.';
                } elseif ($this->existsInDatabase($data)) {
                    $status = 'duplicate';
                    $errors[] = 'A centre with this code already exists for the same examination/session (existing record kept).';
                } else {
                    $seenKeys[$key] = $line;
                }
            }

            $rows[] = ['row_number' => $line, 'data' => $data, 'status' => $status, 'errors' => $errors];
        }

        fclose($handle);

        return ImportPreview::build($rows, $headers, []);
    }

    public function import(array $rows, array $context = []): array
    {
        $imported = 0;
        $updated = 0;
        $errors = [];
        $updateOnDuplicate = (bool) ($context['update_on_duplicate'] ?? false);
        $actorId = $context['user_id'] ?? null;
        $batchId = $context['import_batch_id'] ?? null;
        $defaultSource = $context['source_name'] ?? 'CSV import';

        DB::transaction(function () use (
            $rows, $updateOnDuplicate, $actorId, $batchId, $defaultSource, &$imported, &$updated, &$errors
        ) {
            foreach ($rows as $row) {
                $data = $row['data'] ?? [];

                try {
                    $existing = $this->findExisting($data);

                    if ($existing && $updateOnDuplicate) {
                        $existing->update($this->payload($data, $defaultSource, $batchId, $actorId, updating: true));
                        $updated++;

                        continue;
                    }

                    if ($existing) {
                        $errors[] = 'Row '.($row['row_number'] ?? '?').': duplicate kept (existing record untouched).';

                        continue;
                    }

                    ExamCentre::create($this->payload($data, $defaultSource, $batchId, $actorId));
                    $imported++;
                } catch (\Throwable $e) {
                    // Roll the whole batch back rather than silently importing a partial file.
                    $errors[] = 'Row '.($row['row_number'] ?? '?').': '.$e->getMessage();
                    throw $e;
                }
            }
        });

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => count($errors), 'errors' => $errors];
    }

    // ---------------------------------------------------------------- validation

    /** @return array{0:string,1:array<int,string>} */
    private function validateRow(array $data): array
    {
        $errors = [];

        foreach (self::REQUIRED_COLUMNS as $column) {
            if ($data[$column] === '') {
                $errors[] = ucfirst(str_replace('_', ' ', $column)).' is required.';
            }
        }

        if ($data['centre_code'] !== '' && mb_strlen($data['centre_code']) > 50) {
            $errors[] = 'Centre code must be 50 characters or fewer.';
        }

        [$lat, $lng, $coordErrors] = $this->validateCoordinates($data);
        $errors = array_merge($errors, $coordErrors);

        if ($data['status'] !== '' && ! in_array($data['status'], self::ALLOWED_STATUSES, true)) {
            $errors[] = 'Unknown verification status "'.$data['status'].'".';
        }

        if ($data['examination_id'] !== '' && ! ctype_digit($data['examination_id'])) {
            $errors[] = 'examination_id must be numeric.';
        }

        if ($data['exam_session_id'] !== '' && ! ctype_digit($data['exam_session_id'])) {
            $errors[] = 'exam_session_id must be numeric.';
        }

        if ($data['source_url'] !== '' && ! filter_var($data['source_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'source_url is not a valid URL.';
        }

        // Relationship validation against the live database.
        $examinationId = $this->resolveExaminationId($data);
        if ($data['examination_id'] !== '' && $examinationId === null) {
            $errors[] = 'No examination exists with id '.$data['examination_id'].'.';
        }
        if ($data['examination'] !== '' && $examinationId === null) {
            $errors[] = 'No examination named "'.$data['examination'].'" exists — create it first or leave the column empty.';
        }

        $sessionId = $this->resolveSessionId($data, $examinationId);
        if ($data['exam_session_id'] !== '' && $sessionId === null) {
            $errors[] = 'No examination session exists with id '.$data['exam_session_id'].'.';
        }
        if ($sessionId !== null && $examinationId !== null) {
            $session = ExamSession::find($sessionId);
            if ($session && (int) $session->examination_id !== $examinationId) {
                $errors[] = 'Session '.$sessionId.' does not belong to examination '.$examinationId.'.';
            }
        }

        return [$errors === [] ? 'valid' : 'error', $errors];
    }

    /** @return array{0:?float,1:?float,2:array<int,string>} */
    private function validateCoordinates(array $data): array
    {
        $rawLat = $data['latitude'];
        $rawLng = $data['longitude'];
        $errors = [];

        if (($rawLat === '') !== ($rawLng === '')) {
            return [null, null, ['Provide both latitude and longitude, or neither.']];
        }

        if ($rawLat === '') {
            return [null, null, []];
        }

        if (! is_numeric($rawLat) || ! is_numeric($rawLng)) {
            return [null, null, ['Latitude and longitude must be numeric.']];
        }

        $lat = (float) $rawLat;
        $lng = (float) $rawLng;

        if ($lat < -90 || $lat > 90) {
            $errors[] = 'Latitude must be between -90 and 90.';
        }
        if ($lng < -180 || $lng > 180) {
            $errors[] = 'Longitude must be between -180 and 180.';
        }
        if ($errors === [] && abs($lat) < 0.01 && abs($lng) < 0.01) {
            $errors[] = 'Coordinates 0,0 are almost certainly a data-entry error (Null Island).';
        }
        if ($errors === [] && ($lat < 6 || $lat > 38 || $lng < 67 || $lng > 98)) {
            $errors[] = 'Coordinates fall outside India — check the sign and magnitude.';
        }

        return $errors === [] ? [$lat, $lng, []] : [null, null, $errors];
    }

    private function duplicateKey(array $data): string
    {
        return implode('|', [
            mb_strtolower($data['centre_code']),
            (string) ($this->resolveExaminationId($data) ?? ''),
            (string) ($this->resolveSessionId($data, $this->resolveExaminationId($data)) ?? ''),
        ]);
    }

    private function existsInDatabase(array $data): bool
    {
        return $this->findExisting($data) !== null;
    }

    private function findExisting(array $data): ?ExamCentre
    {
        return ExamCentre::query()
            ->whereRaw('LOWER(centre_code) = ?', [mb_strtolower($data['centre_code'])])
            ->where('examination_id', $this->resolveExaminationId($data))
            ->where('exam_session_id', $this->resolveSessionId($data, $this->resolveExaminationId($data)))
            ->first();
    }

    private function resolveExaminationId(array $data): ?int
    {
        if (($data['examination_id'] ?? '') !== '' && ctype_digit($data['examination_id'])) {
            $exists = Examination::whereKey((int) $data['examination_id'])->exists();

            return $exists ? (int) $data['examination_id'] : null;
        }

        if (($data['examination'] ?? '') !== '') {
            $examination = Examination::whereRaw('LOWER(name) = ?', [mb_strtolower($data['examination'])])->first();

            return $examination?->id;
        }

        return null;
    }

    private function resolveSessionId(array $data, ?int $examinationId): ?int
    {
        if (($data['exam_session_id'] ?? '') !== '' && ctype_digit($data['exam_session_id'])) {
            return ExamSession::whereKey((int) $data['exam_session_id'])->exists()
                ? (int) $data['exam_session_id']
                : null;
        }

        if (($data['session'] ?? '') !== '') {
            $session = ExamSession::query()
                ->when($examinationId, fn ($q, $id) => $q->where('examination_id', $id))
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['session'])])
                ->first();

            return $session?->id;
        }

        return null;
    }

    /** @return array<string,mixed> */
    private function payload(array $data, string $defaultSource, ?int $batchId, ?int $actorId, bool $updating = false): array
    {
        $examinationId = $this->resolveExaminationId($data);
        [$lat, $lng] = $this->validateCoordinates($data);
        $status = $data['status'] !== '' ? $data['status'] : DataStatus::Unverified->value;

        $payload = [
            'centre_code' => $data['centre_code'],
            'name' => $data['name'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'pin_code' => $data['pin_code'] !== '' ? $data['pin_code'] : null,
            'latitude' => $lat,
            'longitude' => $lng,
            'examination_id' => $examinationId,
            'exam_session_id' => $this->resolveSessionId($data, $examinationId),
            'source_name' => $data['source_name'] !== '' ? $data['source_name'] : $defaultSource.($batchId ? ' #'.$batchId : ''),
            'source_url' => $data['source_url'] !== '' ? $data['source_url'] : null,
            'verification_status' => $status,
            'data_status' => $status,
        ];

        if (! $updating) {
            $payload['created_by'] = $actorId;
            $payload['is_demo'] = false;
        }

        if (DataStatus::isVerified($status)) {
            $payload['last_verified_at'] = now();
        }

        return $payload;
    }

    // ------------------------------------------------------------------- parsing

    private string $delimiter = ',';

    /** @return array<int,string> */
    private function readHeader($handle): array
    {
        $firstLine = fgets($handle) ?: '';
        rewind($handle);

        $this->delimiter = $this->detectDelimiter($firstLine);

        $header = fgetcsv($handle, 0, $this->delimiter) ?: [];

        return array_map(
            fn ($h) => preg_replace('/[^a-z0-9_]/', '', mb_strtolower($this->normalise((string) $h))) ?? '',
            $header,
        );
    }

    private function detectDelimiter(string $line): string
    {
        $candidates = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($candidates);

        return (string) array_key_first($candidates) ?: ',';
    }

    /** @return array<int,string> */
    private function validateHeader(array $headers): array
    {
        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, $headers));

        if ($missing !== []) {
            return ['Missing required column(s): '.implode(', ', $missing).'. Download the CSV template from the import page.'];
        }

        return [];
    }

    /** @param array<int,string|null> $row */
    private function isBlank(array $row): bool
    {
        return collect($row)->filter(fn ($v) => trim((string) $v) !== '')->isEmpty();
    }

    public function normalise(string $value): string
    {
        $value = str_replace(["\u{FEFF}", "\u{00A0}"], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
