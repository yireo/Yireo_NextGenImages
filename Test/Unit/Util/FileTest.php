<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Util;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use PHPUnit\Framework\MockObject\MockObject;
use Yireo\NextGenImages\Util\File;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Logger\Debugger;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;
use Yireo\NextGenImages\Util\UrlConvertor;

/**
 * Class FileTest testing behaviour of File
 */
class FileTest extends AbstractTestCase
{
    /**
     * @test \Yireo\NextGenImages\Image\File::resolve()
     */
    public function testResolve(): void
    {
        $urlConvertor = $this->getMagentoMock(UrlConvertor::class);
        $urlConvertor->method('getFilenameFromUrl')->willReturn('/pub/some/fake/url.png');

        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('isExists')->willReturnMap([
            ['http://anotherhost.com/some/fake/url.png', false],
            ['/pub/some/fake/url.png', true]
        ]);

        $file = new File(
            $this->getMagentoMock(DirectoryList::class),
            $this->getFilesystemMock($fileDriverMock),
            $this->getMagentoMock(Debugger::class),
            $urlConvertor,
            $this->getMagentoMock(TargetImageFactory::class),
            $this->getMagentoMock(ImageFactory::class)
        ); // phpstan:ignore

        $resolvedFile = $file->resolve('http://anotherhost.com/some/fake/url.png');
        $this->assertSame('/pub/some/fake/url.png', $resolvedFile);
    }

    /**
     * @test \Yireo\NextGenImages\Image\File::fileExists()
     */
    public function testFileExistsWithExistingFile(): void
    {
        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('isExists')->willReturn(true);

        $file = new File(
            $this->getMagentoMock(DirectoryList::class),
            $this->getFilesystemMock($fileDriverMock),
            $this->getMagentoMock(Debugger::class),
            $this->getMagentoMock(UrlConvertor::class),
            $this->getMagentoMock(TargetImageFactory::class),
            $this->getMagentoMock(ImageFactory::class)
        ); // phpstan:ignore

        $this->assertSame(true, $file->fileExists('/some/fake/url.png'));
    }

    /**
     * @test \Yireo\NextGenImages\Image\File::fileExists()
     */
    public function testFileExistsWithoutExistingFile(): void
    {
        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('isExists')->willReturn(false);

        $file = new File(
            $this->getMagentoMock(DirectoryList::class),
            $this->getFilesystemMock($fileDriverMock),
            $this->getMagentoMock(Debugger::class),
            $this->getMagentoMock(UrlConvertor::class),
            $this->getMagentoMock(TargetImageFactory::class),
            $this->getMagentoMock(ImageFactory::class)
        ); // phpstan:ignore

        $this->assertSame(false, $file->fileExists('/some/fake/url.png'));
    }

    /**
     * @test \Yireo\NextGenImages\Image\File::getModificationTime()
     */
    public function testGetModificationTime()
    {
        $now = time();
        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('stat')->willReturn(['mtime' => $now]);

        $file = new File(
            $this->getMagentoMock(DirectoryList::class),
            $this->getFilesystemMock($fileDriverMock),
            $this->getMagentoMock(Debugger::class),
            $this->getMagentoMock(UrlConvertor::class),
            $this->getMagentoMock(TargetImageFactory::class),
            $this->getMagentoMock(ImageFactory::class)
        ); // phpstan:ignore
        $this->assertSame($now, $file->getModificationTime('/foo/bar.png'));

        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('stat')->willReturn(['mtime' => $now]);
        $fileDriverMock->method('isExists')->willReturn(false);

        $file = new File(
            $this->getMagentoMock(DirectoryList::class),
            $this->getFilesystemMock($fileDriverMock),
            $this->getMagentoMock(Debugger::class),
            $this->getMagentoMock(UrlConvertor::class),
            $this->getMagentoMock(TargetImageFactory::class),
            $this->getMagentoMock(ImageFactory::class)
        ); // phpstan:ignore
        $this->assertSame($now, $file->getModificationTime('/foo/bar.png'));

        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('stat')->willReturn([]);

        $file = new File(
            $this->getMagentoMock(DirectoryList::class),
            $this->getFilesystemMock($fileDriverMock),
            $this->getMagentoMock(Debugger::class),
            $this->getMagentoMock(UrlConvertor::class),
            $this->getMagentoMock(TargetImageFactory::class),
            $this->getMagentoMock(ImageFactory::class)
        ); // phpstan:ignore
        $this->assertSame(0, $file->getModificationTime('/foo/bar.png'));
    }

