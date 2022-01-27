<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Live;

use Yireo\NextGenImages\Exception\ConvertorException;

class ConvertImagesTest extends AbstractTestCase
{
    /**
     * @throws ConvertorException
     */
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
                //$convertor->convert($file);
            }
        }
    }
}
