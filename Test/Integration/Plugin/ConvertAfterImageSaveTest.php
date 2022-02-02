<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Magento\Framework\Image;
use Yireo\NextGenImages\Plugin\ConvertAfterImageSave;
use Yireo\NextGenImages\Test\Integration\AbstractTestCase;

class ConvertAfterImageSaveTest extends AbstractTestCase
{
    public function testIfPluginIsEnabled()
    {
        $this->assertDiPluginIsRegistered(
            Image::class,
            ConvertAfterImageSave::class,
            'after'
        );
    }
}
