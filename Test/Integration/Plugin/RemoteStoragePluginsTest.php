<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Plugin;

use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;
use Yireo\NextGenImages\Convertor\ConvertorInterface;
use Yireo\NextGenImages\Plugin\Convertor\ConvertorInterface as ConvertorInterfacePlugin;
use Yireo\NextGenImages\Plugin\Util\File as FilePlugin;
use Yireo\NextGenImages\Plugin\Util\UrlConvertor as UrlConvertorPlugin;
use Yireo\NextGenImages\Util\File;
use Yireo\NextGenImages\Util\UrlConvertor;

class RemoteStoragePluginsTest extends AbstractTestCase
{
    public function testIfConvertorPluginIsEnabled(): void
    {
        $this->assertInterceptorPluginIsRegistered(
            ConvertorInterface::class,
            ConvertorInterfacePlugin::class,
            'Yireo_NextGenImages::convertorInterface'
        );
    }

    public function testIfFilePluginIsEnabled(): void
    {
        $this->assertInterceptorPluginIsRegistered(
            File::class,
            FilePlugin::class,
            'Yireo_NextGenImages::file'
        );
    }

    public function testIfUrlConvertorPluginIsEnabled(): void
    {
        $this->assertInterceptorPluginIsRegistered(
            UrlConvertor::class,
            UrlConvertorPlugin::class,
            'Yireo_NextGenImages::urlConvertor'
        );
    }
}
