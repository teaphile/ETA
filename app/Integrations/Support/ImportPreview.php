<?php

namespace App\Integrations\Support;

/**
 * Row-level validation report produced before anything is written to the database.
 *
 * Nothing is imported until an administrator reviews this report and approves it,
 * and a rejected row never affects the valid rows.
 */
final readonly class ImportPreview
{
    /**
     * @param  array<int,array{row_number:int,data:array<string,string>,status:string,errors:array<int,string>}>  $rows
     * @param  array<int,string>  $headerErrors
     */
    public function __construct(
        public array $rows = [],
        public array $headerErrors = [],
        public array $headers = [],
        public int $validRows = 0,
        public int $errorRows = 0,
        public int $duplicateRows = 0,
        public int $totalRows = 0,
    ) {}

    public static function build(array $rows, array $headers, array $headerErrors = []): self
    {
        $counts = ['valid' => 0, 'error' => 0, 'duplicate' => 0];

        foreach ($rows as $row) {
            $counts[$row['status']] = ($counts[$row['status']] ?? 0) + 1;
        }

        return new self(
            rows: $rows,
            headerErrors: $headerErrors,
            headers: $headers,
            validRows: $counts['valid'],
            errorRows: $counts['error'],
            duplicateRows: $counts['duplicate'],
            totalRows: count($rows),
        );
    }

    public function isRejected(): bool
    {
        return $this->headerErrors !== [];
    }

    /** @return array<int,array{row_number:int,data:array<string,string>,status:string,errors:array<int,string>}> */
    public function validRows(): array
    {
        return array_values(array_filter($this->rows, fn ($r) => $r['status'] === 'valid'));
    }

    public function summary(): string
    {
        return sprintf(
            '%d row(s): %d valid, %d duplicate, %d with errors',
            $this->totalRows,
            $this->validRows,
            $this->duplicateRows,
            $this->errorRows,
        );
    }

    public function toArray(): array
    {
        return [
            'headers' => $this->headers,
            'header_errors' => $this->headerErrors,
            'rows' => $this->rows,
            'valid_rows' => $this->validRows,
            'error_rows' => $this->errorRows,
            'duplicate_rows' => $this->duplicateRows,
            'total_rows' => $this->totalRows,
        ];
    }
}
