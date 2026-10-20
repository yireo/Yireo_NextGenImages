<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Stub\Convertor;

use Yireo\NextGenImages\Convertor\ConvertorInterface;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\TargetImageFactory;

/**
 * Minimal convertor that works on local files only, like any real convertor using GD or cwebp
 */
class FakeWebpConvertor implements ConvertorInterface
{
    public static int $calls = 0;
    public static array $sourceExistedLocally = [];

    private TargetImageFactory $targetImageFactory;

    public function __construct(TargetImageFactory $targetImageFactory)
    {
        $this->targetImageFactory = $targetImageFactory;
    }

    public static function reset(): void
    {
        self::$calls = 0;
        self::$sourceExistedLocally = [];
    }

    public function getFormat(): string
    {
        return 'webp';
    }

    public function convertImage(Image $image): Image
    {
        self::$calls++;
        self::$sourceExistedLocally[] = file_exists($image->getPath());

        if (!file_exists($image->getPath())) {
            throw new ConvertorException('Source image "'.$image->getPath().'" does not exist locally');
        }

        $targetImage = $this->targetImageFactory->create($image, 'webp');
        file_put_contents($targetImage->getPath(), 'fake-webp:'.md5_file($image->getPath()));

        return $this->checkTargetImage($targetImage);
    }

    protected function checkTargetImage(Image $targetImage): Image
    {
        return $targetImage;
    }
}
