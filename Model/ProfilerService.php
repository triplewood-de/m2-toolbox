<?php

declare(strict_types=1);

namespace Triplewood\Toolbox\Model;

use Magento\Developer\Console\Command\ProfilerEnableCommand;
use Magento\Framework\App\State\CleanupFiles;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Profiler;

/**
 * Controls enabling and disabling of the extended Magento profiler.
 */
class ProfilerService
{
    public const MODE_SINGLE = 'single';
    public const MODE_ACCUMULATE= 'accumulate';

    private const BEFORE_PLUGIN_CALL = '$beforeResult = $pluginInstance->$pluginMethod($this, ...array_values($arguments));';
    private const AROUND_PLUGIN_CALL = '$result = $pluginInstance->$pluginMethod($subject, $next, ...array_values($arguments));';
    private const AFTER_PLUGIN_CALL = '$result = $pluginInstance->$pluginMethod($subject, $result, ...array_values($arguments));';
    private const ORIGINAL_FUNCTION_CALL = '$result = $subject->___callParent($method, $arguments);';

    private const TRIPLEWOOD_PROFILER_MARKER = '/** TW-PROFILER_MARKER **/';
    private const PROFILER_START_TEMPLATE = self::TRIPLEWOOD_PROFILER_MARKER . ' \Magento\Framework\Profiler::start(\'EP_\' . (isset($pluginInstance) ? $pluginInstance::class : self::class) . \'::\' . (isset($pluginMethod) ? $pluginMethod : $method));';
    private const PROFILER_END_TEMPLATE = '\Magento\Framework\Profiler::stop(\'EP_\' . (isset($pluginInstance) ? $pluginInstance::class : self::class). \'::\' . (isset($pluginMethod) ? $pluginMethod : $method));';

    private const PROFILER_MODE_FILE = 'var/profiler-mode.flag';

    public function __construct(
        private readonly File $fileWriter,
        private readonly CleanupFiles $cleanupFiles,
    ) {
    }

    /**
     * @return void
     */
    public function clearGeneratedCode(): void
    {
        $this->cleanupFiles->clearCodeGeneratedClasses();
    }

    /**
     * @return void
     */
    public function enable(): void
    {
        if (!Profiler::isEnabled()) {
            // enable the Magento profiler
            $this->fileWriter->write(BP . '/' . ProfilerEnableCommand::PROFILER_FLAG_FILE, 'csvfile');
            Profiler::enable();
        }
        $this->enableExtendedProfiler();
    }

    /**
     * @return void
     */
    public function disable(): void
    {
        if (Profiler::isEnabled()) {
            $this->fileWriter->rm(BP . '/' . ProfilerEnableCommand::PROFILER_FLAG_FILE);
            Profiler::disable();
        }
        $this->disableExtendedProfiler();
    }

    /**
     * @return bool
     */
    public function isExtendedProfilerEnabled(): bool
    {
        $traitFile = $this->getInterceptorTraitFile();
        $traitDefinition = file_get_contents($traitFile);

        return (str_contains($traitDefinition, self::TRIPLEWOOD_PROFILER_MARKER));
    }

    public function setMode(string $mode): bool
    {
        $result = $this->fileWriter->write(BP . '/' . self::PROFILER_MODE_FILE, $mode);
        return !($result === false);
    }

    public function getMode(): string
    {
        $result = $this->fileWriter->read(BP . '/' . self::PROFILER_MODE_FILE);
        if (empty($result)) {
            return self::MODE_SINGLE;
        }
        return $result;
    }

    /**
     * @return void
     */
    private function enableExtendedProfiler(): void
    {
        if ($this->isExtendedProfilerEnabled()) {
            return;
        }

        $traitFile = $this->getInterceptorTraitFile();
        $traitDefinition = $this->fileWriter->read($traitFile);

        $traitDefinition = $this->wrapPluginCall(self::BEFORE_PLUGIN_CALL, $traitDefinition);
        $traitDefinition = $this->wrapPluginCall(self::AROUND_PLUGIN_CALL, $traitDefinition);
        $traitDefinition = $this->wrapPluginCall(self::AFTER_PLUGIN_CALL, $traitDefinition);
        $traitDefinition = $this->wrapPluginCall(self::ORIGINAL_FUNCTION_CALL, $traitDefinition);

        $this->fileWriter->write($traitFile, $traitDefinition);
    }

    /**
     * @return void
     */
    private function disableExtendedProfiler(): void
    {
        if (!$this->isExtendedProfilerEnabled()) {
            return;
        }

        $traitFile = $this->getInterceptorTraitFile();
        $traitDefinition = $this->fileWriter->read($traitFile);

        $traitDefinition = $this->unwrapPluginCall(self::BEFORE_PLUGIN_CALL, $traitDefinition);
        $traitDefinition = $this->unwrapPluginCall(self::AROUND_PLUGIN_CALL, $traitDefinition);
        $traitDefinition = $this->unwrapPluginCall(self::AFTER_PLUGIN_CALL, $traitDefinition);

        $this->fileWriter->write($traitFile, $traitDefinition);
    }

    /**
     * @param string $pluginCall
     * @param string $traitDefinition
     * @return string
     */
    private function wrapPluginCall(string $pluginCall, string $traitDefinition): string
    {
        return str_replace(
            $pluginCall,
            self::PROFILER_START_TEMPLATE . $pluginCall . self::PROFILER_END_TEMPLATE,
            $traitDefinition
        );
    }

    /**
     * @param string $pluginCall
     * @param string $traitDefinition
     * @return string
     */
    private function unwrapPluginCall(string $pluginCall, string $traitDefinition): string
    {
        return str_replace(
            self::PROFILER_START_TEMPLATE . $pluginCall . self::PROFILER_END_TEMPLATE,
            $pluginCall,
            $traitDefinition
        );
    }

    /**
     * @return string
     */
    private function getInterceptorTraitFile(): string
    {
        $reflection = new \ReflectionClass(\Magento\Framework\Interception\Interceptor::class);
        return $reflection->getFileName();
    }
}
