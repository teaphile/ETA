<?php

namespace App\Contracts;

use App\Integrations\Support\ImportPreview;

/**
 * Validates and imports examination-centre datasets supplied by administrators.
 *
 * Implementations must never invent centres, must validate every row, must keep
 * provenance, and must be safe to run twice.
 */
interface CentreDataImporter
{
    public function name(): string;

    /**
     * Parse and validate a file without writing any centre records.
     */
    public function preview(string $absolutePath, array $options = []): ImportPreview;

    /**
     * Persist only the rows accepted by preview(), inside a transaction.
     *
     * @param  array<int,array<string,mixed>>  $rows  accepted rows (row_number + data)
     * @return array{imported:int,skipped:int,errors:array<int,string>}
     */
    public function import(array $rows, array $context = []): array;
}
