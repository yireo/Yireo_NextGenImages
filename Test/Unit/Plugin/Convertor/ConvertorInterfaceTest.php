<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Plugin\Convertor;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Yireo\NextGenImages\Convertor\ConvertorInterface;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Logger\Debugger;
use Yireo\NextGenImages\Plugin\Convertor\ConvertorInterface as ConvertorInterfacePlugin;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;
use Yireo\NextGenImages\Util\File;
use Yireo\NextGenImages\Util\TmpFileCopier;
use Yireo\NextGenImages\Util\UrlConvertor;
use Yireo\NextGenImages\Image\ImageFactory;

class ConvertorInterfaceTest extends AbstractTestCase
{
    private const TMP_ROOT = '/var/www/html/var/tmp';
    private const REMOTE_ROOT = '/remote';
    private const SOURCE_RELATIVE = 'media/catalog/product/a/b/test.jpg';
    private const TARGET_RELATIVE = 'media/catalog/product/a/b/test.webp';
    private const SRCSET_SOURCE_RELATIVE = 'media/catalog/product/a/b/test-2x.jpg';
    private const SRCSET_TARGET_RELATIVE = 'media/catalog/product/a/b/test-2x.webp';
    private const BASE_URL = 'http://localhost/';

    /** @var RemoteStorageConfig&MockObject */
    private $remoteStorageConfig;

    /** @var TargetImageFactory&MockObject */
    private $targetImageFactory;

    /** @var File&MockObject */
    private $fileUtil;

    /** @var TmpFileCopier&MockObject */
    private $tmpFileCopier;

    /** @var UrlConvertor&MockObject */
    private $urlConvertor;

    /** @var ImageFactory&MockObject */
    private $imageFactory;

    /** @var Debugger&MockObject */
    private $debugger;

    /** @var DriverInterface&MockObject */
    private $tmpDriver;

    /** @var DriverInterface&MockObject */
    private $remoteDriver;

    protected function setUp(): void
    {
        $this->remoteStorageConfig = $this->createMock(RemoteStorageConfig::class);
        $this->fileUtil = $this->createMock(File::class);
        $this->tmpFileCopier = $this->createMock(TmpFileCopier::class);
        $this->urlConvertor = $this->createMock(UrlConvertor::class);
        $this->imageFactory = $this->createMock(ImageFactory::class);
        $this->debugger = $this->createMock(Debugger::class);
        $this->tmpDriver = $this->createMock(DriverInterface::class);
        $this->remoteDriver = $this->createMock(DriverInterface::class);

        $this->targetImageFactory = $this->createMock(TargetImageFactory::class);
        $this->targetImageFactory->method('create')->willReturnCallback(
            fn (Image $image, string $suffix) => new Image(
                preg_replace('/\.(jpg|jpeg|png)$/', '.'.$suffix, $image->getPath()),
                preg_replace('/\.(jpg|jpeg|png)$/', '.'.$suffix, $image->getUrl())
            )
        );

        $this->urlConvertor->method('getFilenameFromUrl')->willReturnCallback(
            fn (string $url) => self::TMP_ROOT.'/'.str_replace(self::BASE_URL, '', $url)
        );
        $this->imageFactory->method('createFromPath')->willReturnCallback(
            fn (string $path) => new Image($path, self::BASE_URL.str_replace(self::TMP_ROOT.'/', '', $path))
        );
    }

    public function testProceedsWhenRemoteStorageIsDisabled(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(false);
        $this->fileUtil->expects($this->never())->method('needsConversion');
        $this->tmpFileCopier->expects($this->never())->method('copy');
        $this->tmpDriver->expects($this->never())->method('rename');

        $sourceImage = $this->getSourceImage();
        $resultImage = $this->getTargetImage();
        $proceed = function (Image $image) use ($sourceImage, $resultImage) {
            $this->assertSame($sourceImage, $image);
            return $resultImage;
        };

        $plugin = $this->createPlugin();
        $this->assertSame($resultImage, $plugin->aroundConvertImage($this->getSubject(), $proceed, $sourceImage));
    }

