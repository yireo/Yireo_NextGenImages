<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Framework\Interception\PluginList\PluginList;
use Magento\Framework\View\Element\Template;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 */
class NoTemplatePluginTest extends TestCase
{
    public function testIfNoPluginIsRegisteredOnTemplate(): void
    {
        $pluginList = Bootstrap::getObjectManager()->get(PluginList::class);
        $pluginList->getNext(Template::class, 'toHtml');

        $pluginNames = array_keys((array)$pluginList->get(Template::class, []));
        $modulePluginNames = array_filter(
            $pluginNames,
            fn ($pluginName) => str_starts_with((string)$pluginName, 'Yireo_NextGenImages::')
        );

        $this->assertSame([], array_values($modulePluginNames), var_export($pluginNames, true));
    }
}
