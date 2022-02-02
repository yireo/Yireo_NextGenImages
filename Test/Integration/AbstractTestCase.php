<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration;

use Magento\Developer\Model\Di\PluginList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class AbstractTestCase extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
    }

    protected function assertModuleIsRegistered(string $moduleName)
    {
        $moduleList = $this->objectManager->create(ModuleList::class);
        $this->assertTrue(
            $moduleList->has($moduleName),
            'The module "' . $moduleName . '" is not enabled'
        );
    }

    protected function assertDiPluginIsRegistered(string $subjectClass, string $pluginClass, string $pluginMethodType)
    {
        $pluginList = $this->objectManager->get(PluginList::class);
        $plugins = $pluginList->getPluginsListByClass($subjectClass);

        $this->assertTrue(!empty($plugins));
        $this->assertArrayHasKey($pluginMethodType, $plugins);
        $this->assertArrayHasKey($pluginClass, $plugins[$pluginMethodType]);
    }

    protected function assertDiFileIsLoaded(string $moduleName, string $diFile = 'etc/di.xml')
    {
        $this->assertModuleIsRegistered($moduleName);

        $modulesReader = $this->objectManager->get(ModuleDirReader::class);
        $configFiles = $modulesReader->getConfigurationFiles('di.xml');
        $this->assertNotEmpty($configFiles);

        $componentRegistrar = $this->objectManager->get(ComponentRegistrar::class);

        $modulePath = $componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $this->assertNotEmpty($modulePath, 'Module path is empty');

        $diFile = $modulePath . '/' . $diFile;

        $diXmlFound = false;
        foreach ($configFiles as $configFile) {
            if (strstr($configFile, $diFile)) {
                $diXmlFound = true;
                break;
            }
        }

        $this->assertTrue($diXmlFound, 'File "' . $diFile . '" has not been loaded by Magento');
    }
}
