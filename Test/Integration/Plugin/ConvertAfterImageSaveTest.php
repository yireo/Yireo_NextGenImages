<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Framework\Image;
use Magento\TestFramework\Helper\Bootstrap;
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

    /**
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 1
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/convert_on_save 1
     */
    public function testIfReturnValueIsKeptWithoutDestination()
    {
        $plugin = Bootstrap::getObjectManager()->get(ConvertAfterImageSave::class);
        $subject = $this->createMock(Image::class);

        $this->assertSame('original', $plugin->afterSave($subject, 'original'));
        $this->assertSame('original', $plugin->afterSave($subject, 'original', null));
        $this->assertSame('original', $plugin->afterSave($subject, 'original', ''));
    }
}
