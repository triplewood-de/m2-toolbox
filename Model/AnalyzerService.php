<?php
declare(strict_types=1);

namespace Triplewood\Toolbox\Model;

use RuntimeException;

class AnalyzerService
{
    /**
     * Parse profiler CSV and return analysis views.
     *
     * @param string $csvPath
     * @return array
     */
    public function analyze(string $csvPath): array
    {
        if (!is_readable($csvPath)) {
            throw new RuntimeException('Profiler CSV not readable: ' . $csvPath);
        }

        $rows = $this->parseCsv($csvPath);

        return $this->extractAggregatedLeafTimes($rows);
    }

    /**
     * @param string $csvPath
     * @return array
     */
    private function parseCsv(string $csvPath): array
    {
        $rows = [];
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            throw new RuntimeException('Failed to open profiler CSV');
        }

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 6) {
                continue;
            }

            $rows[] = [
                'stack' => $data[0],
                'total_time' => (float)$data[1],
                'avg_time' => (float)$data[2],
                'calls' => (int)$data[3],
                'emalloc' => (int)str_replace(',', '', trim($data[4], '"')),
                'realmem' => (int)$data[5],
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

        usort(
            $values,
            function ($val1, $val2) {
                return ($val1['aggregated_execution_time'] < $val2['aggregated_execution_time']) ? 1 : -1;
            }
        );

        return $values;
    }
}
