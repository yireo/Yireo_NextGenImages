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
            ->with($this->equalTo('/tmp/pub/example.jpg.webp'))
            ->willReturn(new Image('/tmp/pub/example.jpg.webp', '/example.jpg.webp'));

        $driver = $this->getMagentoMock(DriverInterface::class);
        $driver->method('getParentDirectory')->willReturn('/tmp');
        $writer = $this->getMagentoMock(WriteInterface::class);
        $writer->method('getDriver')->willReturn($driver);
        $filesystem = $this->getMagentoMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($writer);

        $targetImageFactory = new TargetImageFactory(
            $this->getMagentoMock(DirectoryList::class),
            $this->getMagentoMock(Config::class),
            $imageFactory,
            $filesystem
        );

        $jpgImage = new Image('/tmp/pub/example.jpg', '/example.jpg');
        $webpImage = $targetImageFactory->create($jpgImage, 'webp');
        $this->assertInstanceOf(Image::class, $webpImage);
        $this->assertEquals('/tmp/pub/example.jpg.webp', $webpImage->getPath());
    }

    public function testGetWithCacheTargetDirectory()
    {
        $config = $this->getMagentoMock(Config::class);
        $config->method('getTargetDirectory')->willReturn(TargetDirectory::CACHE);

        $directoryList = $this->getMagentoMock(DirectoryList::class);
        $directoryList->method('getPath')->with($this->equalTo(DirectoryList::MEDIA))->willReturn('/tmp/pub/media');
        $directoryList->method('getRoot')->willReturn('/tmp');

        $imageFactory = $this->getMagentoMock(ImageFactory::class);
        $imageFactory->method('createFromPath')->willReturn(new Image('/tmp/pub/example.jpg.webp', '/example.jpg.webp'));

        $driver = $this->getMagentoMock(DriverInterface::class);
        $driver->method('getParentDirectory')->willReturn('/tmp');
        $writer = $this->getMagentoMock(WriteInterface::class);
        $writer->method('getDriver')->willReturn($driver);
        $filesystem = $this->getMagentoMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($writer);

        $targetImageFactory = new TargetImageFactory(
            $directoryList,
            $config,
            $imageFactory,
            $filesystem
        );

        $jpgImage = new Image('/tmp/pub/example.jpg', '/example.jpg');
        $webpImage = $targetImageFactory->create($jpgImage, 'webp');
        $this->assertInstanceOf(Image::class, $webpImage);
        $this->assertEquals('/tmp/pub/example.jpg.webp', $webpImage->getPath());
    }

    public function testGetWithCustomCacheTargetDirectory()
    {
        $config = $this->getMagentoMock(Config::class);
        $config->method('getTargetDirectory')->willReturn(TargetDirectory::CACHE);
        $config->method('getCacheDirectoryPath')->willReturn('/tmp/pub/media/custom/');

        $directoryList = $this->getMagentoMock(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn('/tmp');

        $imageFactory = $this->getMagentoMock(ImageFactory::class);
        $imageFactory->expects($this->once())
            ->method('createFromPath')
            ->with($this->equalTo('/tmp/pub/media/custom/media/catalog/example.jpg.webp'))
            ->willReturnCallback(fn (string $path) => new Image($path, '/example.webp'));

        $driver = $this->getMagentoMock(DriverInterface::class);
        $driver->method('getParentDirectory')->willReturn('/tmp/pub/media/catalog');
        $writer = $this->getMagentoMock(WriteInterface::class);
        $writer->method('getDriver')->willReturn($driver);
        $filesystem = $this->getMagentoMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($writer);

        $targetImageFactory = new TargetImageFactory($directoryList, $config, $imageFactory, $filesystem);

        $webpImage = $targetImageFactory->create(new Image('/tmp/pub/media/catalog/example.jpg', '/example.jpg'), 'webp');
        $this->assertSame('/tmp/pub/media/custom/media/catalog/example.jpg.webp', $webpImage->getPath());
    }

    public function testSourcesWithSameBasenameGetDistinctTargetsWithoutHash()
    {
        $config = $this->getMagentoMock(Config::class);
        $config->method('addHash')->willReturn(false);

        $targetImageFactory = $this->createTargetImageFactory($config);

        $jpgTarget = $targetImageFactory->create(new Image('/tmp/pub/example.jpg', '/example.jpg'), 'webp');
        $pngTarget = $targetImageFactory->create(new Image('/tmp/pub/example.png', '/example.png'), 'webp');

        $this->assertSame('/tmp/pub/example.jpg.webp', $jpgTarget->getPath());
        $this->assertSame('/tmp/pub/example.png.webp', $pngTarget->getPath());
        $this->assertNotSame($jpgTarget->getPath(), $pngTarget->getPath());
    }

    public function testSourcesWithSameBasenameGetDistinctTargetsWithHash()
    {
        $config = $this->getMagentoMock(Config::class);
        $config->method('addHash')->willReturn(true);

        $targetImageFactory = $this->createTargetImageFactory($config);

        $jpgTarget = $targetImageFactory->create(new Image('/tmp/pub/example.jpg', '/example.jpg'), 'webp');
        $pngTarget = $targetImageFactory->create(new Image('/tmp/pub/example.png', '/example.png'), 'webp');

        $this->assertSame('/tmp/pub/example-'.hash('crc32', '/tmp/pub/example.jpg').'.webp', $jpgTarget->getPath());
        $this->assertSame('/tmp/pub/example-'.hash('crc32', '/tmp/pub/example.png').'.webp', $pngTarget->getPath());
        $this->assertNotSame($jpgTarget->getPath(), $pngTarget->getPath());
    }

    private function createTargetImageFactory(Config $config): TargetImageFactory
    {
        $imageFactory = $this->getMagentoMock(ImageFactory::class);
        $imageFactory->method('createFromPath')
            ->willReturnCallback(fn (string $path) => new Image($path, '/'.basename($path)));

        $driver = $this->getMagentoMock(DriverInterface::class);
        $driver->method('getParentDirectory')->willReturn('/tmp/pub');
        $writer = $this->getMagentoMock(WriteInterface::class);
        $writer->method('getDriver')->willReturn($driver);
        $filesystem = $this->getMagentoMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($writer);

        return new TargetImageFactory($this->getMagentoMock(DirectoryList::class), $config, $imageFactory, $filesystem);
    }
}
