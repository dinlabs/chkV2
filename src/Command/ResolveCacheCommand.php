<?php

/**
 * Auteur : Yannick Lepetit - Juillet 2022
 * Détournement de la commande 'liip:imagine:cache:resolve'
 * pour générer les miniatures d'images en masse
 */

namespace App\Command;

use App\Entity\Product\ProductImage;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Command\CacheCommandTrait;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResolveCacheCommand extends Command
{
    use CacheCommandTrait;
    protected static $defaultName = 'chullanka:imagine:cache:resolve';

    /**
     * @var DataManager
     */
    private $dataManager;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(DataManager $dataManager, CacheManager $cacheManager, FilterManager $filterManager, EntityManagerInterface $manager)
    {
        parent::__construct();

        $this->dataManager = $dataManager;
        $this->cacheManager = $cacheManager;
        $this->filterManager = $filterManager;
        $this->manager = $manager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Genère toutes les miniatures des images des produits')
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Filter(s) to use for image resolution; if none explicitly passed, use all filters.')
            ->addOption('force', 'F', InputOption::VALUE_NONE,
                'Force generating the image and writing the cache, regardless of whether a cached version already exists.')
            ->addOption('no-colors', 'C', InputOption::VALUE_NONE,
                'Write only un-styled text output; remove any colors, styling, etc.')
            ->addOption('as-script', 'S', InputOption::VALUE_NONE,
                'Write only machine-readable output; silenced verbose reporting and implies --no-colors.')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupOutputStyle($input, $output);
        $this->outputCommandHeader();

        $forced = $input->getOption('force');
        $filters = $this->normalizeFilterList($input->getOption('filter'));

        $images = [];
        $productImages = $this->manager->getRepository(ProductImage::class)->findAll();
        foreach($productImages as $productImage)
        {
            $images[] = $productImage->getPath();
        }

        foreach ($images as $i) {
            foreach ($filters as $f) {
                $this->runCacheImageResolve($i, $f, $forced);
            }
        }

        $this->outputCommandResult($images, $filters, 'resolution');

        return $this->getResultCode();
    }

    private function runCacheImageResolve(string $image, string $filter, bool $forced): void
    {
        if (!$this->outputMachineReadable) {
            $this->io->text(' - ');
        }

        $this->io->group($image, $filter, 'blue');

        try {
            if ($forced || !$this->cacheManager->isStored($image, $filter)) {
                $this->cacheManager->store($this->filterManager->applyFilter($this->dataManager->find($filter, $image), $filter), $image, $filter);
                $this->io->status('resolved', 'green');
            } else {
                $this->io->status('cached', 'white');
            }

            $this->io->line(sprintf(' %s', $this->cacheManager->resolve($image, $filter)));
        } catch (\Exception $e) {
            ++$this->failures;

            $this->io->status('failed', 'red');
            $this->io->line(' %s', [$e->getMessage()]);
        }
    }
}
