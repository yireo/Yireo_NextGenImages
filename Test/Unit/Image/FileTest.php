<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Image;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\ReadFactory as DirectoryReadFactory;
use Magento\Framework\Filesystem\File\ReadFactory as FileReadFactory;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\File\ReadInterface;
use Yireo\NextGenImages\Image\File;
use PHPUnit\Framework\TestCase;
use Yireo\NextGenImages\Logger\Debugger;

/**
 * Class FileTest testing behaviour of File
 */
class FileTest extends TestCase
{
    /**
     * @test Test the resolve() function
     */
    public function testResolve(): void
    {
        $target = $this->getTarget();
        $resolvedFile = $target->resolve('http://anotherhost.com/some/fake/url.png');
        $this->assertSame($resolvedFile, '/pub/some/fake/url.png');
    }

    /**
     * @return File
     */
    public function getTarget(): File
    {
        $directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $directoryReadFactoryMock = $this->getMockBuilder(DirectoryReadFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileDriverMock = $this->getMockBuilder(FileDriver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $debugger = $this->getMockBuilder(Debugger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileReadFactoryMock = $this->getMockBuilder(FileReadFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileReadMock = $this->getMockBuilder(ReadInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fileReadFactoryMock->method('create')->willReturn($fileReadMock);

        return new File(
            $directoryListMock,
            $directoryReadFactoryMock,
            $fileDriverMock,
            $debugger,
            $fileReadFactoryMock
        ); // phpstan:ignore
    }
}
