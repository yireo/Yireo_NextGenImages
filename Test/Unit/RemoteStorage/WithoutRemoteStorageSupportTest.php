<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\RemoteStorage;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Read;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Logger\Debugger;
use Yireo\NextGenImages\Plugin\Util\File as FilePlugin;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;
use Yireo\NextGenImages\Util\File;
use Yireo\NextGenImages\Util\UrlConvertor;

/**
 * Illustrates why remote storage support is needed: with remote storage (like S3), images only exist remotely,
 * so the local file checks of NextGenImages fail and conversion is silently skipped.
 */
class WithoutRemoteStorageSupportTest extends AbstractTestCase
{
    private string $sandbox;

    protected function setUp(): void
    {
        $this->sandbox = sys_get_temp_dir().'/nextgenimages-remote-storage-'.uniqid();
        mkdir($this->sandbox.'/local/var/tmp/media', 0777, true);
        mkdir($this->sandbox.'/remote/media', 0777, true);
        file_put_contents($this->sandbox.'/remote/media/test.jpg', 'image-content');
    }

    protected function tearDown(): void
    {
        (new FileDriver())->deleteDirectory($this->sandbox);
    }

    public function testImageOnRemoteStorageIsNotFoundWithoutPlugin(): void
    {
        $file = $this->createFile();
        $localPath = $this->sandbox.'/local/var/tmp/media/test.jpg';

        $this->assertFalse($file->fileExists($localPath));
        $this->assertFalse(
            $file->needsConversion($localPath, $this->sandbox.'/local/var/tmp/media/test.webp'),
            'Without remote storage support, conversion is skipped for images that only exist remotely'
        );
    }

    public function testImageOnRemoteStorageIsFoundWithPlugin(): void
    {
        $file = $this->createFile();
        $localPath = $this->sandbox.'/local/var/tmp/media/test.jpg';
        $proceed = fn (string $path) => $file->fileExists($path);

        $this->assertTrue($this->createFilePlugin()->aroundFileExists($file, $proceed, $localPath));
    }

    private function createFile(): File
    {
        $directoryWrite = $this->createMock(WriteInterface::class);
        $directoryWrite->method('getDriver')->willReturn(new FileDriver());

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($directoryWrite);

        return new File(
            $filesystem,
            $this->createMock(Debugger::class),
            $this->createMock(UrlConvertor::class),
            $this->createMock(TargetImageFactory::class),
            $this->createMock(ImageFactory::class)
        );
    }

    private function createFilePlugin(): FilePlugin
    {
        $remoteStorageConfig = $this->createMock(RemoteStorageConfig::class);
        $remoteStorageConfig->method('isEnabled')->willReturn(true);

        $fileDriver = new FileDriver();
        $readFactory = $this->createMock(ReadFactory::class);
        $tmpDirectoryRead = new Read($readFactory, $fileDriver, $this->sandbox.'/local/var/tmp');
        $remoteDirectoryRead = new Read($readFactory, $fileDriver, $this->sandbox.'/remote');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('getDirectoryRead')->with(DirectoryList::TMP)->willReturn($tmpDirectoryRead);

        $targetDirectory = $this->createMock(TargetDirectory::class);
        $targetDirectory->method('getDirectoryRead')->with(DirectoryList::ROOT)->willReturn($remoteDirectoryRead);

        return new FilePlugin($remoteStorageConfig, $targetDirectory, $filesystem);
    }
}
