<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Yireo\NextGenImages\Plugin\AddImagesToConfigurableJsonConfig;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;

/**
 * @magentoAppArea frontend
 */
class AddImagesToConfigurableJsonConfigTest extends AbstractTestCase
{
    /**
     * @magentoAppArea frontend
     * @return void
     */
    public function testIfPluginIsEnabled()
    {
        $this->assertInterceptorPluginIsRegistered(
            Configurable::class,
            AddImagesToConfigurableJsonConfig::class,
            'Yireo_NextGenImages::addImagesToConfigurableJsonConfig'
        );
    }
}
