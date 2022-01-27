<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Image;

use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Util\UrlConvertor;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;

class ImageTest extends AbstractTestCase
{
    public function testGetPath()
    {
        $urlConvertor = $this->getMagentoMock(UrlConvertor::class);
        $image = new Image($urlConvertor, 'foobar.jpg');
        $this->assertEquals('foobar.jpg', $image->getPath());
    }

    public function testGetUrl()
    {
        $urlConvertor = $this->getMagentoMock(UrlConvertor::class);
        $urlConvertor->method('getUrlFromFilename')->willReturn('/media/foobar.jpg');
        $image = new Image($urlConvertor, 'foobar.jpg');
        $this->assertEquals('/media/foobar.jpg', $image->getUrl());
    }

    public function testGetMimetype()
    {
        $urlConvertor = $this->getMagentoMock(UrlConvertor::class);

        $image = new Image($urlConvertor, 'foobar.gif');
        $this->assertEquals('image/gif', $image->getMimetype());

        $image = new Image($urlConvertor, 'foobar.GIF');
        $this->assertEquals('image/gif', $image->getMimetype());

        $image = new Image($urlConvertor, 'foobar.GIF');
        $this->assertEquals('image/gif', $image->getMimetype());

        $image = new Image($urlConvertor, 'foobar.jpg');
        $this->assertEquals('image/jpeg', $image->getMimetype());

        $image = new Image($urlConvertor, 'foobar.jpeg');
        $this->assertEquals('image/jpeg', $image->getMimetype());

        $image = new Image($urlConvertor, 'foobar.JPEG');
        $this->assertEquals('image/jpeg', $image->getMimetype());

        $image = new Image($urlConvertor, 'foobar.webp');
        $this->assertEquals('image/webp', $image->getMimetype());

        $image = new Image($urlConvertor, 'foobar.WEBP');
        $this->assertEquals('image/webp', $image->getMimetype());

        $image = new Image($urlConvertor, 'foobar.v');
        $this->assertEquals('image/png', $image->getMimetype());

        $image = new Image($urlConvertor, 'foobar.PNG');
        $this->assertEquals('image/png', $image->getMimetype());
    }
}
