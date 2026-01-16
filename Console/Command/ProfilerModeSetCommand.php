<?php
declare(strict_types=1);

namespace Triplewood\Toolbox\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Triplewood\Toolbox\Model\ProfilerService;

class ProfilerModeSetCommand extends Command
{
    public const MODE = 'mode';

    /**
     * @param ProfilerService $profilerService
     * @param State $appState
     */
    public function __construct(
        private readonly ProfilerService $profilerService,
        private readonly State $appState
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('triplewood:profiler:mode-set');
        $this->setDescription('Set profiler mode of operation. Mode "single" means, the profiler.csv file '
            . 'only contains data from the very last magento execution. Mode "accumulate" means, data is collected in '
            . 'profiler.csv over several calls until file is deleted.'
        );
        $this->addArgument(
            self::MODE,
            null,
            'Mode (accumulate, single)'
        );

        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = 0;
        $mode = $input->getArgument(self::MODE);

        $output->writeln('<info>Setting Triplewood profiler mode.</info>');

        if (empty($mode)) {
            $output->writeln('<error>Please provide a mode parameter.</error> ');
            $output->writeln('<info>Examples:</info>');
            $output->writeln('<info>    bin/magento triplewood:profiler:mode-set single</info>');
            $output->writeln('<info>    bin/magento triplewood:profiler:mode-set accumulate</info>');
            return 1;
        }

        $isSuccessful = $this->profilerService->setMode($mode);
        if (!$isSuccessful) {
            $output->writeln('<error>Failed to set profiler mode. Is profiler enabled?</error>');
            return 2;
        }

        $output->writeln('<info>Profiler mode set to ' . $mode . '</info>');

        return $exitCode;
    }
}
