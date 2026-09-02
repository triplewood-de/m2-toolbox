# m2-toolbox

A useful collection of tools for debugging tricky Magento problems.

## Profiling

The Triplewood Toolbox comes with extended profiling capabilities that go beyond what
Magento's standard profiler offers. The extended profiler enriches data 
collected by the Magento profiler to help you paint a more detailed image of the 
performance situation.

Performance bottlenecks in Magento installations are often caused by inefficient custom code.
Much of this custom code lives in plugins that hook into standard Magento functions. While these 
plugins add functionality, they may inadvertently slow down the execution of core Magento 
processes, making it seem as though Magento itself is the issue.

To tackle the challenge to find slow plugins, the Triplewood Extended Profiler integrates with
Magento's plugin creation mechanism. It adds metrics for every plugin to the profiler data,
making it easier to spot bottlenecks hidden in plugins. 

### Enabling Extended Profiling

To enable the extended profiler use

        bin/magento triplewood:profiler:enable

***Note:*** This actually changes core code in the trait in \Magento\Framework\Interception\Interceptor.
When you re-build the vendor folder this may be overwritten.
Enabling this profiler will automatically enable the Magento standard profiler.

### Disabling Extended Profiling

To disable the extended profiler use

        bin/magento triplewood:profiler:disable

***Note:*** This will automatically disable the Magento standard profiler as well, and it will try to
revert the code changes in the Interceptor trait. This may fail if the inserted code has been changed
as the algorithm uses an exact string-matching algorithm.
