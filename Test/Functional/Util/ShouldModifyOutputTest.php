<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Functional\Util;

use Magento\Framework\App\State;
use PHPUnit\Framework\TestCase;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertAreaCodeEquals;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\GetObjectManager;
use Yireo\NextGenImages\Util\ShouldModifyOutput;

class ShouldModifyOutputTest extends TestCase
{
    use AssertAreaCodeEquals;
    use GetObjectManager;

    public function testInstantiation()
    {
        $this->assertAreaCodeEquals('frontend');

        $shouldModifyOutput = $this->om()->get(ShouldModifyOutput::class);
        $skippedHandles = $shouldModifyOutput->getSkippedHandles();
        $this->assertNotEmpty($skippedHandles);
    }
}
