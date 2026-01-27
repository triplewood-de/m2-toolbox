<?php
declare(strict_types=1);

namespace Triplewood\Toolbox\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Triplewood\Toolbox\Model\AnalyzerService;

class ProfilerAnalyzePerformanceCommand extends Command
{
    /**
     * Black list of words that, if contained in a stack entry, should get filtered out to make the output
     * more readable. It
     */
    private const STACK_BLACKLIST = [
        'Triplewood\CacheVisualizer',
        'MSP\DevTools\Plugin\View\LayoutPlugin::aroundRenderElement',
        'Triplewood\Toolbox\Plugin\AppPlugin::aroundLaunch',
        'MSP\DevTools\Plugin\View\Element\AbstractBlockPlugin',
        'MSP\DevTools\Plugin\Event\ManagerInterfacePlugin',
        'routers_match',
        'LAYOUT',
        'layout_render'
    ];

    private const MAX_RESULTS = 50;

    private const DEFAULT_CSV_PATH = 'var/log/profiler.csv';

    public function __construct(
        private readonly AnalyzerService $analyzerService,
        private readonly State $appState
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('triplewood:profiler:analyze');
        $this->setDescription(
            'Analyses the latest profiler.csv and prints the most expensive leaf functions.'
        );

        $this->addOption(
            'path',
            null,
            InputOption::VALUE_OPTIONAL,
            'Path to the profiler file.'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info></info>');
        $output->writeln('<info>Triplewood Profiler Data Analyzer</info>');
        $output->writeln('<info>---------------------------------</info>');

        $this->appState->setAreaCode('adminhtml');

        try {
            $path = $input->getOption('path');
            if (!$path) {
                $path = self::DEFAULT_CSV_PATH;
            }
            $results = $this->analyzerService->analyze($path);
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        if (empty($results)) {
            $output->writeln('<comment>No profiler data found.</comment>');
            return Command::SUCCESS;
        }

        $output->writeln('<info>Here is a list of elements you should have a look at:</info>');

        $index = 0;
        foreach ($results as $row) {
            if ($index >= self::MAX_RESULTS) {
                break;
            }

            if ($this->isBlacklistedEntry($row['name'])) {
                continue;
            }

            $output->writeln(
                ($index + 1) . ".\t" .
                number_format($row['aggregated_execution_time'], 2) . 's'
                . "\t" . ' (' . $row['calls'] . ' calls)'
                . "\t" . $row['name']
            );
            $index++;
        }

        return Command::SUCCESS;
    }

    private function isBlacklistedEntry(string $line): bool
    {
        foreach (self::STACK_BLACKLIST as $blacklist) {
            if (stripos($line, $blacklist) !== false) {
                return true;
            }
        }
        return false;
    }
}
