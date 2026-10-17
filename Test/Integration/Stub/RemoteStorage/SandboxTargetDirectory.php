<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Stub\RemoteStorage;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Read;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Filesystem\File\WriteFactory;

/**
 * Replaces the remote storage (like S3) with a local sandbox folder
 */
class SandboxTargetDirectory extends TargetDirectory
{
    private SandboxDriver $driver;
    private ReadFactory $readFactory;
    private WriteFactory $writeFactory;

    public function __construct(
        Filesystem $filesystem,
        ReadFactory $readFactory,
        WriteFactory $writeFactory,
        SandboxDriver $driver
    ) {
        parent::__construct($filesystem);
        $this->driver = $driver;
        $this->readFactory = $readFactory;
        $this->writeFactory = $writeFactory;
    }

    public function getDirectoryWrite(string $directoryCode): WriteInterface
    {
        return new Write($this->writeFactory, $this->driver, $this->driver->getRoot());
    }

    public function getDirectoryRead(string $directoryCode): ReadInterface
    {
        return new Read($this->readFactory, $this->driver, $this->driver->getRoot());
    }
}
