<?php
declare(strict_types=1);

namespace Triplewood\Toolbox\Plugin;

use Magento\Framework\AppInterface;
use Triplewood\Toolbox\Driver\Accumulate;

class AppPlugin
{
    public function __construct(
        private readonly Accumulate $accumulate,
    )
    {
//        Profiler::add($accumulate);
//        $accumulate->start('magento');
    }

    public function aroundLaunch(AppInterface $subject, callable $proceed) {
        //$this->accumulate->init();
        return $proceed();
    }
}
