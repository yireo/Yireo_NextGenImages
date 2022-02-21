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

        $urlConvertor = $this->objectManager->get(UrlConvertor::class);
        //$images = [new Image($urlConvertor, '/tmp/images/test.webp')];
        $images = [];

        $pictureFactory = $this->objectManager->create(PictureFactory::class);
        $picture = $pictureFactory->create('/images/test.png', $images, '<img src="/images/test.png"/>');

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

        $urlConvertor = $this->objectManager->get(UrlConvertor::class);
        //$images = [new Image($urlConvertor, '/tmp/images/test.webp')];
        $images = [];

        $pictureFactory = $this->objectManager->create(PictureFactory::class);
        $picture = $pictureFactory->create('/images/test.png', $images, '<img src="/images/test.png"/>');

        $html = $picture->toHtml();
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<source type="image/png" srcset="/images/test.png">', $html);
        $this->assertStringContainsString('<img src="/images/test.png" loading="lazy" />', $html);
    }
}