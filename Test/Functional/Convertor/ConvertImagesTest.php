<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Functional\Convertor;

use Yireo\NextGenImages\Convertor\ConvertorInterface;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Test\Functional\AbstractTestCase;

class ConvertImagesTest extends AbstractTestCase
{
    public function testConvertImages()
    {
        $testFolder = $this->getDirectoryList()->getRoot().'/pub/media/yireo-nextgenimages-tests/';
        $this->copyFixturesToTestFolder($testFolder);

        $files = glob($testFolder.'/*');
        if (empty($files)) {
            $this->markTestSkipped('No images found');
        }

        $convertors = $this->getConvertors();
        $this->assertNotEmpty($convertors);

        foreach ($files as $file) {
            if (!preg_match('/\.(png|jpeg|jpg)$/i', $file)) {
                continue;
            }

            foreach ($convertors as $convertor) {
                $this->assertInstanceOf(ConvertorInterface::class, $convertor);
                $image = new Image($file, '/test/'.basename($file));
                $newImage = $convertor->convertImage($image);
                $this->assertTrue(file_exists($newImage->getPath()));
            }
        }
    }

    private function copyFixturesToTestFolder(string $testFolder)
    {
        $sourceFolder = __DIR__.'/../fixtures/images';
        mkdir($testFolder);

        $files = glob($sourceFolder.'/*');
        foreach ($files as $file) {
            copy($file, $testFolder.'/'.basename($file));
        }
    }
}
