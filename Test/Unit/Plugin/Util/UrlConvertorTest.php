<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Plugin\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;
use Yireo\NextGenImages\Plugin\Util\UrlConvertor as UrlConvertorPlugin;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;
use Yireo\NextGenImages\Util\UrlConvertor;

class UrlConvertorTest extends AbstractTestCase
{
    private const ROOT = '/var/www/html';

    public function testAfterGetFilenameFromUrlDoesNothingWhenRemoteStorageIsDisabled(): void
    {
        $plugin = $this->createPlugin(false);
        $filename = self::ROOT.'/pub/media/catalog/product/test.jpg';

        $this->assertSame($filename, $plugin->afterGetFilenameFromUrl($this->getSubject(), $filename));
    }

    public function testBeforeGetUrlFromFilenameDoesNothingWhenRemoteStorageIsDisabled(): void
    {
        $plugin = $this->createPlugin(false);
        $filename = self::ROOT.'/var/tmp/media/catalog/product/test.webp';

        $this->assertSame([$filename], $plugin->beforeGetUrlFromFilename($this->getSubject(), $filename));
    }

    /**
     * @dataProvider getLocalToTmpPaths
     */
    public function testAfterGetFilenameFromUrlMapsLocalPathToTmpPath(string $localPath, string $tmpPath): void
    {
        $plugin = $this->createPlugin(true);
        $this->assertSame($tmpPath, $plugin->afterGetFilenameFromUrl($this->getSubject(), $localPath));
    }

    /**
     * @dataProvider getLocalToTmpPaths
     */
    public function testBeforeGetUrlFromFilenameMapsTmpPathToLocalPath(string $localPath, string $tmpPath): void
    {
        $plugin = $this->createPlugin(true);
        $this->assertSame([$localPath], $plugin->beforeGetUrlFromFilename($this->getSubject(), $tmpPath));
    }

    /**
     * @dataProvider getLocalToTmpPaths
     */
    public function testRoundTripReturnsOriginalPath(string $localPath): void
    {
        $plugin = $this->createPlugin(true);
        $tmpPath = $plugin->afterGetFilenameFromUrl($this->getSubject(), $localPath);
        $this->assertNotSame($localPath, $tmpPath);
        $this->assertSame([$localPath], $plugin->beforeGetUrlFromFilename($this->getSubject(), $tmpPath));
    }

    public static function getLocalToTmpPaths(): array
    {
        return [
            'media' => [
                self::ROOT.'/pub/media/catalog/product/a/b/test.jpg',
                self::ROOT.'/var/tmp/media/catalog/product/a/b/test.jpg',
            ],
            'static' => [
                self::ROOT.'/pub/static/frontend/Magento/luma/en_US/images/logo.png',
                self::ROOT.'/var/tmp/static/frontend/Magento/luma/en_US/images/logo.png',
            ],
            'base' => [
                self::ROOT.'/pub/logo.png',
                self::ROOT.'/var/tmp/pub/logo.png',
            ],
        ];
    }

    public function testAfterGetFilenameFromUrlLeavesNonLocalPathsAlone(): void
    {
        $plugin = $this->createPlugin(true);
        $this->assertSame('image.url', $plugin->afterGetFilenameFromUrl($this->getSubject(), 'image.url'));
    }

    /**
     * With remote storage, the local pub/media folder often does not exist, so realpath() returns false.
     * The plugin should still map the path instead of crashing with a TypeError (strpos() with a bool).
     */
    public function testAfterGetFilenameFromUrlWhenLocalMediaFolderDoesNotExist(): void
    {
        $plugin = $this->createPlugin(true, false);
        $localPath = self::ROOT.'/pub/media/catalog/product/a/b/test.jpg';

        $this->assertSame(
            self::ROOT.'/var/tmp/media/catalog/product/a/b/test.jpg',
            $plugin->afterGetFilenameFromUrl($this->getSubject(), $localPath)
        );
    }

    private function createPlugin(bool $remoteStorageEnabled, bool $localFoldersExist = true): UrlConvertorPlugin
    {
        $remoteStorageConfig = $this->createMock(RemoteStorageConfig::class);
        $remoteStorageConfig->method('isEnabled')->willReturn($remoteStorageEnabled);

        $directoryList = $this->createMock(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn(self::ROOT);
        $directoryList->method('getPath')->willReturnMap([
            [DirectoryList::MEDIA, self::ROOT.'/pub/media'],
            [DirectoryList::STATIC_VIEW, self::ROOT.'/pub/static'],
            [DirectoryList::TMP, self::ROOT.'/var/tmp'],
        ]);

        $fileDriver = $this->createMock(FileDriver::class);
        $fileDriver->method('getRealPath')->willReturnCallback(
            fn (string $path) => $localFoldersExist ? $path : false
        );

        return new UrlConvertorPlugin($remoteStorageConfig, $directoryList, $fileDriver);
    }

    private function getSubject(): UrlConvertor
    {
        return $this->createMock(UrlConvertor::class);
    }
}
