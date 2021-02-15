<?php

declare(strict_types=1);

namespace Yireo\NextGenImages\Image;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\ReadFactory as DirectoryReadFactory;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Yireo\NextGenImages\Logger\Debugger;

class File
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var DirectoryReadFactory
     */
    private $directoryReadFactory;

    /**
     * @var FileDriver
     */
    private $fileDriver;
    /**
     * @var Debugger
     */
    private $debugger;

    /**
     * File constructor.
     *
     * @param DirectoryList $directoryList
     * @param DirectoryReadFactory $directoryReadFactory
     * @param FileDriver $fileDriver
     * @param Debugger $debugger
     */
    public function __construct(
        DirectoryList $directoryList,
        DirectoryReadFactory $directoryReadFactory,
        FileDriver $fileDriver,
        Debugger $debugger
    ) {
        $this->directoryList = $directoryList;
        $this->directoryReadFactory = $directoryReadFactory;
        $this->fileDriver = $fileDriver;
        $this->debugger = $debugger;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function resolve(string $url): string
    {
        // phpcs:disable Magento2.Functions.DiscouragedFunction
        $parsedUrl = parse_url($url);
        if (!$parsedUrl) {
            return '';
        }

        $path = $parsedUrl['path'];
        $path = preg_replace('/^\/pub\//', '/', (string)$path);
        $path = preg_replace('/\/static\/version([0-9]+\/)/', '/static/', (string)$path);
        $path = $this->getAbsolutePathFromImagePath((string)$path);

        return $path;
    }

    /**
     * @param string $sourceFilename
     *
     * @return string
     */
    public function convertSuffix(string $sourceFilename, string $destinationSuffix): string
    {
        return (string)preg_replace('/\.(jpg|jpeg|png)/i', $destinationSuffix, $sourceFilename);
    }

    /**
     * @param string $imagePath
     *
     * @return string
     */
    public function getAbsolutePathFromImagePath(string $imagePath): string
    {
        return $this->directoryList->getRoot() . '/pub' . $imagePath;
    }

    /**
     * @param string $filePath
     *
     * @return int
     */
    public function getModificationTime(string $filePath): int
    {
        try {
            $stat = $this->fileDriver->stat($filePath);
            return (isset($stat['mtime'])) ? (int)$stat['mtime'] : $stat['ctime'];
        } catch (FileSystemException $e) {
            $this->debugger->debug($e->getMessage(), ['filePath' => $filePath]);
            return 0;
        }
    }

    /**
     * @param string $targetFile
     * @param string $comparisonFile
     *
     * @return bool
     */
    public function isNewerThan(string $targetFile, string $comparisonFile): bool
    {
        $targetFileModificationTime = $this->getModificationTime($targetFile);
        if ($targetFileModificationTime === 0) {
            return false;
        }

        $comparisonFileModificationTime = $this->getModificationTime($comparisonFile);
        if ($comparisonFileModificationTime === 0) {
            return true;
        }

        if ($targetFileModificationTime > $comparisonFileModificationTime) {
            return true;
        }

        return false;
    }
}
