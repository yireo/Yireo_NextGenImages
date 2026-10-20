<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;
use Yireo\NextGenImages\Util\TmpFileCopier;

class TmpFileCopierTest extends AbstractTestCase
{
    private const TMP_ROOT = '/var/www/html/var/tmp';
    private const REMOTE_ROOT = '/remote';
    private const RELATIVE_PATH = 'media/catalog/product/a/b/test.jpg';

    /** @var WriteInterface&MockObject */
    private $tmpDirectoryWrite;

    /** @var WriteInterface&MockObject */
    private $remoteDirectoryWrite;

    /** @var DriverInterface&MockObject */
    private $tmpDriver;

    /** @var DriverInterface&MockObject */
    private $remoteDriver;

    /** @var LoggerInterface&MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->tmpDriver = $this->createMock(DriverInterface::class);
        $this->tmpDriver->method('getParentDirectory')->willReturnCallback(fn (string $path) => dirname($path));

        $this->remoteDriver = $this->createMock(DriverInterface::class);

        $this->tmpDirectoryWrite = $this->createMock(WriteInterface::class);
        $this->tmpDirectoryWrite->method('getDriver')->willReturn($this->tmpDriver);
        $this->tmpDirectoryWrite->method('getAbsolutePath')->willReturnCallback(
            fn (string $path) => self::TMP_ROOT.'/'.$path
        );

        $this->remoteDirectoryWrite = $this->createMock(WriteInterface::class);
        $this->remoteDirectoryWrite->method('getDriver')->willReturn($this->remoteDriver);
        $this->remoteDirectoryWrite->method('getAbsolutePath')->willReturnCallback(
            fn (string $path) => self::REMOTE_ROOT.'/'.$path
        );

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testReturnsSamePathWhenRemoteStorageIsDisabled(): void
    {
        $this->remoteDirectoryWrite->expects($this->never())->method('isFile');
        $this->tmpDriver->expects($this->never())->method('filePutContents');

        $copier = $this->createCopier(false);
        $this->assertSame(self::RELATIVE_PATH, $copier->copy(self::RELATIVE_PATH));
    }

    public function testCopiesRemoteFileToTmpFolder(): void
    {
        $this->remoteDirectoryWrite->method('isFile')
            ->with(self::REMOTE_ROOT.'/'.self::RELATIVE_PATH)
            ->willReturn(true);
        $this->remoteDriver->expects($this->once())
            ->method('fileGetContents')
            ->with(self::RELATIVE_PATH)
            ->willReturn('image-content');

        $this->tmpDirectoryWrite->expects($this->once())
            ->method('create')
            ->with(dirname(self::TMP_ROOT.'/'.self::RELATIVE_PATH));
        $this->tmpDriver->expects($this->once())
            ->method('filePutContents')
            ->with(self::TMP_ROOT.'/'.self::RELATIVE_PATH, 'image-content')
            ->willReturn(13);

        $copier = $this->createCopier(true);
        $this->assertSame(self::TMP_ROOT.'/'.self::RELATIVE_PATH, $copier->copy(self::RELATIVE_PATH));
    }

    public function testReturnsSamePathWhenRemoteFileDoesNotExist(): void
    {
        $this->remoteDirectoryWrite->method('isFile')->willReturn(false);
        $this->remoteDriver->expects($this->never())->method('fileGetContents');
        $this->tmpDriver->expects($this->never())->method('filePutContents');

        $copier = $this->createCopier(true);
        $this->assertSame(self::RELATIVE_PATH, $copier->copy(self::RELATIVE_PATH));
    }

    /**
     * The same image is often converted multiple times per request (e.g. WebP and AVIF, or srcset entries).
     * It should only be downloaded from remote storage once.
     */
    public function testDownloadsSameFileOnlyOnce(): void
    {
        $this->remoteDirectoryWrite->method('isFile')->willReturn(true);
        $this->remoteDriver->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('image-content');
        $this->tmpDriver->method('filePutContents')->willReturn(13);

        $copier = $this->createCopier(true);
        $firstPath = $copier->copy(self::RELATIVE_PATH);
        $secondPath = $copier->copy(self::RELATIVE_PATH);

        $this->assertSame($firstPath, $secondPath);
    }

    public function testRemovesTmpFilesOnDestruct(): void
    {
        $this->remoteDirectoryWrite->method('isFile')->willReturn(true);
        $this->remoteDriver->method('fileGetContents')->willReturn('image-content');
        $this->tmpDriver->method('filePutContents')->willReturn(13);
        $this->tmpDirectoryWrite->expects($this->once())
            ->method('delete')
            ->with(self::TMP_ROOT.'/'.self::RELATIVE_PATH);

        $copier = $this->createCopier(true);
        $copier->copy(self::RELATIVE_PATH);
        unset($copier);
    }

    public function testLogsErrorWhenTmpFileCanNotBeRemoved(): void
    {
        $this->remoteDirectoryWrite->method('isFile')->willReturn(true);
        $this->remoteDriver->method('fileGetContents')->willReturn('image-content');
        $this->tmpDriver->method('filePutContents')->willReturn(13);
        $this->tmpDirectoryWrite->method('delete')
            ->willThrowException(new FileSystemException(__('Permission denied')));
        $this->logger->expects($this->once())->method('error')->with('Permission denied');

        $copier = $this->createCopier(true);
        $copier->copy(self::RELATIVE_PATH);
        unset($copier);
    }

    private function createCopier(bool $remoteStorageEnabled): TmpFileCopier
    {
        $remoteStorageConfig = $this->createMock(RemoteStorageConfig::class);
        $remoteStorageConfig->method('isEnabled')->willReturn($remoteStorageEnabled);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->with(DirectoryList::TMP)->willReturn($this->tmpDirectoryWrite);

        $targetDirectory = $this->createMock(TargetDirectory::class);
        $targetDirectory->method('getDirectoryWrite')->with(DirectoryList::ROOT)->willReturn($this->remoteDirectoryWrite);

        return new TmpFileCopier($filesystem, $targetDirectory, $remoteStorageConfig, $this->logger);
    }
}
