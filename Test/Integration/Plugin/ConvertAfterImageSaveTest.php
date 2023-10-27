<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Framework\Image;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertDatabaseQueryCount;
use Yireo\NextGenImages\Plugin\ConvertAfterImageSave;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;

class ConvertAfterImageSaveTest extends AbstractTestCase
{
    use AssertDatabaseQueryCount;
    
    public function testIfPluginIsEnabled()
    {
        $queryCount = $this->getDatabaseQueryCount();
        
        $this->assertInterceptorPluginIsRegistered(
            Image::class,
            ConvertAfterImageSave::class,
            'Yireo_NextGenImages::convertAfterImageSave'
        );
        
        $this->assertDatabaseQueryCount($queryCount);
    }
}
