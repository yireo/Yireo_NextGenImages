<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\NextGenImages\Convertor\ConvertorListing;
use Yireo\NextGenImages\Exception\ConvertorException;

class ConvertCommand extends Command
{
    /**
     * @var ConvertorListing
     */
    private $convertorListing;

    /**
     * TestUriCommand constructor.
     * @param ConvertorListing $convertorListing
     */
    public function __construct(
        ConvertorListing $convertorListing,
        string $name = null
    ) {
        parent::__construct($name);
        $this->convertorListing = $convertorListing;
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
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $image = (string)$input->getArgument('image');

        foreach ($this->convertorListing->getConvertors() as $convertor) {
            try {
                $convertor->convert($image);
            } catch (ConvertorException $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }

        return -1;
    }
}
