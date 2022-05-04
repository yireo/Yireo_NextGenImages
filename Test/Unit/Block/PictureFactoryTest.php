<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Block;

use Magento\Framework\View\LayoutInterface;
use Yireo\NextGenImages\Block\Picture;
use Yireo\NextGenImages\Block\PictureFactory;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Image\Image;
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

        $image = $this->getMagentoMock(Image::class);
        $image->method('getUrl')->willReturn('/images/test.png');

        $pictureBlock = $pictureFactory->create($image, [], '<div></div>', true);
        $this->assertSame('/images/test.png', $pictureBlock->getUrl());
    }
}
