<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Stub\RemoteStorage;

use Magento\Framework\Filesystem\Driver\File;

/**
 * Local folder that acts like a remote storage driver (like AwsS3): relative paths are resolved against
 * the storage root instead of against the current working directory.
 */
class SandboxDriver extends File
{
    private string $root;

    public function __construct(string $root)
    {
        parent::__construct();
        $this->root = rtrim($root, '/');
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    public function isExists($path)
    {
        return parent::isExists($this->resolve($path));
    }

    public function isFile($path)
    {
        return parent::isFile($this->resolve($path));
    }

    public function stat($path)
    {
        return parent::stat($this->resolve($path));
    }

    public function fileGetContents($path, $flag = null, $context = null)
    {
        return parent::fileGetContents($this->resolve($path), $flag, $context);
    }

    public function filePutContents($path, $content, $mode = null)
    {
        return parent::filePutContents($this->resolve($path), $content, $mode);
    }

    private function resolve(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return $this->root.'/'.$path;
    }
}
