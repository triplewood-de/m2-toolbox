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

### Performance Analysis

You can manually visit the file in var/log/profiler.csv in your favorite spreadsheet tool. Alternatively,
we added a simple automatic analysis. You can run it using


        bin/magento triplewood:profiler:analyze

This will print out 50 entries with the most expensive single actions recorded by the Magento profiler. 
Example:


        Triplewood Profiler Data Analyzer
        ---------------------------------
        Here is a list of elements you should have a look at:
        1.      1019ms  (100.00%)        (1 calls)      Magento Execution Time
        2.      156ms   ( 15.31%)        (4 calls)      EP_Smile\ElasticsuiteVirtualCategory\Model\Layer\Filter\Category\Interceptor::getItems
        3.      148ms   ( 14.52%)        (4 calls)      EAV:load_collection
        4.      134ms   ( 13.15%)        (1 calls)      EP_Smile\ElasticsuiteCatalog\Model\Layer\FilterList\Interceptor::getFilters
        5.      85ms    (  8.34%)        (94 calls)     EP_Hyva\Theme\Plugin\TemplateEngine\PhpPlugin::beforeRender
        6.      78ms    (  7.65%)        (5 calls)      ES:Execute Search Query
        7.      62ms    (  6.08%)        (12 calls)     EP_Custom\AdvancedStockIndicator\Plugin\AddExtensionAttributesPlugin::afterGetItemById
        8.      25ms    (  2.45%)        (2 calls)      generate_elements
        9.      19ms    (  1.86%)        (13 calls)     EP_Magento\Inventory\Model\SourceRepository\Interceptor::getList
        10.     18ms    (  1.77%)        (1 calls)      EP_Smile\ElasticsuiteCatalog\Model\Layer\Filter\Attribute\Interceptor::getItems
        11.     16ms    (  1.57%)        (2 calls)      EP_Hyva\ThemeFallback\Plugin\ThemeFallbackPlugin::beforeExecute
        12.     14ms    (  1.37%)        (106 calls)    cache_load
        13.     11ms    (  1.08%)        (1 calls)      EP_Magento\Framework\App\Http\Interceptor::launch
        14.     11ms    (  1.08%)        (12 calls)     EP_Custom\UnitConversion\Plugin\Product\ExtensionAttribute\ProductConversionConfigPlugin::afterGetItemById
        15.     10ms    (  0.98%)        (73 calls)     EP_Magento\Framework\View\Element\Template\Interceptor::toHtml
        16.     10ms    (  0.98%)        (2 calls)      OBSERVER:persistent_quote
        17.     9ms     (  0.88%)        (1 calls)      EP_Yireo\NextGenImages\Plugin\ReplaceTagsInHtml::afterGetOutput
        ...

This will not replace manual analysis of the CSV file but it will help to gain a first impression.