<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Framework\View\LayoutInterface;
use Yireo\NextGenImages\Plugin\ReplaceTagsPlugin;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;

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
        $this->assertInterceptorPluginIsRegistered(
            LayoutInterface::class,
            ReplaceTagsPlugin::class,
            'Yireo_NextGenImages::replaceTags'
        );
    }
}
