<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Live\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Test\Live\AbstractTestCase;

class PictureFactoryTest extends AbstractTestCase
{
    public function testCreate()
    {
        ObjectManager::getInstance()->get(State::class)->setAreaCode('frontend');
        $pictureFactory = ObjectManager::getInstance()->get(PictureFactory::class); // phpcs:ignore
        $picture = $pictureFactory->create('/foo/bar.png', [], '<img src="/foo/bar.png"/>');
        $html = $picture->toHtml();

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('<source type="image/png" srcset="/foo/bar.png">', $html);
        $this->assertStringContainsString(' <img src="/foo/bar.png" loading="lazy" />', $html);
        $this->assertStringContainsString('</picture>', $html);
    }
}
