<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\RemoteStorage;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Interception\PluginList\PluginList;
use Magento\Framework\Interception\PluginListInterface;
use ReflectionProperty;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;
use Yireo\NextGenImages\Plugin\Convertor\ConvertorInterface as ConvertorInterfacePlugin;
use Yireo\NextGenImages\Plugin\Util\File as FilePlugin;
use Yireo\NextGenImages\Plugin\Util\UrlConvertor as UrlConvertorPlugin;
use Yireo\NextGenImages\Test\Integration\Stub\Convertor\FakeWebpConvertor;
use Yireo\NextGenImages\Test\Integration\Stub\RemoteStorage\EnabledRemoteStorageConfig;
use Yireo\NextGenImages\Test\Integration\Stub\RemoteStorage\SandboxDriver;
use Yireo\NextGenImages\Test\Integration\Stub\RemoteStorage\SandboxTargetDirectory;
use Yireo\NextGenImages\Util\TmpFileCopier;

/**
 * Simulates remote storage (like S3) with a local sandbox folder that is not part of the Magento filesystem.
 * Images are added to the sandbox only, just like with a real remote storage.
 */
abstract class AbstractRemoteStorageTestCase extends AbstractTestCase
{
    protected const TEST_FOLDER = 'nextgenimages-remote-storage-test';

    protected string $remoteRoot;
    protected string $tmpRoot;
    protected ?TmpFileCopier $tmpFileCopier = null;

    private array $sharedInstances = [];

    protected function setUp(): void
    {
        parent::setUp();
        FakeWebpConvertor::reset();

        $directoryList = $this->objectManager->get(DirectoryList::class);
        $this->remoteRoot = $directoryList->getPath(DirectoryList::VAR_DIR).'/nextgenimages-remote-sandbox';
        $this->tmpRoot = $directoryList->getPath(DirectoryList::TMP);

        $this->removeTestFiles();
        mkdir($this->remoteRoot.'/media/'.self::TEST_FOLDER, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach ($this->sharedInstances as $className) {
            $this->objectManager->removeSharedInstance($className);
        }

        $this->sharedInstances = [];
        $this->tmpFileCopier = null;
        $this->resetPluginInstances();
        $this->removeTestFiles();
        FakeWebpConvertor::reset();

        parent::tearDown();
    }

    protected function enableRemoteStorage(): void
    {
        $remoteStorageConfig = $this->objectManager->create(EnabledRemoteStorageConfig::class);
        $targetDirectory = $this->objectManager->create(SandboxTargetDirectory::class, [
            'driver' => new SandboxDriver($this->remoteRoot),
        ]);

        $arguments = [
            'remoteStorageConfig' => $remoteStorageConfig,
            'targetDirectory' => $targetDirectory,
        ];

        $this->tmpFileCopier = $this->objectManager->create(TmpFileCopier::class, $arguments);
        $this->addSharedInstance($this->tmpFileCopier, TmpFileCopier::class);
        $this->addSharedInstance($this->objectManager->create(FilePlugin::class, $arguments), FilePlugin::class);
        $this->addSharedInstance(
            $this->objectManager->create(UrlConvertorPlugin::class, ['remoteStorageConfig' => $remoteStorageConfig]),
            UrlConvertorPlugin::class
        );
        $this->addSharedInstance(
            $this->objectManager->create(
                ConvertorInterfacePlugin::class,
                $arguments + ['tmpFileCopier' => $this->tmpFileCopier]
            ),
            ConvertorInterfacePlugin::class
        );

        $this->resetPluginInstances();
    }

    protected function addRemoteImage(string $filename, int $age = 100): string
    {
        $relativePath = 'media/'.self::TEST_FOLDER.'/'.$filename;
        $remotePath = $this->remoteRoot.'/'.$relativePath;
        copy(__DIR__.'/../fixtures/images/'.$filename, $remotePath);
        touch($remotePath, time() - $age);

        return $relativePath;
    }

    protected function getTmpPath(string $relativePath): string
    {
        return $this->tmpRoot.'/'.$relativePath;
    }

    protected function getRemotePath(string $relativePath): string
    {
        return $this->remoteRoot.'/'.$relativePath;
    }

    private function addSharedInstance(object $instance, string $className): void
    {
        $this->objectManager->addSharedInstance($instance, $className);
        $this->sharedInstances[] = $className;
    }

    /**
     * Plugin instances are cached by the PluginList, so they need to be reset to pick up the stubbed plugins
     */
    private function resetPluginInstances(): void
    {
        $pluginList = $this->objectManager->get(PluginListInterface::class);
        $property = new ReflectionProperty(PluginList::class, '_pluginInstances');
        $property->setValue($pluginList, []);
    }

    private function removeTestFiles(): void
    {
        $fileDriver = new FileDriver();
        foreach ([$this->remoteRoot, $this->tmpRoot.'/media/'.self::TEST_FOLDER] as $folder) {
            if (is_dir($folder)) {
                $fileDriver->deleteDirectory($folder);
            }
        }
    }
}
