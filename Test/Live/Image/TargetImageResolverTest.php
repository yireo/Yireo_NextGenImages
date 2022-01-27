<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Yireo\NextGenImages\Test\Live\AbstractTestCase;

class TargetImageFactoryTest extends AbstractTestCase
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
        $webpImagePath = $mediaPath . '/nextgenimages/training-erin.webp';
        $this->assertEquals($webpImagePath, $webpImage->getPath());
    }

    private function getImagePath(): string
    {
        $mediaPath = $this->getDirectoryList()->getPath(DirectoryList::MEDIA);
        return $mediaPath . '/wysiwyg/training/training-erin.jpg';
    }
}