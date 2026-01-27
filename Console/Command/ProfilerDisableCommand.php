<?php
declare(strict_types=1);

namespace Triplewood\Toolbox\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Triplewood\Toolbox\Model\ProfilerService;

class ProfilerDisableCommand extends Command
{
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
        $this->setName('triplewood:profiler:disable');
        $this->setDescription('This disabled the extended profiling capabilities of the Triplewood profiler.');
        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = 0;

        $output->writeln('<info>Disabling Triplewood profiler.</info>');

        if ($this->appState->getMode() === State::MODE_PRODUCTION) {
            $output->writeln(
                '<comment>' .
                'Your shop runs in PRODUCTION mode. This means you have to run "bin/magento static:content:deploy" ' .
                'to disable extended profiling in all classes.' .
                '</comment>'
            );
        }
        if ($this->appState->getMode() === State::MODE_DEVELOPER || $this->appState->getMode() === State::MODE_PRODUCTION) {
            $this->profilerService->clearGeneratedCode();
            $output->writeln(
                '<comment>' .
                'Your shop runs in DEVELOPER or DEFAULT mode. We automatically reset generated code to disable extended ' .
                'profiling in all classes.' .
                '</comment>'
            );
        }

        $this->profilerService->disable();
        $output->writeln('<info>Disabled Triplewood profiler.</info>');

        return $exitCode;
    }
}
