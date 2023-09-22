<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Functional;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ValueFactory as ConfigValueFactory;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Yireo\NextGenImages\Config\Source\TargetDirectory;
use Yireo\NextGenImages\Convertor\ConvertorInterface;
use Yireo\NextGenImages\Convertor\ConvertorListing;

class AbstractTestCase extends TestCase
{
    /**
     * @return ObjectManager
     */
    protected function getObjectManager(): ObjectManager
    {
        return ObjectManager::getInstance();
    }

    /**
     * @param string $className
     * @return mixed
     */
    protected function getObject(string $className)
    {
        return $this->getObjectManager()->get($className);
    }

    /**
     * @return ConvertorInterface[]
     */
    protected function getConvertors(): array
    {
        return $this->getObject(ConvertorListing::class)->getConvertors();
    }

    protected function getScopeConfig(): ScopeConfigInterface
    {
        return $this->getObject(ScopeConfigInterface::class);
    }

    protected function getDirectoryList(): DirectoryList
    {
        return $this->getObject(DirectoryList::class);
    }

    protected function getStoreManager(): StoreManagerInterface
    {
        return $this->getObject(StoreManagerInterface::class);
    }

    protected function getBaseUrl(string $type = ''): string
    {
        /** @var Store $store */
        $store = $this->getStoreManager()->getStore();
        return $store->getBaseUrl($type);
    }

    protected function getMediaUrl(): string
    {
        return $this->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    protected function getStaticUrl(): string
    {
        return $this->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
    }
}
