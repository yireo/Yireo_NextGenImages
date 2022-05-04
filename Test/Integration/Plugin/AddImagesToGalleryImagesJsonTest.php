<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Catalog\Block\Product\View\Gallery;
use Yireo\NextGenImages\Plugin\AddImagesToGalleryImagesJson;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;

/**
 * @magentoAppArea frontend
 */
class AddImagesToGalleryImagesJsonTest extends AbstractTestCase
{
    /**
     * @magentoAppArea frontend
     * @return void
     */
    public function testIfPluginIsEnabled()
    {
        $this->assertInterceptorPluginIsRegistered(
            Gallery::class,
            AddImagesToGalleryImagesJson::class,
            'Yireo_NextGenImages::addImagesToGalleryImagesJson'
        );
    }
}
