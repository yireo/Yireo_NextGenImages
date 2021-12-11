<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Image;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use PHPUnit\Framework\MockObject\MockObject;
use Yireo\NextGenImages\Image\File;
use Yireo\NextGenImages\Image\UrlConvertor;
use PHPUnit\Framework\TestCase;
use Yireo\NextGenImages\Logger\Debugger;

/**
 * Class FileTest testing behaviour of File
 */
class FileTest extends TestCase
{
    /**
     * @test \Yireo\NextGenImages\Image\File::resolve()
     */
    public function testResolve(): void
    {
        $urlConvertor = $this->getUrlConvertorMock();
        $urlConvertor->method('getFilenameFromUrl')->willReturn('/pub/some/fake/url.png');

        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('isExists')->willReturnMap([
            ['http://anotherhost.com/some/fake/url.png', false],
            ['/pub/some/fake/url.png', true]
        ]);

        $file = new File(
            $this->getDirectoryListMock(),
            $this->getFilesystemMock($fileDriverMock),
            $this->getDebuggerMock(),
            $urlConvertor
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
            $this->getDirectoryListMock(),
            $this->getFilesystemMock($fileDriverMock),
            $this->getDebuggerMock(),
            $this->getUrlConvertorMock()
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
            $this->getDirectoryListMock(),
            $this->getFilesystemMock($fileDriverMock),
            $this->getDebuggerMock(),
            $this->getUrlConvertorMock()
        ); // phpstan:ignore

        $this->assertSame(false, $file->fileExists('/some/fake/url.png'));
    }

    /**
     * @test \Yireo\NextGenImages\Image\File::convertSuffix()
     */
    public function testConvertSuffix()
    {
        $file = new File(
            $this->getDirectoryListMock(),
            $this->getFilesystemMock($this->getFileDriverMock()),
            $this->getDebuggerMock(),
            $this->getUrlConvertorMock()
        ); // phpstan:ignore

        $destination = $file->convertSuffix('/some/path/example.jpg', '.webp');
        $this->assertSame('/some/path/example.webp', $destination);

        $destination = $file->convertSuffix('/some/path/example.JPG', '.webp');
        $this->assertSame('/some/path/example.webp', $destination);

        $destination = $file->convertSuffix('/some/path/example.jpeg', '.webp');
        $this->assertSame('/some/path/example.webp', $destination);

        $destination = $file->convertSuffix('/some/path/example.png', '.webp');
        $this->assertSame('/some/path/example.webp', $destination);

        $destination = $file->convertSuffix('/some/path/example.jpeg', '.foobar');
        $this->assertSame('/some/path/example.foobar', $destination);
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
            $this->getDirectoryListMock(),
            $this->getFilesystemMock($fileDriverMock),
            $this->getDebuggerMock(),
            $this->getUrlConvertorMock()
        ); // phpstan:ignore
        $this->assertSame($now, $file->getModificationTime('/foo/bar.png'));

        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('stat')->willReturn(['mtime' => $now]);
        $fileDriverMock->method('isExists')->willReturn(false);

        $file = new File(
            $this->getDirectoryListMock(),
            $this->getFilesystemMock($fileDriverMock),
            $this->getDebuggerMock(),
            $this->getUrlConvertorMock()
        ); // phpstan:ignore
        $this->assertSame($now, $file->getModificationTime('/foo/bar.png'));

        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('stat')->willReturn([]);

        $file = new File(
            $this->getDirectoryListMock(),
            $this->getFilesystemMock($fileDriverMock),
            $this->getDebuggerMock(),
            $this->getUrlConvertorMock()
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
            $this->getDirectoryListMock(),
            $this->getFilesystemMock($fileDriverMock),
            $this->getDebuggerMock(),
            $this->getUrlConvertorMock()
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
            $this->getDirectoryListMock(),
            $this->getFilesystemMock($fileDriverMock),
            $this->getDebuggerMock(),
            $this->getUrlConvertorMock()
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
            $this->getDirectoryListMock(),
            $this->getFilesystemMock($fileDriverMock),
            $this->getDebuggerMock(),
            $this->getUrlConvertorMock()
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
            $this->getDirectoryListMock(),
            $this->getFilesystemMock($fileDriverMock),
            $this->getDebuggerMock(),
            $this->getUrlConvertorMock()
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
            $this->getDirectoryListMock(),
            $this->getFilesystemMock($fileDriverMock),
            $this->getDebuggerMock(),
            $this->getUrlConvertorMock()
        ); // phpstan:ignore

        $this->assertTrue($file->needsConversion('source.png', 'destination.webp'));
    }

    /**
     * @return MockObject
     */
    private function getUrlConvertorMock(): MockObject
    {
        return $this->getMockBuilder(UrlConvertor::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $directoryWriteMock = $this->getMockBuilder(Filesystem\Directory\WriteInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $directoryWriteMock->method('getDriver')->willReturn($fileDriver);
        $filesystemMock->method('getDirectoryWrite')->willReturn($directoryWriteMock);

        return $filesystemMock;
    }

    /**
     * @return MockObject
     */
    private function getDirectoryListMock(): MockObject
    {
        return $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
