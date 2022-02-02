<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

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
    private ImageFactory $imageFactory;

    /**
     * ConvertAfterImageSave constructor.
     * @param ConvertorListing $convertorListing
     * @param Debugger $debugger
     * @param Config $config
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
     * @param null $destination
     * @return void
     */
    public function afterSave(Image $subject, $return, $destination = null)
    {
        if (!$this->config->enabled()) {
            return;
        }

        if (!$this->config->convertImagesOnSave()) {
            return;
        }

        $image = $this->imageFactory->createFromPath((string)$destination);
        foreach ($this->convertorListing->getConvertors() as $convertor) {
            try {
                $convertor->convertImage($image);
            } catch (ConvertorException $e) {
                $this->debugger->debug($e->getMessage());
            }
        }
    }
}
