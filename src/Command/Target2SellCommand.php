<?php
namespace App\Command;

use App\Entity\Channel\ChannelPricing;
use App\Repository\Chullanka\BrandRepository;
use App\Service\Target2SellHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

class Target2SellCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'chullanka:target2sell';
    
    protected $target2SellHelper;
    protected $output;
    
    public function __construct(Target2SellHelper $target2SellHelper)
    {
        parent::__construct();
        $this->target2SellHelper = $target2SellHelper;
    }
    
    protected function configure(): void
    {
        $this
            ->setDescription('Import et mise à jour du catalogue Chullanka depuis Target2Sell.')
            ->addArgument('cmdtype', InputArgument::OPTIONAL, 'import ou export')
            ->setHelp('Envoie du catalog à Target2Sell et récupération du poids de produits')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            '',
            '============================',
            'Target2Sell - imports/export',
            '============================',
            '',
        ]);
        $output->writeln('Début à ' . date('H:i:s d/m/Y'));
        
        $typeCmd = $input->getArgument('cmdtype');
        switch($typeCmd)
        {
            case 'import':
                $output->writeln('Import du poids des produits');
                $this->target2SellHelper->updateProductRanks();
            break;
                
            case 'export':
            default:
                $output->writeln('Export du catalogue des catégories et produits');
                $this->target2SellHelper->exportCatalog();
            break;
        }
        $output->writeln('Fin à ' . date('H:i:s d/m/Y'));
        
        return Command::SUCCESS;
    }
}