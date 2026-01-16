<?php
declare(strict_types=1);

namespace Triplewood\Toolbox\Model;

class AnalyzerService
{
    private const TOP_N = 20;

    /**
     * Parse profiler CSV and return analysis views.
     *
     * @param string $csvPath
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function analyze(string $csvPath): array
    {
        if (!is_readable($csvPath)) {
            throw new \RuntimeException('Profiler CSV not readable: ' . $csvPath);
        }

        $rows = $this->parseCsv($csvPath);

        return $this->extractAggregatedLeafTimes($rows);
    }

    /**
     * @param string $csvPath
     * @return array<int, array<string, mixed>>
     */
    private function parseCsv(string $csvPath): array
    {
        $rows = [];

        if (($handle = fopen($csvPath, 'r')) === false) {
            throw new \RuntimeException('Failed to open profiler CSV');
        }

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 6) {
                continue;
            }

            $rows[] = [
                'stack'      => $data[0],
                'total_time' => (float)$data[1],
                'avg_time'   => (float)$data[2],
                'calls'      => (int)$data[3],
                'emalloc'    => (int)str_replace(',', '', trim($data[4], '"')),
                'realmem'    => (int)$data[5],
            ];
        }

        fclose($handle);

        return $rows;
    }

    public function extractAggregatedLeafTimes(array $rows): array
    {
        $leafTimes = [];

        foreach ($rows as &$row) {
            $stack = $row['stack'];
            $stackParts = explode('->', $stack);
            $leafName = end($stackParts);
            if (!isset($leafTimes[$leafName])) {
                $leafTimes[$leafName] = [
                    'name' => $leafName,
                    'aggregated_execution_time' => 0,
                    'calls' => 0
                ];
            }
            $leafTimes[$leafName]['aggregated_execution_time'] += $row['total_time'];
            $leafTimes[$leafName]['calls'] += $row['calls'];
        }

        $values = array_values($leafTimes);

        usort($values, function ($a, $b) {
            return ($a['aggregated_execution_time'] < $b['aggregated_execution_time']) ? 1 : -1;
        });

        return $values;
    }
}
