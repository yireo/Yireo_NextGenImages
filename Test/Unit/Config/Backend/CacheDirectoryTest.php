<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Unit\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\NextGenImages\Config\Backend\CacheDirectory;

class CacheDirectoryTest extends TestCase
{
    /**
     * @dataProvider provideValidValues
     */
    #[DataProvider('provideValidValues')]
    public function testBeforeSaveAcceptsValidValues(string $value, string $expectedValue)
    {
        $cacheDirectory = $this->getCacheDirectory();
        $cacheDirectory->setValue($value);
        $cacheDirectory->beforeSave();

        $this->assertSame($expectedValue, $cacheDirectory->getValue());
    }

    public static function provideValidValues(): array
    {
        return [
            'default' => ['media/nextgenimages/', 'media/nextgenimages/'],
            'without trailing slash' => ['media/nextgenimages', 'media/nextgenimages'],
            'nested' => ['media/cache/next_gen-images/', 'media/cache/next_gen-images/'],
            'surrounding whitespace' => ["  media/nextgenimages/\n", 'media/nextgenimages/'],
            'empty falls back to default' => ['', ''],
        ];
    }

    /**
     * @dataProvider provideInvalidValues
     */
    #[DataProvider('provideInvalidValues')]
    public function testBeforeSaveRejectsInvalidValues(string $value)
    {
        $this->expectException(ValidatorException::class);

        $cacheDirectory = $this->getCacheDirectory();
        $cacheDirectory->setValue($value);
        $cacheDirectory->beforeSave();
    }

    public static function provideInvalidValues(): array
    {
        return [
            'parent traversal' => ['../x'],
            'traversal inside media' => ['media/../app'],
            'absolute path' => ['/etc'],
            'whitespace in path' => ['media/a b'],
            'media only' => ['media/'],
            'outside media' => ['static/cache'],
            'dot segment' => ['media/./cache'],
            'backslash traversal' => ['media/..\\app'],
            'null byte' => ["media/cache\x00/x"],
            'newline' => ["media/cache\n/x"],
            'media without slash' => ['media'],
            'empty trailing segment' => ['media//'],
            'empty segment in the middle' => ['media/a//b'],
            'double trailing slash' => ['media/a//'],
            'media prefix of other folder' => ['mediafoo/x'],
        ];
    }

    private function getCacheDirectory(): CacheDirectory
    {
        $context = $this->createMock(Context::class);
        $context->method('getEventDispatcher')->willReturn($this->createMock(ManagerInterface::class));

        return new CacheDirectory(
            $context,
            $this->createMock(Registry::class),
            $this->createMock(ScopeConfigInterface::class),
            $this->createMock(TypeListInterface::class)
        );
    }
}
