<?php

namespace Tests\Feature\Nfr;

use Tests\TestCase;

/**
 * Keeps the requirement documents honest (docs/Non_Functional_Requirements.md, docs/Functional_Requirements.md):
 * the NFR document may only cite tests that exist, every row must carry a recognised result, the summary
 * counts must equal the rows, and there must be exactly one functional requirement per use case narrative.
 */
class NfrTraceabilityTest extends TestCase
{
    private const RESULTS = ['Pass', 'Fail', 'Not tested'];

    /** @return array<int,array{id:string,evidence:string,result:string}> */
    private function nfrRows(): array
    {
        $rows = [];

        foreach (file(base_path('docs/Non_Functional_Requirements.md'), FILE_IGNORE_NEW_LINES) as $line) {
            if (! str_starts_with($line, '| NFR-')) {
                continue;
            }
            // split on the column pipes (not on an escaped \| inside a note); an empty last cell must survive
            $cells = array_map('trim', array_slice(preg_split('/(?<!\\\\)\|/', $line), 1, -1));
            $this->assertCount(6, $cells, "NFR row has the wrong number of columns: {$cells[0]}");

            $result = preg_match('/Not tested/', $cells[4]) ? 'Not tested' : (preg_match('/Fail/', $cells[4]) ? 'Fail' : (preg_match('/Pass/', $cells[4]) ? 'Pass' : '?'));
            $rows[] = ['id' => $cells[0], 'evidence' => $cells[3], 'result' => $result];
        }

        return $rows;
    }

