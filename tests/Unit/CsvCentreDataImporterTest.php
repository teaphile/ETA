<?php

namespace Tests\Unit;

use App\Integrations\Import\CsvCentreDataImporter;
use Tests\TestCase;

class CsvCentreDataImporterTest extends TestCase
{
    private function writeCsv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv').'.csv';
        file_put_contents($path, $contents);

        return $path;
    }

    public function test_preview_rejects_missing_required_headers_without_writing(): void
    {
        $path = $this->writeCsv("name,city\nFoo,Bar\n");

        $preview = (new CsvCentreDataImporter)->preview($path);

        $this->assertTrue($preview->isRejected());
        $this->assertNotEmpty($preview->headerErrors);
        $this->assertSame(0, $preview->totalRows);

        @unlink($path);
    }

    public function test_preview_reports_an_unreadable_file(): void
    {
        $preview = (new CsvCentreDataImporter)->preview('/tmp/definitely-not-here-'.uniqid().'.csv');

        $this->assertTrue($preview->isRejected());
    }

    public function test_preview_rejects_an_empty_file(): void
    {
        $path = $this->writeCsv('');
        $preview = (new CsvCentreDataImporter)->preview($path);

        $this->assertTrue($preview->isRejected());

        @unlink($path);
    }

    public function test_required_and_optional_column_contract_is_stable(): void
    {
        $this->assertSame(['centre_code', 'name', 'address', 'city', 'state'], CsvCentreDataImporter::REQUIRED_COLUMNS);
        $this->assertContains('latitude', CsvCentreDataImporter::OPTIONAL_COLUMNS);
    }
}
