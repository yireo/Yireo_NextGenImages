<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Functional\Image;

use Magento\Framework\App\Filesystem\DirectoryList;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Test\Functional\AbstractTestCase;

class TargetImageResolverTest extends AbstractTestCase
{
    public function testResolve()
    {
        $imageFactory = $this->getObject(ImageFactory::class);
        $image = $imageFactory->createFromPath($this->getImagePath());
        $targetImageFactory = $this->getObject(TargetImageFactory::class);
        $webpImage = $targetImageFactory->create($image, 'webp');

        $mediaPath = $this->getDirectoryList()->getPath(DirectoryList::MEDIA);
        $webpImagePath = $mediaPath . '/wysiwyg/training/training-erin.webp';
        $this->assertEquals($webpImagePath, $webpImage->getPath());
    }

    public function testResolveFromCache()
    {
        $imageFactory = $this->getObject(ImageFactory::class);
        $image = $imageFactory->createFromPath($this->getImagePath());
        $targetImageFactory = $this->getObject(TargetImageFactory::class);
        $webpImage = $targetImageFactory->create($image, 'webp');

        $mediaPath = $this->getDirectoryList()->getPath(DirectoryList::MEDIA);
        $webpImagePath = $mediaPath . '/wysiwyg/training/training-erin.webp';
        $this->assertEquals($webpImagePath, $webpImage->getPath());
    }

    private function getImagePath(): string
    {
        $mediaPath = $this->getDirectoryList()->getPath(DirectoryList::MEDIA);
        return $mediaPath . '/wysiwyg/training/training-erin.jpg';
    }
}
