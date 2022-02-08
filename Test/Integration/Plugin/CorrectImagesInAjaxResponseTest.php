<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Swatches\Helper\Data as SwatchesHelper;
use Yireo\NextGenImages\Plugin\CorrectImagesInAjaxResponse;
use Yireo\NextGenImages\Test\Integration\AbstractTestCase;

/**
 * @magentoAppArea frontend
 */
class CorrectImagesInAjaxResponseTest extends AbstractTestCase
{
    /**
     * @magentoAppArea frontend
     * @return void
     */
    public function testIfPluginIsEnabled()
    {
        //$this->assertDiFileIsLoaded('Yireo_NextGenImages', 'etc/frontend/di.xml');

        $this->assertDiPluginIsRegistered(
            SwatchesHelper::class,
            CorrectImagesInAjaxResponse::class,
            'Yireo_NextGenImages::correctImagesInAjaxResponse'
        );
    }
}
