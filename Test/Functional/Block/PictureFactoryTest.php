<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Functional\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Test\Functional\AbstractTestCase;

class PictureFactoryTest extends AbstractTestCase
{
    public function testCreate()
    {
        ObjectManager::getInstance()->get(State::class)->setAreaCode('frontend');
        $pictureFactory = ObjectManager::getInstance()->get(PictureFactory::class); // phpcs:ignore
        $image = new Image('/var/www/html/pub/foo/bar.png', '/foo/bar.png');
        $altImage = new Image('/var/www/html/pub/foo/bar.webp', '/foo/bar.webp');
        $picture = $pictureFactory->create($image, [$altImage], '<img src="/foo/bar.png"/>');
        $html = $picture->toHtml();

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('<source type="image/webp" srcset="/foo/bar.webp">', $html);
        $this->assertStringContainsString(' <img src="/foo/bar.png" loading="lazy" />', $html);
        $this->assertStringContainsString('</picture>', $html);
    }
}
