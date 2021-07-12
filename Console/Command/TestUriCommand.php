<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\NextGenImages\Image\File as ImageFile;

class TestUriCommand extends Command
{
    /**
     * @var ImageFile
     */
    private $imageFile;

    /**
     * TestUriCommand constructor.
     * @param ImageFile $imageFile
     * @param string|null $name
     */
    public function __construct(
        ImageFile $imageFile,
        string $name = null
    ) {
        parent::__construct($name);
        $this->imageFile = $imageFile;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('next-gen-images:test-uri')
            ->setDescription('Test URI with convertors')
            ->addArgument('uri', InputArgument::REQUIRED, 'URI');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $uri = (string)$input->getArgument('uri');
        $path = $this->imageFile->resolve($uri);

        $output->writeln('Source path: ' . $path);
        $output->writeln('Source exists: ' . (int)$this->imageFile->uriExists($uri));
        $output->writeln('Source modification time: '.date('r', $this->imageFile->getModificationTime($path)));

        $targetUri = $this->imageFile->convertSuffix($uri, '.webp');
        $targetPath = $this->imageFile->resolve($targetUri);

        $output->writeln('Target path: ' . $targetPath);
        $output->writeln('Target exists: ' . (int)$this->imageFile->uriExists($targetUri));
        $output->writeln('Target modification time: '.date('r', $this->imageFile->getModificationTime($targetPath)));

        $output->writeln('Source is newer than target: '.(int)$this->imageFile->isNewerThan($path, $targetPath));
        return -1;
    }
}
