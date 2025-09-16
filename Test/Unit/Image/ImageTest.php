<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Image;

use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Util\UrlConvertor;
use Yireo\NextGenImages\Test\Unit\AbstractTestCase;

class ImageTest extends AbstractTestCase
{
    public function testGetPath()
    {
        $image = new Image('/tmp/pub/foobar.jpg', '/media/foobar.jpg');
        $this->assertEquals('/tmp/pub/foobar.jpg', $image->getPath());
    }

    public function testGetUrl()
    {
        $image = new Image('/tmp/pub/foobar.jpg', '/media/foobar.jpg');
        $this->assertEquals('/media/foobar.jpg', $image->getUrl());
    }

    public function testGetSrcSet()
    {
        $image = new Image('/tmp/pub/foobar.jpg', '/media/foobar.jpg', '/foo/bar.jpg,/baz/qux.jpg 500w');
        $this->assertEquals('/foo/bar.jpg,/baz/qux.jpg 500w', $image->getSrcSet());
    }

    public function testGetMimetype()
    {
        $image = new Image('/tmp/pub/foobar.gif', 'foobar.gif');
        $this->assertEquals('image/gif', $image->getMimetype());

        $image = new Image('/tmp/pub/foobar.GIF', 'foobar.GIF');
        $this->assertEquals('image/gif', $image->getMimetype());

        $image = new Image('/tmp/pub/foobar.GIF', 'foobar.GIF');
        $this->assertEquals('image/gif', $image->getMimetype());

        $image = new Image('/tmp/pub/foobar.jpg', 'foobar.jpg');
        $this->assertEquals('image/jpeg', $image->getMimetype());

        $image = new Image('/tmp/pub/foobar.jpeg', 'foobar.jpeg');
        $this->assertEquals('image/jpeg', $image->getMimetype());

        $image = new Image('/tmp/pub/foobar.JPEG', 'foobar.JPEG');
        $this->assertEquals('image/jpeg', $image->getMimetype());

        $image = new Image('/tmp/pub/foobar.webp', 'foobar.webp');
        $this->assertEquals('image/webp', $image->getMimetype());

        $image = new Image('/tmp/pub/foobar.WEBP', 'foobar.WEBP');
        $this->assertEquals('image/webp', $image->getMimetype());

        $image = new Image('/tmp/pub/foobar.v', 'foobar.v');
        $this->assertEquals('image/png', $image->getMimetype());

        $image = new Image('/tmp/pub/foobar.PNG', 'foobar.PNG');
        $this->assertEquals('image/png', $image->getMimetype());
    }
}
