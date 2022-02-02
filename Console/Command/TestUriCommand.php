<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\NextGenImages\Util\File as FileUtil;

class TestUriCommand extends Command
{
    /**
     * @var FileUtil
     */
    private $fileUtil;

    /**
     * TestUriCommand constructor.
     * @param FileUtil $fileUtil
     * @param string|null $name
     */
    public function __construct(
        FileUtil $fileUtil,
        string $name = null
    ) {
        parent::__construct($name);
        $this->fileUtil = $fileUtil;
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
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $uri = (string)$input->getArgument('uri');
        $path = $this->fileUtil->resolve($uri);

        $output->writeln('Source path: ' . $path);
        $output->writeln('Source exists: ' . (int)$this->fileUtil->uriExists($uri));
        $output->writeln('Source modification time: '.date('r', $this->fileUtil->getModificationTime($path)));

        $targetUri = $this->fileUtil->convertSuffix($uri, '.webp');
        $targetPath = $this->fileUtil->resolve($targetUri);

        $output->writeln('Target path: ' . $targetPath);
        $output->writeln('Target exists: ' . (int)$this->fileUtil->uriExists($targetUri));
        $output->writeln('Target modification time: '.date('r', $this->fileUtil->getModificationTime($targetPath)));

        $output->writeln('Source is newer than target: '.(int)$this->fileUtil->isNewerThan($path, $targetPath));
        return -1;
    }
}
