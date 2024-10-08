<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\DriverInterface;
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
            ->with($this->equalTo('/tmp/pub/example.webp'))
            ->willReturn(new Image('/tmp/pub/example.webp', '/example.webp'));

        $driver = $this->getMagentoMock(DriverInterface::class);
        $driver->method('getParentDirectory')->willReturn('/tmp');
        $writer = $this->getMagentoMock(WriteInterface::class);
        $writer->method('getDriver')->willReturn($driver);
        $filesystem = $this->getMagentoMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($writer);

        // @phpstan-ignore-next-line
        $targetImageFactory = new TargetImageFactory(
            $this->getMagentoMock(DirectoryList::class),
            $this->getMagentoMock(Config::class),
            $imageFactory,
            $filesystem
        );

        $jpgImage = new Image('/tmp/pub/example.jpg', '/example.jpg');
        $webpImage = $targetImageFactory->create($jpgImage, 'webp');
        $this->assertInstanceOf(Image::class, $webpImage);
        $this->assertEquals('/tmp/pub/example.webp', $webpImage->getPath());
    }

    public function testGetWithCacheTargetDirectory()
    {
        $config = $this->getMagentoMock(Config::class);
        $config->method('getTargetDirectory')->willReturn(TargetDirectory::CACHE);

        $directoryList = $this->getMagentoMock(DirectoryList::class);
        $directoryList->method('getPath')->with($this->equalTo(DirectoryList::MEDIA))->willReturn('/tmp/pub/media');
        $directoryList->method('getRoot')->willReturn('/tmp');

        $imageFactory = $this->getMagentoMock(ImageFactory::class);
        $imageFactory->method('createFromPath')->willReturn(new Image('/tmp/pub/example.webp', '/example.webp'));

        $driver = $this->getMagentoMock(DriverInterface::class);
        $driver->method('getParentDirectory')->willReturn('/tmp');
        $writer = $this->getMagentoMock(WriteInterface::class);
        $writer->method('getDriver')->willReturn($driver);
        $filesystem = $this->getMagentoMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($writer);

        // @phpstan-ignore-next-line
        $targetImageFactory = new TargetImageFactory(
            $directoryList,
            $config,
            $imageFactory,
            $filesystem
        );

        $jpgImage = new Image('/tmp/pub/example.jpg', '/example.jpg');
        $webpImage = $targetImageFactory->create($jpgImage, 'webp');
        $this->assertInstanceOf(Image::class, $webpImage);
        $this->assertEquals('/tmp/pub/example.webp', $webpImage->getPath());
    }
}
