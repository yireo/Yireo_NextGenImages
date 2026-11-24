<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Config;

use Magento\Framework\Acl\AclResource\ProviderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Yireo\NextGenImages\Test\Integration\AbstractTestCase;

/**
 * @magentoAppArea adminhtml
 */
class AclTest extends AbstractTestCase
{
    public function testIfAclResourceExists(): void
    {
        $provider = Bootstrap::getObjectManager()->get(ProviderInterface::class);
        $path = $this->findResourcePath($provider->getAclResources(), 'Yireo_NextGenImages::config');

        $this->assertSame([
            'Magento_Backend::admin',
            'Magento_Backend::stores',
            'Magento_Backend::stores_settings',
            'Magento_Config::config',
            'Yireo_NextGenImages::config',
        ], $path);
    }

    private function findResourcePath(array $resources, string $resourceId): array
    {
        foreach ($resources as $resource) {
            if ($resource['id'] === $resourceId) {
                return [$resourceId];
            }

            $childPath = $this->findResourcePath($resource['children'] ?? [], $resourceId);
            if ($childPath !== []) {
                return array_merge([$resource['id']], $childPath);
            }
        }

        return [];
    }
}
