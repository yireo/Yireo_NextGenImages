<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Asset\File\NotFoundException as AssetNotFoundException;
use Magento\Framework\Image;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Convertor\ConvertorListing;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Logger\Debugger;

class ConvertAfterImageSave
{
    /**
     * @var ConvertorListing
     */
    private $convertorListing;

    /**
     * @var Debugger
     */
    private $debugger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @param ConvertorListing $convertorListing
     * @param Debugger $debugger
     * @param Config $config
     * @param ImageFactory $imageFactory
     */
    public function __construct(
        ConvertorListing $convertorListing,
        Debugger $debugger,
        Config $config,
        ImageFactory $imageFactory
    ) {
        $this->convertorListing = $convertorListing;
        $this->debugger = $debugger;
        $this->config = $config;
        $this->imageFactory = $imageFactory;
    }

    /**
     * @param Image $subject
     * @param mixed $return
     * @param string|null $destination
     * @return mixed
     */
    public function afterSave(Image $subject, $return, $destination = null)
    {
        if (!$this->config->enabled()) {
            return $return;
        }

        if (!$this->config->convertImagesOnSave()) {
            return $return;
        }

        if (empty($destination)) {
            return $return;
        }

        try {
            $image = $this->imageFactory->createFromPath((string)$destination);
        } catch (AssetNotFoundException|NotFoundException|FileSystemException|NoSuchEntityException $e) {
            $this->debugger->debug($e->getMessage());
            return $return;
        }

        foreach ($this->convertorListing->getConvertors() as $convertor) {
            try {
                $convertor->convertImage($image);
            } catch (
                ConvertorException|AssetNotFoundException|NotFoundException|FileSystemException|NoSuchEntityException $e
            ) {
                $this->debugger->debug($e->getMessage());
            }
        }

        return $return;
    }
}
