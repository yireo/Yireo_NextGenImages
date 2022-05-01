<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Framework\View\LayoutInterface;
use Yireo\NextGenImages\Plugin\ReplaceTagsInHtml;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;

/**
 * @magentoAppArea frontend
 */
class ReplaceTagsInHtmlTest extends AbstractTestCase
{
    /**
     * @magentoAppArea frontend
     * @return void
     */
    public function testIfPluginIsEnabled()
    {
        $this->assertInterceptorPluginIsRegistered(
            LayoutInterface::class,
            ReplaceTagsInHtml::class,
            'Yireo_NextGenImages::replaceTagsInHtml'
        );
    }
}
