<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration;

use Magento\Developer\Model\Di\PluginList;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\DeploymentConfig\Reader as DeploymentConfigReader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\ObjectManagerInterface;
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
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        //$this->objectManager = ObjectManager::getInstance();
    }

    protected function tearDown(): void
    {
        $this->setAreaCode(null);
    }

    protected function createObject(string $className)
    {
        return $this->objectManager->create($className, ['config' => $this->getConfig()]);
    }

    protected function setAreaCode($areaCode)
    {
        $applicationState = $this->objectManager->get(State::class);
        $applicationState->setAreaCode($areaCode);
    }

    protected function getConfig()
    {
        $directoryList = $this->objectManager->create(DirectoryList::class, ['root' => BP]);
        $configReader = $this->objectManager->create(DeploymentConfigReader::class, ['dirList' => $directoryList]);
        return $this->objectManager->create(DeploymentConfig::class, ['reader' => $configReader]);
    }

    protected function getModulePath(string $moduleName): string
    {
        $componentRegistrar = $this->createObject(ComponentRegistrar::class);

        $modulePaths = $componentRegistrar->getPaths(ComponentRegistrar::MODULE);
        $this->assertArrayHasKey($moduleName, $modulePaths);

        $modulePath = $componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $this->assertNotEmpty($modulePath, 'Module path is empty');

        return $modulePath;
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
        $this->setAreaCode('frontend');

        $this->assertModuleIsRegistered($moduleName);

        /** @var ModuleDirReader $modulesReader */
        $modulesReader = $this->createObject(ModuleDirReader::class);
        $configFiles = array_keys($modulesReader->getConfigurationFiles('di.xml')->toArray());
        $this->assertNotEmpty($configFiles);

        $diFile = $this->getModulePath($moduleName) . '/' . $diFile;

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
