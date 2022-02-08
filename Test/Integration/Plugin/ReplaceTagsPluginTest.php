<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Framework\View\LayoutInterface;
use Yireo\NextGenImages\Plugin\ReplaceTagsPlugin;
use Yireo\NextGenImages\Test\Integration\AbstractTestCase;

class ReplaceTagsPluginTest extends AbstractTestCase
{
    /**
     * @magentoAppArea frontend
     * @return void
     */
    public function testIfPluginIsEnabled()
    {
        \Magento\TestFramework\Helper\Bootstrap::getInstance()->loadArea('frontend');

        $this->assertDiPluginIsRegistered(
            LayoutInterface::class,
            ReplaceTagsPlugin::class,
            'after'
        );
    }
}
