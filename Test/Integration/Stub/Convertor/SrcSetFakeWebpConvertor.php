<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Stub\Convertor;

use Yireo\NextGenImages\Convertor\ConvertorInterface;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Util\File;

/**
 * Like \Yireo\Webp2\Convertor\Convertor::convertImage(), this convertor converts the srcset images as well,
 * skips images that are up to date and adds the converted srcset to the target image
 */
class SrcSetFakeWebpConvertor implements ConvertorInterface
{
    public static array $convertedPaths = [];

    private TargetImageFactory $targetImageFactory;
    private ImageFactory $imageFactory;
    private File $file;

    public function __construct(TargetImageFactory $targetImageFactory, ImageFactory $imageFactory, File $file)
    {
        $this->targetImageFactory = $targetImageFactory;
        $this->imageFactory = $imageFactory;
        $this->file = $file;
    }

    public static function reset(): void
    {
        self::$convertedPaths = [];
    }

    public function getFormat(): string
    {
        return 'webp';
    }

    public function convertImage(Image $image): Image
    {
        $targetImage = $this->convert($image);

        if (!empty($image->getSrcSet())) {
            $targetImage->setSrcSet($this->convertSrcSet($image->getSrcSet()));
        }

        return $targetImage;
    }

    private function convertSrcSet(string $srcSet): string
    {
        $targetSrcSet = [];
        foreach (explode(',', $srcSet) as $srcSetImage) {
            $pieces = explode(' ', trim($srcSetImage, " \f\n\r\t\v\x00"));
            $targetImage = $this->convert($this->imageFactory->createFromUrl($pieces[0]));
            $targetSrcSet[] = trim($targetImage->getUrl().' '.($pieces[1] ?? ''), " \f\n\r\t\v\x00");
        }

        return implode(', ', $targetSrcSet);
    }

    private function convert(Image $image): Image
    {
        $targetImage = $this->targetImageFactory->create($image, 'webp');
        if (!$this->file->needsConversion($image->getPath(), $targetImage->getPath())) {
            return $targetImage;
        }

        if (!file_exists($image->getPath())) {
            throw new ConvertorException('Source image "'.$image->getPath().'" does not exist locally');
        }

        file_put_contents($targetImage->getPath(), 'fake-webp:'.md5_file($image->getPath()));
        self::$convertedPaths[] = $image->getPath();

        return $targetImage;
    }
}
