<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Live\Convertor;

use Yireo\NextGenImages\Convertor\ConvertorInterface;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Test\Live\AbstractTestCase;

class ConvertImagesTest extends AbstractTestCase
{
    public function testConvertImages()
    {
        $searchPath = $this->getDirectoryList()->getRoot() . '/dev/tests/nextgenimages';
        $files = glob($searchPath . '/*');
        $this->assertNotEmpty($files, $searchPath);

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
