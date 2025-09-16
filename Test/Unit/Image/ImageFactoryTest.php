<?php declare(strict_types=1); // @phpstan-ignorefile

namespace Yireo\NextGenImages\Test\Unit\Image;

use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
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
        $objectManager->method('create')->willReturn(new Image('/tmp/pub/foo/bar.jpg', '/foo/bar.jpg'));

        // @phpstan-ignore-next-line
        $imageFactory = new ImageFactory($objectManager, $urlConvertor);
        $image = $imageFactory->createFromPath('/tmp/pub/foo/bar.jpg');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals('/tmp/pub/foo/bar.jpg', $image->getPath());
    }

    public function testCreateFromUrl()
    {
        $urlConvertor = $this->getUrlConvertor();
        $urlConvertor->method('getFilenameFromUrl')->willReturn('/tmp/pub/foo/bar.jpg');
        $urlConvertor->method('getUrlFromFilename')->willReturn('/foo/bar.jpg');
        $objectManager = $this->getObjectManager();
        $objectManager->method('create')->willReturn(new Image('/tmp/pub/foo/bar.jpg', '/foo/bar.jpg'));

        // @phpstan-ignore-next-line
        $imageFactory = new ImageFactory($objectManager, $urlConvertor);
        $image = $imageFactory->createFromUrl('/foo/bar.jpg');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals('/tmp/pub/foo/bar.jpg', $image->getPath());
        $this->assertEquals('/foo/bar.jpg', $image->getUrl());
    }

    public function testCreateFromUrlWithMultipleUrls()
    {
        $urlConvertor = $this->getUrlConvertor();
        $urlConvertor->method('getFilenameFromUrl')->willReturn('/tmp/pub/foo/bar.jpg');
        $urlConvertor->method('getUrlFromFilename')->willReturn('/foo/bar.jpg');
        $objectManager = $this->getObjectManager();
        $objectManager->method('create')->willReturn(
            new Image('/tmp/pub/foo/bar.jpg', '/foo/bar.jpg', '/foo/bar.jpg,/baz/qux.jpg 500w')
        );
        
        // @phpstan-ignore-next-line
        $imageFactory = new ImageFactory($objectManager, $urlConvertor);
        $image = $imageFactory->createFromUrl(['/foo/bar.jpg', '500w' => '/baz/qux.jpg']);
        
        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals('/tmp/pub/foo/bar.jpg', $image->getPath());
        $this->assertEquals('/foo/bar.jpg', $image->getUrl());
        $this->assertEquals('/foo/bar.jpg,/baz/qux.jpg 500w', $image->getSrcSet());
    }

    private function getObjectManager(): MockObject
    {
        return $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getUrlConvertor(): MockObject
    {
        return $this->getMockBuilder(UrlConvertor::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
