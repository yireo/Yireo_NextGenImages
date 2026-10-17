<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Plugin\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Yireo\NextGenImages\Plugin\Util\File as FilePlugin;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;
use Yireo\NextGenImages\Util\File;

class FileTest extends AbstractTestCase
{
    private const TMP_PATH = '/var/www/html/var/tmp/media/catalog/product/a/b/test.jpg';
    private const RELATIVE_PATH = 'media/catalog/product/a/b/test.jpg';

    /** @var ReadInterface&MockObject */
    private $tmpDirectoryRead;

    /** @var ReadInterface&MockObject */
    private $remoteDirectoryRead;

    protected function setUp(): void
    {
        $this->tmpDirectoryRead = $this->createMock(ReadInterface::class);
        $this->tmpDirectoryRead->method('getRelativePath')
            ->with(self::TMP_PATH)
            ->willReturn(self::RELATIVE_PATH);

        $this->remoteDirectoryRead = $this->createMock(ReadInterface::class);
    }

    public function testFileExistsProceedsWhenRemoteStorageIsDisabled(): void
    {
        $this->remoteDirectoryRead->expects($this->never())->method('isExist');

        $plugin = $this->createPlugin(false);
        $proceed = $this->createProceed(true, self::TMP_PATH);

        $this->assertTrue($plugin->aroundFileExists($this->getFileSubject(), $proceed, self::TMP_PATH));
    }

    public function testFileExistsChecksRemoteStorageWhenEnabled(): void
    {
        $this->remoteDirectoryRead->expects($this->once())
            ->method('isExist')
            ->with(self::RELATIVE_PATH)
            ->willReturn(true);

        $plugin = $this->createPlugin(true);
        $proceed = $this->createNeverCalledProceed();

        $this->assertTrue($plugin->aroundFileExists($this->getFileSubject(), $proceed, self::TMP_PATH));
    }

    public function testFileExistsReturnsFalseWhenFileIsMissingOnRemoteStorage(): void
    {
        $this->remoteDirectoryRead->method('isExist')->willReturn(false);

        $plugin = $this->createPlugin(true);
        $proceed = $this->createNeverCalledProceed();

        $this->assertFalse($plugin->aroundFileExists($this->getFileSubject(), $proceed, self::TMP_PATH));
    }

    /**
     * The original File::fileExists() never throws: it turns a FileSystemException into `false`.
     * The plugin should keep that contract, otherwise a broken remote connection breaks the page.
     */
    public function testFileExistsReturnsFalseWhenRemoteStorageThrowsException(): void
    {
        $this->remoteDirectoryRead->method('isExist')
            ->willThrowException(new FileSystemException(__('Remote storage unavailable')));

        $plugin = $this->createPlugin(true);
        $proceed = $this->createNeverCalledProceed();

        $this->assertFalse($plugin->aroundFileExists($this->getFileSubject(), $proceed, self::TMP_PATH));
    }

    public function testGetModificationTimeProceedsWhenRemoteStorageIsDisabled(): void
    {
        $this->remoteDirectoryRead->expects($this->never())->method('stat');

        $plugin = $this->createPlugin(false);
        $proceed = $this->createProceed(1234, self::TMP_PATH);

        $this->assertSame(1234, $plugin->aroundGetModificationTime($this->getFileSubject(), $proceed, self::TMP_PATH));
    }

    public function testGetModificationTimeUsesRemoteMtime(): void
    {
        $this->remoteDirectoryRead->expects($this->once())
            ->method('stat')
            ->with(self::RELATIVE_PATH)
            ->willReturn(['mtime' => 1111, 'ctime' => 2222]);

        $plugin = $this->createPlugin(true);
        $proceed = $this->createNeverCalledProceed();

        $this->assertSame(1111, $plugin->aroundGetModificationTime($this->getFileSubject(), $proceed, self::TMP_PATH));
    }

    public function testGetModificationTimeFallsBackToRemoteCtime(): void
    {
        $this->remoteDirectoryRead->method('stat')->willReturn(['ctime' => 2222]);

        $plugin = $this->createPlugin(true);
        $proceed = $this->createNeverCalledProceed();

        $this->assertSame(2222, $plugin->aroundGetModificationTime($this->getFileSubject(), $proceed, self::TMP_PATH));
    }

    public function testGetModificationTimeFallsBackToZero(): void
    {
        $this->remoteDirectoryRead->method('stat')->willReturn([]);

        $plugin = $this->createPlugin(true);
        $proceed = $this->createNeverCalledProceed();

        $this->assertSame(0, $plugin->aroundGetModificationTime($this->getFileSubject(), $proceed, self::TMP_PATH));
    }

    /**
     * The original File::getModificationTime() returns 0 when stat() fails (e.g. missing target file).
     * The plugin should keep that contract.
     */
    public function testGetModificationTimeReturnsZeroWhenRemoteStatThrowsException(): void
    {
        $this->remoteDirectoryRead->method('stat')
            ->willThrowException(new FileSystemException(__('File does not exist')));

        $plugin = $this->createPlugin(true);
        $proceed = $this->createNeverCalledProceed();

        $this->assertSame(0, $plugin->aroundGetModificationTime($this->getFileSubject(), $proceed, self::TMP_PATH));
    }

    private function createPlugin(bool $remoteStorageEnabled): FilePlugin
    {
        $remoteStorageConfig = $this->createMock(RemoteStorageConfig::class);
        $remoteStorageConfig->method('isEnabled')->willReturn($remoteStorageEnabled);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('getDirectoryRead')
            ->with(DirectoryList::TMP)
            ->willReturn($this->tmpDirectoryRead);

        $targetDirectory = $this->createMock(TargetDirectory::class);
        $targetDirectory->method('getDirectoryRead')
            ->with(DirectoryList::ROOT)
            ->willReturn($this->remoteDirectoryRead);

        return new FilePlugin($remoteStorageConfig, $targetDirectory, $filesystem);
    }

    private function getFileSubject(): File
    {
        return $this->createMock(File::class);
    }

    private function createProceed($returnValue, string $expectedArgument): callable
    {
        return function (string $filePath) use ($returnValue, $expectedArgument) {
            $this->assertSame($expectedArgument, $filePath);
            return $returnValue;
        };
    }

    private function createNeverCalledProceed(): callable
    {
        return function () {
            $this->fail('$proceed should not be called when remote storage is enabled');
        };
    }
}
