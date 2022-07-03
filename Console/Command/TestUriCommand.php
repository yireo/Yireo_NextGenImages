<?php declare(strict_types=1);

namespace Yireo\NextGenImages\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Util\File as FileUtil;

class TestUriCommand extends Command
{
    /**
     * @var FileUtil
     */
    private $fileUtil;
    
    /**
     * @var TargetImageFactory
     */
    private $targetImageFactory;
    /**
     * @var ImageFactory
     */
    private $imageFactory;
    
    /**
     * TestUriCommand constructor.
     * @param FileUtil $fileUtil
     * @param TargetImageFactory $targetImageFactory
     * @param ImageFactory $imageFactory
     * @param string|null $name
     */
    public function __construct(
        FileUtil $fileUtil,
        TargetImageFactory $targetImageFactory,
        ImageFactory $imageFactory,
        string $name = null
    ) {
        parent::__construct($name);
        $this->fileUtil = $fileUtil;
        $this->targetImageFactory = $targetImageFactory;
        $this->imageFactory = $imageFactory;
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

        $rows = [];
        $rows[] = ['Source path', $path];
        $rows[] = ['Source exists', $this->getYesNo($this->fileUtil->uriExists($uri))];
        $rows[] = ['Source modification time', date('r', $this->fileUtil->getModificationTime($path))];

        $image = $this->imageFactory->createFromUrl($uri);
        $targetImage = $this->targetImageFactory->create($image, 'webp');
        $targetUri = $targetImage->getUrl();
        $targetPath = $this->fileUtil->resolve($targetUri);
    
        $rows[] = ['Target path', $targetPath];
        $rows[] = ['Target exists', $this->getYesNo($this->fileUtil->uriExists($targetUri))];
        $modificationTime = $this->fileUtil->getModificationTime($targetPath);
        $modificationTimeFormatted = $modificationTime ? date('r', $modificationTime) : 'n/a';
        $rows[] = ['Target modification time', $modificationTimeFormatted];
        
        $rows[] = ['Source is newer than target', $this->getYesNo($this->fileUtil->isNewerThan($path, $targetPath))];
    
        $table = new Table($output);
        $table->setHeaders(['Check', 'Outcome']);
        $table->setRows($rows);
        $table->render();
    
        return -1;
    }
    
    /**
     * @param bool $value
     * @return string
     */
    private function getYesNo(bool $value): string
    {
        return ($value) ? (string)__('Yes') : (string)__('No');
    }
}
