<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Convertor;

use PHPUnit\Framework\TestCase;
use Yireo\NextGenImages\Convertor\ConvertorInterface;
use Yireo\NextGenImages\Convertor\ConvertorListing;

class ConvertorListingTest extends TestCase
{
    public function testGetConvertorsWithNoConvertor()
    {
        $convertorListing = new ConvertorListing();
        $this->assertEmpty($convertorListing->getConvertors());
    }

    public function testGetConvertorsWithOneConvertor()
    {
        $convertorListing = new ConvertorListing([
            $this->getMockBuilder(ConvertorInterface::class)->getMock()
        ]);

        $this->assertEquals(1, count($convertorListing->getConvertors()));
    }
}
