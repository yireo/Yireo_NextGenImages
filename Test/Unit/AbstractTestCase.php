<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yireo\NextGenImages\Util\UrlConvertor;

class AbstractTestCase extends TestCase
{
    public function getMagentoMock(string $className): MockObject
    {
        return $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