    private function resolveClass(string $short): ?string
    {
        foreach (["Tests\\Feature\\{$short}", "Tests\\Feature\\Nfr\\{$short}"] as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    public function test_the_document_has_the_expected_shape()
    {
        $rows = $this->nfrRows();

        $this->assertGreaterThan(60, count($rows));
        $this->assertSame(count($rows), count(array_unique(array_column($rows, 'id'))), 'duplicate NFR id');

        foreach ($rows as $row) {
            $this->assertMatchesRegularExpression('/^NFR-[A-Z]+-\d{2}$/', $row['id']);
            $this->assertContains($row['result'], self::RESULTS, "{$row['id']} has no recognised result");
        }
    }

    public function test_every_test_the_nfr_document_cites_exists()
    {
        $cited = 0;

        foreach ($this->nfrRows() as $row) {
            preg_match_all('/`([A-Z][A-Za-z0-9]*Test)(?:::(test_\w+))?`/', $row['evidence'], $matches, PREG_SET_ORDER);

            foreach ($matches as $m) {
                $class = $this->resolveClass($m[1]);
                $this->assertNotNull($class, "{$row['id']} cites a test class that does not exist: {$m[1]}");

                if (isset($m[2])) {
                    $this->assertTrue(method_exists($class, $m[2]), "{$row['id']} cites a test that does not exist: {$m[1]}::{$m[2]}");
                }
                $cited++;
            }
        }

        $this->assertGreaterThan(150, $cited, 'the document should cite plenty of tests');
    }

    public function test_a_passing_row_cites_evidence_and_a_failing_row_cites_a_known_gap_test()
    {
        foreach ($this->nfrRows() as $row) {
            $hasTest = (bool) preg_match('/`[A-Z][A-Za-z0-9]*Test|phpunit|pint/', $row['evidence']);

            if ($row['result'] === 'Pass') {
                $this->assertTrue($hasTest || str_contains($row['evidence'], 'manual'), "{$row['id']} passes but cites no test");
            }
            if ($row['result'] === 'Fail') {
                $this->assertTrue(
                    str_contains($row['evidence'], 'KnownGapsNfrTest::test_gap_') || str_contains($row['evidence'], 'pint'),
                    "{$row['id']} fails but cites neither a known-gap test nor a tool run"
                );
            }
            if ($row['result'] === 'Not tested') {
                $this->assertStringNotContainsString('Test::', $row['evidence'], "{$row['id']} says not tested but cites a test");
            }
        }
    }

    public function test_each_known_gap_test_is_tagged_with_the_requirement_that_cites_it()
    {
        foreach ($this->nfrRows() as $row) {
            preg_match_all('/`KnownGapsNfrTest::(test_gap_\w+)`/', $row['evidence'], $gaps);

            foreach ($gaps[1] as $method) {
                $doc = (new \ReflectionMethod(KnownGapsNfrTest::class, $method))->getDocComment();
                $this->assertStringContainsString("@group {$row['id']}", (string) $doc, "{$row['id']} cites {$method}, which is not tagged with it");
            }
        }
    }

    public function test_the_known_gap_tests_are_excluded_from_the_default_run_but_still_exist()
    {
        $this->assertStringContainsString('nfr-gap', file_get_contents(base_path('phpunit.xml')));
        $this->assertStringContainsString('@group nfr-gap', file_get_contents(base_path('tests/Feature/Nfr/KnownGapsNfrTest.php')));
    }

    public function test_the_summary_counts_equal_the_rows()
    {
        $text = file_get_contents(base_path('docs/Non_Functional_Requirements.md'));
        $rows = $this->nfrRows();

        $byCategory = [];
        foreach ($rows as $row) {
            $category = explode('-', $row['id'])[1];
            $byCategory[$category][$row['result']] = ($byCategory[$category][$row['result']] ?? 0) + 1;
        }

        preg_match('/\| \*\*Total \((\d+)\)\*\* \| \*\*(\d+)\*\* \| \*\*(\d+)\*\* \| \*\*(\d+)\*\* \|/', $text, $total);
        $this->assertNotEmpty($total, 'the summary table has no Total row');

        $this->assertSame(count($rows), (int) $total[1]);
        foreach (['Pass' => 2, 'Fail' => 3, 'Not tested' => 4] as $result => $column) {
            $count = count(array_filter($rows, fn ($r) => $r['result'] === $result));
            $this->assertSame($count, (int) $total[$column], "summary total for '{$result}' does not match the rows");
        }
    }

    public function test_every_requirement_tag_in_the_nfr_tests_is_a_documented_requirement()
    {
        $documented = array_column($this->nfrRows(), 'id');
        $tagged = [];

        foreach (glob(base_path('tests/Feature/Nfr/*Test.php')) as $file) {
            preg_match_all('/@group (NFR-[A-Z]+-\d{2})/', file_get_contents($file), $m);
            $tagged = array_merge($tagged, $m[1]);
        }

        $this->assertGreaterThan(30, count(array_unique($tagged)), 'the NFR tests should tag the requirements they evidence');
        $this->assertSame([], array_values(array_diff(array_unique($tagged), $documented)), 'a test is tagged with a requirement that is not in the document');
    }

    public function test_the_functional_requirements_are_one_per_use_case_narrative()
    {
        $sheets = [];
        foreach (glob(base_path('docs/UCN/UCN_SC_E*.md')) as $file) {
            preg_match('/E\d{3}/', basename($file), $id);
            $sheets[$id[0]] = trim(preg_replace('/^# UCN_SC_E\d+ — /', '', fgets(fopen($file, 'r'))));
        }
        ksort($sheets);

        $text = file_get_contents(base_path('docs/Functional_Requirements.md'));
        preg_match_all('/^## FR-(E\d{3}) — (.+)$/m', $text, $m);
        $requirements = array_combine($m[1], array_map('trim', $m[2]));

        $this->assertSame(array_keys($sheets), array_keys($requirements), 'there must be exactly one functional requirement per UCN, with the same number');
        $this->assertSame($sheets, $requirements, 'each requirement carries its narrative\'s title');
        $this->assertCount(28, $requirements);
        $this->assertSame(array_map(fn ($i) => sprintf('E%03d', $i), range(1, 28)), array_keys($sheets), 'the UCN numbers are contiguous');
    }
}
