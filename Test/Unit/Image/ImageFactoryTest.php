<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Image;

use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Util\UrlConvertor;

class ImageFactoryTest extends TestCase
{
    public function testCreateFromPath()
    {
        $urlConvertor = $this->getUrlConvertor();
        $objectManager = $this->getObjectManager();
        $objectManager->method('create')->willReturn(new Image($urlConvertor, '/path/foo/bar.jpg'));

        $imageFactory = new ImageFactory($objectManager, $urlConvertor);
        $image = $imageFactory->createFromPath('/path/foo/bar.jpg');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals('/path/foo/bar.jpg', $image->getPath());
    }

    public function testCreateFromUrl()
    {
        $urlConvertor = $this->getUrlConvertor();
        $urlConvertor->method('getFilenameFromUrl')->willReturn('/path/foo/bar.jpg');
        $urlConvertor->method('getUrlFromFilename')->willReturn('/url/foo/bar.jpg');
        $objectManager = $this->getObjectManager();
        $objectManager->method('create')->willReturn(new Image($urlConvertor, '/path/foo/bar.jpg'));

        $imageFactory = new ImageFactory($objectManager, $urlConvertor);
        $image = $imageFactory->createFromUrl('/foo/bar.jpg');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals('/path/foo/bar.jpg', $image->getPath());
        $this->assertEquals('/url/foo/bar.jpg', $image->getUrl());
    }

    private function getObjectManager(): ObjectManagerInterface
    {
        return $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getUrlConvertor(): UrlConvertor
    {
        return $this->getMockBuilder(UrlConvertor::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}