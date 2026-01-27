<?php
declare(strict_types=1);

namespace Triplewood\Toolbox\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Triplewood\Toolbox\Model\ProfilerService;

class ProfilerEnableCommand extends Command
{
    public function __construct(
        private readonly ProfilerService $profilerService,
        private readonly State $appState
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('triplewood:profiler:enable');
        $this->setDescription('This enables the extended profiling capabilities of the Triplewood profiler.');
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

        $output->writeln('<info>Enabling Triplewood profiler.</info>');

        $output->writeln('<comment>' .
            '| NOTE: Enabling the Triplewood profiler changes some files in the core that cannot be changed ' . PHP_EOL .
            '| through other means like overrides or plugins. Those changes can be reverted by using triplewood:profile:disable. ' . PHP_EOL .
            '| It is possible that they can be accidentally reverted by composer, too. Be careful! </comment>');

        if ($this->appState->getMode() === State::MODE_PRODUCTION) {
            $output->writeln(
                '<comment>' .
                'Your shop runs in PRODUCTION mode. This means you have to run "bin/magento static:content:deploy" ' .
                'to enable extended profiling in all classes.' .
                '</comment>'
            );
        }
        if ($this->appState->getMode() === State::MODE_DEVELOPER || $this->appState->getMode() === State::MODE_PRODUCTION) {
            $this->profilerService->clearGeneratedCode();
            $output->writeln(
                '<comment>' .
                'Your shop runs in DEVELOPER or DEFAULT mode. We automatically reset generated code to enable extended ' .
                'profiling in all classes.' .
                '</comment>'
            );
        }

        $this->profilerService->enable();
        $output->writeln('<info>Enabled Triplewood profiler. Please note, that the ' .
            'Triplewood Extended Profiler only works in csvfile-mode. Other modes are not supported.</info>');

        return $exitCode;
    }
}
