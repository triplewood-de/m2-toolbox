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
        $filtered = $this->extractAggregatedLeafTimes($rows);

        $magento = [];
        foreach ($rows as $entry) {
            if ($entry['stack'] === 'magento') {
                $magento = [
                    'name' => 'Magento Execution Time',
                    'aggregated_execution_time' => $entry['total_time'],
                    'calls' => (int) $entry['calls'],
                ];
                break;
            }
        }

        array_unshift($filtered, $magento);
        return $filtered;
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

    /**
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function extractAggregatedLeafTimes(array $rows): array
    {
        // Index all rows by their stack path for quick lookup
        $stackIndex = [];
        foreach ($rows as $row) {
            $stackIndex[$row['stack']] = $row;
        }

        $nodeTimes = [];

        foreach ($rows as $row) {
            $stack = $row['stack'];
            $parts = explode('->', $stack);
            $nodeName = end($parts);

            // Find direct children: rows whose stack is this stack + '->' + one more segment
            $childrenTime = 0;
            $prefix = $stack . '->';
            foreach ($rows as $candidate) {
                $candidateStack = $candidate['stack'];
                // Must start with our stack + '->'
                if (strncmp($candidateStack, $prefix, strlen($prefix)) !== 0) {
                    continue;
                }
                // Must be exactly one level deeper (no further '->' after the prefix)
                $remainder = substr($candidateStack, strlen($prefix));
                if (strpos($remainder, '->') === false) {
                    $childrenTime += $candidate['total_time'];
                }
            }

            $ownTime = $row['total_time'] - $childrenTime;

            // Skip negligible own-time entries (noise from pure delegation)
            if ($ownTime <= 0.0) {
                continue;
            }

            if (!isset($nodeTimes[$nodeName])) {
                $nodeTimes[$nodeName] = [
                    'name'                      => $nodeName,
                    'aggregated_execution_time' => 0.0,
                    'calls'                     => 0,
                ];
            }

            $nodeTimes[$nodeName]['aggregated_execution_time'] += $ownTime;
            $nodeTimes[$nodeName]['calls']                     += $row['calls'];
        }

        $values = array_values($nodeTimes);

        usort($values, static function (array $a, array $b): int {
            return $b['aggregated_execution_time'] <=> $a['aggregated_execution_time'];
        });

        return $values;
    }
}
