<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Bundle\Model\Plugin\Frontend\ProductIdentitiesExtender;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Interception\PluginList;
use Yireo\NextGenImages\Plugin\ReplaceTagsPlugin;
use Yireo\NextGenImages\Test\Integration\AbstractTestCase;

/**
 * @magentoAppArea frontend
 */
class ReplaceTagsPluginTest extends AbstractTestCase
{
    /**
     * @magentoAppArea frontend
     * @return void
     */
    public function testIfPluginIsEnabled()
    {
        $this->assertDiPluginIsRegistered(
            LayoutInterface::class,
            ReplaceTagsPlugin::class,
            'Yireo_NextGenImages::replaceTags'
        );
    }
}