    public function testSkipsConversionWhenTargetIsUpToDate(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(true);
        $this->fileUtil->expects($this->once())
            ->method('needsConversion')
            ->with(self::TMP_ROOT.'/'.self::SOURCE_RELATIVE, self::TMP_ROOT.'/'.self::TARGET_RELATIVE)
            ->willReturn(false);
        $this->tmpFileCopier->expects($this->never())->method('copy');
        $this->tmpDriver->expects($this->never())->method('rename');

        $proceed = function () {
            $this->fail('Convertor should not be called when the target image is up to date');
        };

        $plugin = $this->createPlugin();
        $result = $plugin->aroundConvertImage($this->getWebpSubject(), $proceed, $this->getSourceImage());
        $this->assertSame(self::TMP_ROOT.'/'.self::TARGET_RELATIVE, $result->getPath());
    }

    public function testCopiesSourceToTmpConvertsAndMovesResultToRemoteStorage(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(true);
        $this->fileUtil->method('needsConversion')->willReturn(true);

        $calls = [];
        $this->tmpFileCopier->expects($this->once())
            ->method('copy')
            ->with(self::SOURCE_RELATIVE)
            ->willReturnCallback(function (string $path) use (&$calls) {
                $calls[] = 'copy';
                return self::TMP_ROOT.'/'.$path;
            });

        $this->tmpDriver->method('isExists')
            ->with(self::TMP_ROOT.'/'.self::TARGET_RELATIVE)
            ->willReturn(true);
        $this->tmpDriver->expects($this->once())
            ->method('rename')
            ->with(
                self::TMP_ROOT.'/'.self::TARGET_RELATIVE,
                self::REMOTE_ROOT.'/'.self::TARGET_RELATIVE,
                $this->remoteDriver
            )
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'move';
                return true;
            });

        $resultImage = $this->getTargetImage();
        $proceed = function () use ($resultImage, &$calls) {
            $calls[] = 'convert';
            return $resultImage;
        };

        $plugin = $this->createPlugin();
        $this->assertSame($resultImage, $plugin->aroundConvertImage($this->getWebpSubject(), $proceed, $this->getSourceImage()));
        $this->assertSame(['copy', 'convert', 'move'], $calls);
    }

    public function testDoesNotMoveAnythingWhenConvertorDidNotCreateFile(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('needsConversion')->willReturn(true);
        $this->tmpDriver->method('isExists')->willReturn(false);
        $this->tmpDriver->expects($this->never())->method('rename');

        $resultImage = $this->getTargetImage();
        $proceed = fn () => $resultImage;

        $plugin = $this->createPlugin();
        $this->assertSame($resultImage, $plugin->aroundConvertImage($this->getSubject(), $proceed, $this->getSourceImage()));
    }

    /**
     * Without remote storage, a convertor (like Yireo_Webp2) throws a ConvertorException when the source is missing,
     * which makes the frontend fall back to the original image. The plugin should not hide that by returning a
     * target image that does not exist: the browser would then pick a broken <source>.
     */
    public function testDoesNotReturnNonExistingTargetWhenSourceIsMissing(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(false);
        $this->fileUtil->method('needsConversion')->willReturn(false); // Because the source does not exist

        $proceedCalled = false;
        $proceed = function (Image $image) use (&$proceedCalled) {
            $proceedCalled = true;
            return $image;
        };

        $plugin = $this->createPlugin();
        $plugin->aroundConvertImage($this->getWebpSubject(), $proceed, $this->getSourceImage());
        $this->assertTrue($proceedCalled, 'Convertor should decide what happens to a missing source image');
    }

    /**
     * The plugin is registered on ConvertorInterface, so it also applies to non-WebP convertors (like Yireo_Avif).
     * A convertor producing AVIF images should never get a WebP image back.
     */
    public function testRespectsFormatOfNonWebpConvertor(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(true);
        $this->fileUtil->method('needsConversion')->willReturnCallback(
            // Only the WebP image is up to date, the AVIF image does not exist yet
            fn (string $source, string $target) => !str_ends_with($target, '.webp')
        );

        $sourceImage = $this->getSourceImage();
        $avifImage = new Image(self::TMP_ROOT.'/media/catalog/product/a/b/test.avif', '/media/test.avif');
        $proceed = fn () => $avifImage;

        $plugin = $this->createPlugin();
        $result = $plugin->aroundConvertImage($this->getAvifSubject(), $proceed, $sourceImage);
        $this->assertStringEndsWith('.avif', $result->getPath());
    }

    public function testUsesFormatOfConvertor(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(true);
        $this->fileUtil->expects($this->once())
            ->method('needsConversion')
            ->with(self::TMP_ROOT.'/'.self::SOURCE_RELATIVE, self::TMP_ROOT.'/media/catalog/product/a/b/test.avif')
            ->willReturn(false);

        $proceed = function () {
            $this->fail('Convertor should not be called when the target image is up to date');
        };

        $plugin = $this->createPlugin();
        $result = $plugin->aroundConvertImage($this->getFormatAwareSubject('avif'), $proceed, $this->getSourceImage());
        $this->assertStringEndsWith('.avif', $result->getPath());
    }

    public function testUsesFormatFromDiConfiguration(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(true);
        $this->fileUtil->expects($this->once())
            ->method('needsConversion')
            ->with(self::TMP_ROOT.'/'.self::SOURCE_RELATIVE, self::TMP_ROOT.'/media/catalog/product/a/b/test.avif')
            ->willReturn(false);

        $subject = $this->getAvifSubject();
        $proceed = function () {
            $this->fail('Convertor should not be called when the target image is up to date');
        };

        $plugin = $this->createPlugin([get_class($subject) => 'avif']);
        $result = $plugin->aroundConvertImage($subject, $proceed, $this->getSourceImage());
        $this->assertStringEndsWith('.avif', $result->getPath());
    }

    public function testAlwaysConvertsWhenFormatIsUnknown(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(true);
        $this->fileUtil->expects($this->never())->method('needsConversion');
        $this->tmpFileCopier->expects($this->once())->method('copy')->with(self::SOURCE_RELATIVE);

        $resultImage = $this->getTargetImage();
        $plugin = $this->createPlugin();
        $result = $plugin->aroundConvertImage($this->getSubject(), fn () => $resultImage, $this->getSourceImage());
        $this->assertSame($resultImage, $result);
    }

    public function testRunsConvertorWithoutCopyingWhenEverythingIsUpToDate(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(true);
        $this->fileUtil->expects($this->exactly(2))->method('needsConversion')->willReturn(false);
        $this->tmpFileCopier->expects($this->never())->method('copy');
        $this->tmpDriver->expects($this->never())->method('rename');

        $resultImage = $this->getTargetImageWithSrcSet();
        $proceedCalls = 0;
        $proceed = function () use ($resultImage, &$proceedCalls) {
            $proceedCalls++;
            return $resultImage;
        };

        $plugin = $this->createPlugin();
        $result = $plugin->aroundConvertImage($this->getWebpSubject(), $proceed, $this->getSourceImageWithSrcSet());
        $this->assertSame($resultImage, $result);
        $this->assertSame(1, $proceedCalls, 'Convertor should add the converted srcset');
    }

    public function testOnlyCopiesOutdatedSrcSetImages(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(true);
        $this->fileUtil->method('needsConversion')->willReturnCallback(
            fn (string $source) => $source === self::TMP_ROOT.'/'.self::SRCSET_SOURCE_RELATIVE
        );
        $this->tmpFileCopier->expects($this->once())->method('copy')->with(self::SRCSET_SOURCE_RELATIVE);

        $resultImage = $this->getTargetImageWithSrcSet();
        $plugin = $this->createPlugin();
        $result = $plugin->aroundConvertImage(
            $this->getWebpSubject(),
            fn () => $resultImage,
            $this->getSourceImageWithSrcSet()
        );
        $this->assertSame($resultImage, $result);
    }

    public function testDoesNotCheckMainImageAgainWhenItIsPartOfSrcSet(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(true);
        $this->fileUtil->expects($this->exactly(2))->method('needsConversion')->willReturn(false);

        $sourceImage = new Image(
            self::TMP_ROOT.'/'.self::SOURCE_RELATIVE,
            self::BASE_URL.self::SOURCE_RELATIVE,
            self::BASE_URL.self::SOURCE_RELATIVE.','.self::BASE_URL.self::SRCSET_SOURCE_RELATIVE.' 2x'
        );

        $plugin = $this->createPlugin();
        $plugin->aroundConvertImage($this->getWebpSubject(), fn () => $this->getTargetImage(), $sourceImage);
    }

    public function testMovesConvertedSrcSetImagesToRemoteStorage(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(true);
        $this->fileUtil->method('needsConversion')->willReturn(true);
        $this->tmpFileCopier->expects($this->exactly(2))->method('copy');
        $this->tmpDriver->method('isExists')->willReturn(true);

        $movedFiles = [];
        $this->tmpDriver->expects($this->exactly(2))
            ->method('rename')
            ->willReturnCallback(function (string $source, string $destination) use (&$movedFiles) {
                $movedFiles[$source] = $destination;
                return true;
            });

        $plugin = $this->createPlugin();
        $plugin->aroundConvertImage(
            $this->getWebpSubject(),
            fn () => $this->getTargetImageWithSrcSet(),
            $this->getSourceImageWithSrcSet()
        );

        $this->assertSame([
            self::TMP_ROOT.'/'.self::TARGET_RELATIVE => self::REMOTE_ROOT.'/'.self::TARGET_RELATIVE,
            self::TMP_ROOT.'/'.self::SRCSET_TARGET_RELATIVE => self::REMOTE_ROOT.'/'.self::SRCSET_TARGET_RELATIVE,
        ], $movedFiles);
    }

    public function testNeverMovesSrcSetImagesOfOtherFormats(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(true);
        $this->fileUtil->method('needsConversion')->willReturn(true);
        $this->tmpDriver->method('isExists')->willReturn(true);
        $this->tmpDriver->expects($this->once())
            ->method('rename')
            ->with(self::TMP_ROOT.'/'.self::TARGET_RELATIVE, self::REMOTE_ROOT.'/'.self::TARGET_RELATIVE);

        $sourceImage = $this->getSourceImageWithSrcSet();
        $resultImage = $this->getTargetImage();
        $resultImage->setSrcSet($sourceImage->getSrcSet());

        $plugin = $this->createPlugin();
        $plugin->aroundConvertImage($this->getWebpSubject(), fn () => $resultImage, $sourceImage);
    }

    public function testMatchesTargetFormatCaseInsensitive(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(true);
        $this->fileUtil->method('needsConversion')->willReturn(true);
        $this->tmpDriver->method('isExists')->willReturn(true);
        $this->tmpDriver->expects($this->once())
            ->method('rename')
            ->with(self::TMP_ROOT.'/media/catalog/product/a/b/test.WEBP');

        $resultImage = new Image(self::TMP_ROOT.'/media/catalog/product/a/b/test.WEBP', '/media/test.WEBP');

        $plugin = $this->createPlugin();
        $plugin->aroundConvertImage($this->getWebpSubject(), fn () => $resultImage, $this->getSourceImage());
    }

    public function testHandsMissingSourceWithSrcSetToConvertor(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->method('fileExists')->willReturn(false);
        $this->fileUtil->expects($this->never())->method('needsConversion');
        $this->tmpFileCopier->expects($this->never())->method('copy');
        $this->tmpDriver->expects($this->never())->method('rename');

        $proceed = function () {
            throw new ConvertorException('Source image does not exist');
        };

        $this->expectException(ConvertorException::class);
        $plugin = $this->createPlugin();
        $plugin->aroundConvertImage($this->getWebpSubject(), $proceed, $this->getSourceImageWithSrcSet());
    }

    public function testOnlyMovesMainImageWhenFormatIsUnknown(): void
    {
        $this->remoteStorageConfig->method('isEnabled')->willReturn(true);
        $this->fileUtil->expects($this->never())->method('needsConversion');
        $this->tmpFileCopier->expects($this->once())->method('copy')->with(self::SOURCE_RELATIVE);
        $this->tmpDriver->method('isExists')->willReturn(true);
        $this->tmpDriver->expects($this->once())
            ->method('rename')
            ->with(self::TMP_ROOT.'/'.self::TARGET_RELATIVE, self::REMOTE_ROOT.'/'.self::TARGET_RELATIVE);

        $resultImage = $this->getTargetImageWithSrcSet();
        $plugin = $this->createPlugin();
        $result = $plugin->aroundConvertImage(
            $this->getSubject(),
            fn () => $resultImage,
            $this->getSourceImageWithSrcSet()
        );
        $this->assertSame($resultImage, $result);
    }

    private function createPlugin(array $formats = []): ConvertorInterfacePlugin
    {
        $tmpDirectoryWrite = $this->createMock(WriteInterface::class);
        $tmpDirectoryWrite->method('getDriver')->willReturn($this->tmpDriver);
        $tmpDirectoryWrite->method('getRelativePath')->willReturnCallback(
            fn (string $path) => ltrim(str_replace(self::TMP_ROOT, '', $path), '/')
        );

        $remoteDirectoryWrite = $this->createMock(WriteInterface::class);
        $remoteDirectoryWrite->method('getDriver')->willReturn($this->remoteDriver);
        $remoteDirectoryWrite->method('getAbsolutePath')->willReturnCallback(
            fn (string $path) => self::REMOTE_ROOT.'/'.$path
        );

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->with(DirectoryList::TMP)->willReturn($tmpDirectoryWrite);

        $targetDirectory = $this->createMock(TargetDirectory::class);
        $targetDirectory->method('getDirectoryWrite')->with(DirectoryList::ROOT)->willReturn($remoteDirectoryWrite);

        return new ConvertorInterfacePlugin(
            $this->remoteStorageConfig,
            $filesystem,
            $targetDirectory,
            $this->targetImageFactory,
            $this->fileUtil,
            $this->tmpFileCopier,
            $this->urlConvertor,
            $this->imageFactory,
            $this->debugger,
            $formats
        );
    }

    private function getSourceImage(): Image
    {
        return new Image(self::TMP_ROOT.'/'.self::SOURCE_RELATIVE, '/media/'.self::SOURCE_RELATIVE);
    }

    private function getTargetImage(): Image
    {
        return new Image(self::TMP_ROOT.'/'.self::TARGET_RELATIVE, '/media/'.self::TARGET_RELATIVE);
    }

    private function getSourceImageWithSrcSet(): Image
    {
        return new Image(
            self::TMP_ROOT.'/'.self::SOURCE_RELATIVE,
            self::BASE_URL.self::SOURCE_RELATIVE,
            self::BASE_URL.self::SRCSET_SOURCE_RELATIVE.' 2x'
        );
    }

    private function getTargetImageWithSrcSet(): Image
    {
        return new Image(
            self::TMP_ROOT.'/'.self::TARGET_RELATIVE,
            self::BASE_URL.self::TARGET_RELATIVE,
            self::BASE_URL.self::SRCSET_TARGET_RELATIVE.' 2x'
        );
    }

    private function getSubject(): ConvertorInterface
    {
        return $this->createMock(ConvertorInterface::class);
    }

    private function getWebpSubject(): ConvertorInterface
    {
        return $this->getFormatAwareSubject('webp');
    }

    private function getFormatAwareSubject(string $format): ConvertorInterface
    {
        return new class($format) implements ConvertorInterface {
            private string $format;

            public function __construct(string $format)
            {
                $this->format = $format;
            }

            public function getFormat(): string
            {
                return $this->format;
            }

            public function convertImage(Image $image): Image
            {
                return $image;
            }
        };
    }

    private function getAvifSubject(): ConvertorInterface
    {
        return new class implements ConvertorInterface {
            public function convertImage(Image $image): Image
            {
                return new Image(preg_replace('/\.jpg$/', '.avif', $image->getPath()), $image->getUrl());
            }
        };
    }
}
