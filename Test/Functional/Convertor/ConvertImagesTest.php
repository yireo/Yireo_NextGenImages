<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Functional\Convertor;

use Yireo\NextGenImages\Convertor\ConvertorInterface;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Test\Functional\AbstractTestCase;

class ConvertImagesTest extends AbstractTestCase
{
    public function testConvertImages()
    {
        $searchPath = $this->getDirectoryList()->getRoot() . '/dev/tests/nextgenimages';
        $files = glob($searchPath . '/*');
        if (empty($files)) {
            $this->markTestSkipped('No images found');
            return;
        }

        $convertors = $this->getConvertors();
        $this->assertNotEmpty($convertors);

        foreach ($files as $file) {
            if (!preg_match('/\.(png|jpeg|jpg)$/i', $file)) {
                continue;
            }

            foreach ($convertors as $convertor) {
                $this->assertInstanceOf(ConvertorInterface::class, $convertor);
            }
        }
    }
}
