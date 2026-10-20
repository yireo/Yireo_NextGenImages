<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Test\Integration\Stub\Convertor;

use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Util\File;

/**
 * Like \Yireo\Webp2\Convertor\Convertor::convertImage(), this convertor verifies that the target image exists
 * after conversion using \Yireo\NextGenImages\Util\File::fileExists()
 */
class Webp2LikeFakeConvertor extends FakeWebpConvertor
{
    private File $file;

    public function __construct(TargetImageFactory $targetImageFactory, File $file)
    {
        parent::__construct($targetImageFactory);
        $this->file = $file;
    }

    protected function checkTargetImage(Image $targetImage): Image
    {
        if (!$this->file->fileExists($targetImage->getPath())) {
            throw new ConvertorException('WebP path "'.$targetImage->getPath().'" does not exist after conversion');
        }

        return $targetImage;
    }
}
