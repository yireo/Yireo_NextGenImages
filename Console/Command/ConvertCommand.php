<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\NextGenImages\Convertor\ConvertorListing;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\ImageFactory;

class ConvertCommand extends Command
{
    /**
     * @var ConvertorListing
     */
    private $convertorListing;

    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * TestUriCommand constructor.
     * @param ConvertorListing $convertorListing
     * @param ImageFactory $imageFactory
     * @param string|null $name
     */
    public function __construct(
        ConvertorListing $convertorListing,
        ImageFactory $imageFactory,
        string $name = null
    ) {
        parent::__construct($name);
        $this->convertorListing = $convertorListing;
        $this->imageFactory = $imageFactory;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('next-gen-images:convert')
            ->setDescription('Convert a specific image')
            ->addArgument('image', InputArgument::REQUIRED, 'Image');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // phpcs:ignore
        $imagePath = realpath((string)$input->getArgument('image'));

        // phpcs:ignore
        if ($imagePath === false || !is_file($imagePath)) {
            $output->writeln('<error>Please supply a valid image</error>');
            return -1;
        }

        foreach ($this->convertorListing->getConvertors() as $convertor) {
            try {
                $image = $this->imageFactory->createFromPath($imagePath);
                $newImage = $convertor->convertImage($image);
                $output->writeln('Converted image to ' . $newImage->getPath());
            } catch (ConvertorException $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }

        return -1;
    }
}
