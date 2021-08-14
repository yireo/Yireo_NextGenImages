<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Image;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\File\ReadFactory as FileReadFactory;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\File\ReadInterface;
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
        $fileRead = $this->getFileReadMockWithoutExistingFile();

        $file = new File(
            $this->getDirectoryListMock(),
            $this->getFileDriverMock(),
            $this->getDebuggerMock(),
            $this->getFileReadFactoryMockWithNonExistingFiles(),
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
        $file = new File(
            $this->getDirectoryListMock(),
            $this->getFileDriverMock(),
            $this->getDebuggerMock(),
            $this->getFileReadFactoryMockWithExistingFiles(),
            $this->getUrlConvertorMock()
        ); // phpstan:ignore

        $this->assertSame(true, $file->fileExists('/some/fake/url.png'));
    }

    /**
     * @test \Yireo\NextGenImages\Image\File::fileExists()
     */
    public function testFileExistsWithoutExistingFile(): void
    {
        $file = new File(
            $this->getDirectoryListMock(),
            $this->getFileDriverMock(),
            $this->getDebuggerMock(),
            $this->getFileReadFactoryMockWithNonExistingFiles(),
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
            $this->getFileDriverMock(),
            $this->getDebuggerMock(),
            $this->getFileReadFactoryMock(),
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
            $fileDriverMock,
            $this->getDebuggerMock(),
            $this->getFileReadFactoryMock(),
            $this->getUrlConvertorMock()
        ); // phpstan:ignore
        $this->assertSame($now, $file->getModificationTime('/foo/bar.png'));

        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('stat')->willReturn(['mtime' => $now]);

        $file = new File(
            $this->getDirectoryListMock(),
            $fileDriverMock,
            $this->getDebuggerMock(),
            $this->getFileReadFactoryMock(),
            $this->getUrlConvertorMock()
        ); // phpstan:ignore
        $this->assertSame($now, $file->getModificationTime('/foo/bar.png'));

        $fileDriverMock = $this->getFileDriverMock();
        $fileDriverMock->method('stat')->willReturn([]);

        $file = new File(
            $this->getDirectoryListMock(),
            $this->getFileDriverMock([]),
            $this->getDebuggerMock(),
            $this->getFileReadFactoryMock(),
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
        $fileDriverMock->method('stat')->willReturnMap([
            ['target.png', ['ctime' => $now]],
            ['source.png', ['ctime' => $now]]
        ]);

        $file = new File(
            $this->getDirectoryListMock(),
            $fileDriverMock,
            $this->getDebuggerMock(),
            $this->getFileReadFactoryMockWithExistingFiles(),
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
        $fileDriverMock->method('stat')->willReturnMap([
            ['target.png', ['ctime' => $now]],
            ['source.png', ['ctime' => $now]]
        ]);

        $file = new File(
            $this->getDirectoryListMock(),
            $fileDriverMock,
            $this->getDebuggerMock(),
            $this->getFileReadFactoryMockWithExistingFiles(),
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
        $fileDriverMock->method('stat')->willReturnMap([
            ['target.png', ['ctime' => $now + 1000]],
            ['source.png', ['ctime' => $now]]
        ]);

        $file = new File(
            $this->getDirectoryListMock(),
            $fileDriverMock,
            $this->getDebuggerMock(),
            $this->getFileReadFactoryMockWithExistingFiles(),
            $this->getUrlConvertorMock()
        ); // phpstan:ignore

        $this->assertTrue($file->isNewerThan('target.png', 'source.png'));
    }

    /**
     * @test \Yireo\NextGenImages\Image\File::needsConversion()
     */
    public function testNeedsConversionWithExistingFiles()
    {
        $file = new File(
            $this->getDirectoryListMock(),
            $this->getFileDriverMock(),
            $this->getDebuggerMock(),
            $this->getFileReadFactoryMockWithExistingFiles(),
            $this->getUrlConvertorMock()
        ); // phpstan:ignore

        $this->assertFalse($file->needsConversion('source.png', 'destination.webp'));
    }

    /**
     * @test \Yireo\NextGenImages\Image\File::needsConversion()
     */
    public function testNeedsConversionWithNonExistingDestinationFile()
    {
        $fileReadFactoryMock = $this->getFileReadFactoryMock();
        $fileReadFactoryMock->method('create')->willReturnMap([
            ['source.png', 'file', $this->getFileReadMockWithExistingFile()],
            ['destination.webp', 'file', $this->getFileReadMockWithoutExistingFile()]
        ]);

        $file = new File(
            $this->getDirectoryListMock(),
            $this->getFileDriverMock(),
            $this->getDebuggerMock(),
            $fileReadFactoryMock,
            $this->getUrlConvertorMock()
        ); // phpstan:ignore

        $this->assertTrue($file->needsConversion('source.png', 'destination.webp'));
    }

    /**
     * @return UrlConvertor
     */
    private function getUrlConvertorMock(): UrlConvertor
    {
        return $this->getMockBuilder(UrlConvertor::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return FileReadFactory
     */
    private function getFileReadFactoryMockWithExistingFiles(): FileReadFactory
    {
        $fileRead = $this->getFileReadMockWithExistingFile();
        $fileReadFactoryMock = $this->getFileReadFactoryMock();
        $fileReadFactoryMock->method('create')->willReturn($fileRead);
        return $fileReadFactoryMock;
    }

    /**
     * @return FileReadFactory
     */
    private function getFileReadFactoryMockWithNonExistingFiles(): FileReadFactory
    {
        $fileRead = $this->getFileReadMockWithoutExistingFile();
        $fileReadFactoryMock = $this->getFileReadFactoryMock();
        $fileReadFactoryMock->method('create')->willReturn($fileRead);
        return $fileReadFactoryMock;
    }

    /**
     * @return FileReadFactory
     */
    private function getFileReadFactoryMock(): FileReadFactory
    {
        return $this->getMockBuilder(FileReadFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ReadInterface
     */
    private function getFileReadMockWithExistingFile(): ReadInterface
    {
        $fileReadMock = $this->getFileReadMock();
        $fileReadMock->method('readAll')->willReturn('foobar');
        return $fileReadMock;
    }

    /**
     * @return ReadInterface
     */
    private function getFileReadMockWithoutExistingFile(): ReadInterface
    {
        $fileReadMock = $this->getFileReadMock();
        $fileReadMock->method('readAll')->willThrowException(new FileSystemException(__('Nope')));
        return $fileReadMock;
    }

    /**
     * @return ReadInterface
     */
    private function getFileReadMock(): ReadInterface
    {
        return $this->getMockBuilder(ReadInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return Debugger
     */
    private function getDebuggerMock(): Debugger
    {
        return $this->getMockBuilder(Debugger::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return FileDriver
     */
    private function getFileDriverMock(): FileDriver
    {
        return $this->getMockBuilder(FileDriver::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return DirectoryList
     */
    private function getDirectoryListMock(): DirectoryList
    {
        return $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
