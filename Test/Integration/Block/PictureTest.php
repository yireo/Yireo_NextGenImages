<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Block;

use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Util\UrlConvertor;

class PictureTest extends AbstractTestCase
{
    /**
     * @return void
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/lazy_loading 0
     */
    public function testPictureCreation()
    {
        $this->setAreaCodeToFrontend();

        $originalImage = new Image('/tmp/pub/images/test.png', '/images/test.png');
        $images = [new Image('/tmp/pub/images/test.webp', '/images/test.webp')];

        $pictureFactory = $this->objectManager->create(PictureFactory::class);
        $picture = $pictureFactory->create($originalImage, $images, '<img src="/images/test.png"/>');

        $html = $picture->toHtml();
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<source type="image/png" srcset="/images/test.png">', $html);
        $this->assertStringContainsString('<img src="/images/test.png"/>', $html);
    }

    /**
     * @return void
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/lazy_loading 1
     */
    public function testPictureCreationWithLazyLoading()
    {
        $this->setAreaCodeToFrontend();

        $originalImage = new Image('/tmp/pub/images/test.png', '/images/test.png');
        $images = [new Image('/tmp/pub/images/test.webp', '/images/test.webp')];

        $pictureFactory = $this->objectManager->create(PictureFactory::class);
        $picture = $pictureFactory->create($originalImage, $images, '<img src="/images/test.png"/>');

        $html = $picture->toHtml();
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<source type="image/png" srcset="/images/test.png">', $html);
        $this->assertStringContainsString('<img src="/images/test.png" loading="lazy" />', $html);
    }
}
