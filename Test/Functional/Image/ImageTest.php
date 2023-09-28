<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Functional\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Test\Functional\AbstractTestCase;

class ImageTest extends AbstractTestCase
{
    public function testGetPath()
    {
        $imageFactory = $this->getObject(ImageFactory::class);
        $image = $imageFactory->createFromPath($this->getImagePath());
        $this->assertEquals($this->getImagePath(), $image->getPath());
    }

    public function testGetMimetype()
    {
        $imageFactory = $this->getObject(ImageFactory::class);
        $image = $imageFactory->createFromPath($this->getImagePath());
        $this->assertEquals('image/jpeg', $image->getMimetype());
    }

    public function testGetUrl()
    {
        $imageFactory = $this->getObject(ImageFactory::class);
        $image = $imageFactory->createFromPath($this->getImagePath());
        $this->assertEquals($this->getMediaUrl() . 'wysiwyg/training/training-erin.jpg', $image->getUrl());
    }

    private function getImagePath(): string
    {
        $mediaPath = $this->getDirectoryList()->getPath(DirectoryList::MEDIA);
        return $mediaPath . '/wysiwyg/training/training-erin.jpg';
    }
}
