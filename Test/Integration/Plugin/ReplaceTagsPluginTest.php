<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Application;
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
        /** @var Application $application */
        $application = $this->objectManager->get(Application::class);
        $application->loadArea('frontend');
        \Magento\TestFramework\Helper\Bootstrap::getInstance()->loadArea('frontend');


        $this->assertDiPluginIsRegistered(
            LayoutInterface::class,
            ReplaceTagsPlugin::class,
            'after'
        );
    }
}