    /**
     * @test \Yireo\NextGenImages\Image\File::isNewerThan()
     */
    public function testIsNewerThanReturnsFalseWithFilesThatDontExist()
    {
        $now = time();
        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('isExists')->willReturn(false);
        $fileDriverMock->method('stat')->willReturnMap([
            ['target.png', ['ctime' => $now]],
            ['source.png', ['ctime' => $now]]
        ]);

        $file = new File(
            $this->getMagentoMock(DirectoryList::class),
            $this->getFilesystemMock($fileDriverMock),
            $this->getMagentoMock(Debugger::class),
            $this->getMagentoMock(UrlConvertor::class),
            $this->getMagentoMock(TargetImageFactory::class),
            $this->getMagentoMock(ImageFactory::class)
        ); // phpstan:ignore

        $this->assertFalse($file->isNewerThan('target.png', 'source.png'));
    }

    /**
     * @test \Yireo\NextGenImages\Image\File::isNewerThan()
     */
    public function testIsNewerThanReturnsFalseWithFilesCreatedAtSameTime()
    {
        $now = time();
        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('isExists')->willReturn(false);
        $fileDriverMock->method('stat')->willReturnMap([
            ['target.png', ['ctime' => $now]],
            ['source.png', ['ctime' => $now]]
        ]);

        $file = new File(
            $this->getMagentoMock(DirectoryList::class),
            $this->getFilesystemMock($fileDriverMock),
            $this->getMagentoMock(Debugger::class),
            $this->getMagentoMock(UrlConvertor::class),
            $this->getMagentoMock(TargetImageFactory::class),
            $this->getMagentoMock(ImageFactory::class)
        ); // phpstan:ignore

        $this->assertFalse($file->isNewerThan('target.png', 'source.png'));
    }

    /**
     * @test \Yireo\NextGenImages\Image\File::isNewerThan()
     */
    public function testIsNewerThanReturnsFalseWithTargetFileNewerThanSource()
    {
        $now = time();
        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('isExists')->willReturn(true);
        $fileDriverMock->method('stat')->willReturnMap([
            ['target.png', ['ctime' => $now + 1000]],
            ['source.png', ['ctime' => $now]]
        ]);

        $file = new File(
            $this->getMagentoMock(DirectoryList::class),
            $this->getFilesystemMock($fileDriverMock),
            $this->getMagentoMock(Debugger::class),
            $this->getMagentoMock(UrlConvertor::class),
            $this->getMagentoMock(TargetImageFactory::class),
            $this->getMagentoMock(ImageFactory::class)
        ); // phpstan:ignore

        $this->assertTrue($file->isNewerThan('target.png', 'source.png'));
    }

    /**
     * @test \Yireo\NextGenImages\Image\File::needsConversion()
     */
    public function testNeedsConversionWithExistingFiles()
    {
        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('isExists')->willReturn(false);

        $file = new File(
            $this->getMagentoMock(DirectoryList::class),
            $this->getFilesystemMock($fileDriverMock),
            $this->getMagentoMock(Debugger::class),
            $this->getMagentoMock(UrlConvertor::class),
            $this->getMagentoMock(TargetImageFactory::class),
            $this->getMagentoMock(ImageFactory::class)
        ); // phpstan:ignore

        $this->assertFalse($file->needsConversion('source.png', 'destination.webp'));
    }

    /**
     * @test \Yireo\NextGenImages\Image\File::needsConversion()
     */
    public function testNeedsConversionWithNonExistingDestinationFile()
    {
        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('isExists')->willReturnMap([
            ['source.png', true],
            ['destination.webp', false]
        ]);

        $file = new File(
            $this->getMagentoMock(DirectoryList::class),
            $this->getFilesystemMock($fileDriverMock),
            $this->getMagentoMock(Debugger::class),
            $this->getMagentoMock(UrlConvertor::class),
            $this->getMagentoMock(TargetImageFactory::class),
            $this->getMagentoMock(ImageFactory::class)
        ); // phpstan:ignore

        $this->assertTrue($file->needsConversion('source.png', 'destination.webp'));
    }

    /**
     * @return MockObject
     */
    private function getDebuggerMock(): MockObject
    {
        return $this->getMockBuilder(Debugger::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject
     */
    private function getFileDriverMock(): MockObject
    {
        return $this->getMockBuilder(FileDriver::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param MockObject $fileDriver
     *
     * @return MockObject
     */
    private function getFilesystemMock(MockObject $fileDriver): MockObject
    {
        $directoryWriteMock = $this->getMagentoMock(WriteInterface::class);
        $directoryWriteMock->method('getDriver')->willReturn($fileDriver);

        $filesystemMock = $this->getMagentoMock(Filesystem::class);
        $filesystemMock->method('getDirectoryWrite')->willReturn($directoryWriteMock);

        return $filesystemMock;
    }
}
