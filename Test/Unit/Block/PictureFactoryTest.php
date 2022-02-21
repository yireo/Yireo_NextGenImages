<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Block;

use Magento\Framework\View\LayoutInterface;
use Yireo\NextGenImages\Block\Picture;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;

class PictureFactoryTest extends AbstractTestCase
{
    public function testCreate()
    {
        $pictureBlock = $this->getMagentoMock(Picture::class);
        $pictureBlock->method('getUrl')->willReturn('/images/test.png');

        $layout = $this->getMagentoMock(LayoutInterface::class);
        $layout->method('createBlock')->willReturn($pictureBlock);

        $config = $this->getMagentoMock(Config::class);
        $pictureFactory = new PictureFactory($layout, $config);
        $pictureBlock = $pictureFactory->create('/images/test.png', [], '<div></div>', true);
        $this->assertSame('/images/test.png', $pictureBlock->getUrl());
    }
}
