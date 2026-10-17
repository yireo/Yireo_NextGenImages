<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Stub\RemoteStorage;

use Magento\RemoteStorage\Model\Config;

class EnabledRemoteStorageConfig extends Config
{
    public function isEnabled(): bool
    {
        return true;
    }
}
