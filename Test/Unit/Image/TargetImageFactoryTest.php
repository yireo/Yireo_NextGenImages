<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Config\Source\TargetDirectory;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;

class TargetImageFactoryTest extends AbstractTestCase
{
    public function testGetWithSameTargetDirectory()
    {
        $imageFactory = $this->getMagentoMock(ImageFactory::class);
        $imageFactory->method('createFromPath')
            ->with($this->equalTo('foo/bar/example.webp'))
            ->willReturn(new Image($this->getUrlConvertorMock(), 'foo/bar/example.webp'));

        $targetImageFactory = new TargetImageFactory(
            $this->getMagentoMock(DirectoryList::class),
            $this->getMagentoMock(Config::class),
            $imageFactory
        );

        $jpgImage = new Image($this->getUrlConvertorMock(), 'foo/bar/example.jpg');
        $webpImage = $targetImageFactory->create($jpgImage, 'webp');
        $this->assertEquals('foo/bar/example.webp', $webpImage->getPath());
    }

    public function testGetWithCacheTargetDirectory()
    {
        $config = $this->getMagentoMock(Config::class);
        $config->method('getTargetDirectory')->willReturn(TargetDirectory::CACHE);

        $directoryList = $this->getMagentoMock(DirectoryList::class);
        $directoryList->method('getPath')->with($this->equalTo(DirectoryList::MEDIA))->willReturn('/tmp/pub/media');

        $imageFactory = $this->getMagentoMock(ImageFactory::class);
        $imageFactory->method('createFromPath')
            ->with($this->equalTo('/tmp/pub/media/nextgenimages/example.webp'))
            ->willReturn(new Image($this->getUrlConvertorMock(), '/tmp/pub/media/nextgenimages/example.webp'));

        $targetImageFactory = new TargetImageFactory(
            $directoryList,
            $config,
            $imageFactory
        );

        $jpgImage = new Image($this->getUrlConvertorMock(), 'foo/bar/example.jpg');
        $webpImage = $targetImageFactory->create($jpgImage, 'webp');
        $this->assertEquals('/tmp/pub/media/nextgenimages/example.webp', $webpImage->getPath());
    }
}
