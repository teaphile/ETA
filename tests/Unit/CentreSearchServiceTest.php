<?php

namespace Tests\Unit;

use App\Services\CentreSearchService;
use App\Services\SearchLogger;
use Tests\TestCase;

class CentreSearchServiceTest extends TestCase
{
    private function service(): CentreSearchService
    {
        return new CentreSearchService(new SearchLogger);
    }

    public function test_normalise_trims_and_collapses_whitespace(): void
    {
        $out = $this->service()->normalise(['q' => "   New\u{00A0}Delhi  "]);

        $this->assertSame('New Delhi', $out['q']);
    }

    public function test_normalise_strips_control_characters_and_bounds_length(): void
    {
        $out = $this->service()->normalise(['city' => "Del\x00hi\x1F", 'q' => str_repeat('a', 500)]);

        $this->assertSame('Delhi', $out['city']);
        $this->assertSame(100, mb_strlen($out['q']));
    }

    public function test_normalise_defaults_and_validates_sort(): void
    {
        $this->assertSame('city', $this->service()->normalise([])['sort']);
        $this->assertSame('name', $this->service()->normalise(['sort' => 'name'])['sort']);
        $this->assertSame('city', $this->service()->normalise(['sort' => 'DROP TABLE'])['sort']);
    }

    public function test_normalise_coerces_positive_integers_only(): void
    {
        $out = $this->service()->normalise(['exam' => '12', 'session' => '-4']);

        $this->assertSame(12, $out['exam']);
        $this->assertNull($out['session']);
    }

    public function test_empty_values_become_null(): void
    {
        $out = $this->service()->normalise(['city' => '   ', 'q' => '']);

        $this->assertNull($out['city']);
        $this->assertNull($out['q']);
    }
}
