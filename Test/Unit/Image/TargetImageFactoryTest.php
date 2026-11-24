<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\DriverInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ->with($this->equalTo('/tmp/pub/media/custom/media/catalog/example.webp'))
            ->willReturnCallback(fn (string $path) => new Image($path, '/example.webp'));

        $driver = $this->getMagentoMock(DriverInterface::class);
        $driver->method('getParentDirectory')->willReturn('/tmp/pub/media/catalog');
        $writer = $this->getMagentoMock(WriteInterface::class);
        $writer->method('getDriver')->willReturn($driver);
        $filesystem = $this->getMagentoMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($writer);

        $targetImageFactory = new TargetImageFactory($directoryList, $config, $imageFactory, $filesystem);

        $webpImage = $targetImageFactory->create(new Image('/tmp/pub/media/catalog/example.jpg', '/example.jpg'), 'webp');
        $this->assertSame('/tmp/pub/media/custom/media/catalog/example.webp', $webpImage->getPath());
    }

    /**
     * @dataProvider provideCacheTargetPaths
     */
    #[DataProvider('provideCacheTargetPaths')]
    public function testGetWithCacheTargetDirectoryKeepsPubInFolderNames(string $parentDirectory, string $expectedPath)
    {
        $config = $this->getMagentoMock(Config::class);
        $config->method('getTargetDirectory')->willReturn(TargetDirectory::CACHE);
        $config->method('getCacheDirectoryPath')->willReturn('/tmp/pub/media/nextgenimages/');

        $directoryList = $this->getMagentoMock(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn('/tmp');

        $imageFactory = $this->getMagentoMock(ImageFactory::class);
        $imageFactory->expects($this->once())
            ->method('createFromPath')
            ->with($this->equalTo($expectedPath))
            ->willReturnCallback(fn (string $path) => new Image($path, '/example.webp'));

        $driver = $this->getMagentoMock(DriverInterface::class);
        $driver->method('getParentDirectory')->willReturn($parentDirectory);
        $writer = $this->getMagentoMock(WriteInterface::class);
        $writer->method('getDriver')->willReturn($driver);
        $filesystem = $this->getMagentoMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($writer);

        $targetImageFactory = new TargetImageFactory($directoryList, $config, $imageFactory, $filesystem);
        $webpImage = $targetImageFactory->create(new Image($parentDirectory.'/example.jpg', '/example.jpg'), 'webp');
        $this->assertSame($expectedPath, $webpImage->getPath());
    }

    public function testGetWithCacheTargetDirectoryNormalizesTargetPath()
    {
        $targetImageFactory = $this->getCacheTargetImageFactory('/tmp/pub/media/catalog/../product');
        $webpImage = $targetImageFactory->create(
            new Image('/tmp/pub/media/catalog/../product/example.jpg', '/example.jpg'),
            'webp'
        );

        $this->assertSame('/tmp/pub/media/nextgenimages/media/product/example.webp', $webpImage->getPath());
    }

    public function testGetWithCacheTargetDirectoryRejectsTraversalOutsideCacheDirectory()
    {
        $targetImageFactory = $this->getCacheTargetImageFactory('/tmp/pub/media/../../../../etc', false);

        $this->expectException(FileSystemException::class);
        $targetImageFactory->create(new Image('/tmp/pub/media/../../../../etc/example.jpg', '/example.jpg'), 'webp');
    }

    private function getCacheTargetImageFactory(
        string $parentDirectory,
        bool $expectCreate = true
    ): TargetImageFactory {
        $config = $this->getMagentoMock(Config::class);
        $config->method('getTargetDirectory')->willReturn(TargetDirectory::CACHE);
        $config->method('getCacheDirectoryPath')->willReturn('/tmp/pub/media/nextgenimages/');

        $directoryList = $this->getMagentoMock(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn('/tmp');

        $imageFactory = $this->getMagentoMock(ImageFactory::class);
        $imageFactory->expects($expectCreate ? $this->once() : $this->never())
            ->method('createFromPath')
            ->willReturnCallback(fn (string $path) => new Image($path, '/example.webp'));

        $driver = $this->getMagentoMock(DriverInterface::class);
        $driver->method('getParentDirectory')->willReturn($parentDirectory);
        $writer = $this->getMagentoMock(WriteInterface::class);
        $writer->method('getDriver')->willReturn($driver);
        $filesystem = $this->getMagentoMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($writer);

        return new TargetImageFactory($directoryList, $config, $imageFactory, $filesystem);
    }

    public static function provideCacheTargetPaths(): array
    {
        return [
            'publications folder' => [
                '/tmp/pub/media/publications',
                '/tmp/pub/media/nextgenimages/media/publications/example.webp',
            ],
            'pub inside folder name' => [
                '/tmp/pub/media/catalog/pub/x',
                '/tmp/pub/media/nextgenimages/media/catalog/pub/x/example.webp',
            ],
            'root name repeated in path' => [
                '/tmp/pub/media/tmp/catalog',
                '/tmp/pub/media/nextgenimages/media/tmp/catalog/example.webp',
            ],
            'root as prefix of other folder' => [
                '/tmpfoo/pub/media',
                '/tmp/pub/media/nextgenimages/tmpfoo/pub/media/example.webp',
            ],
        ];
    }
}
