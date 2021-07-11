<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Plugin;

use Magento\Framework\Image;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Convertor\ConvertorListing;
use Yireo\NextGenImages\Exception\ConvertorException;
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
     * ConvertAfterImageSave constructor.
     * @param ConvertorListing $convertorListing
     * @param Debugger $debugger
     * @param Config $config
     */
    public function __construct(
        ConvertorListing $convertorListing,
        Debugger $debugger,
        Config $config
    ) {
        $this->convertorListing = $convertorListing;
        $this->debugger = $debugger;
        $this->config = $config;
    }

    /**
     * @param Image $subject
     * @param $return
     * @param null $destination
     * @param null $newFileName
     * @return void
     */
    public function afterSave(Image $subject, $return, $destination = null, $newFileName = null)
    {
        if (!$this->config->enabled()) {
            return $return;
        }

        if (!$this->config->convertImagesOnSave()) {
            return $return;
        }

        foreach ($this->convertorListing->getConvertors() as $convertor) {
            try {
                $convertor->convert((string)$destination);
            } catch (ConvertorException $e) {
                $this->debugger->debug($e->getMessage());
            }
        }

        return $return;
    }
}
