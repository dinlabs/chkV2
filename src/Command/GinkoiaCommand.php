<?php
namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Loevgaard\SyliusBrandPlugin\Doctrine\ORM\BrandRepositoryInterface;
use Loevgaard\SyliusBrandPlugin\Model\BrandInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
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

class GinkoiaCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'chullanka:ginkoia:import';
    
    protected $manager;
    protected $slugGenerator;
    protected $productFactory;
    protected $productVariantFactory;
    protected $brandFactory;
    protected $productRepository;
    protected $productVariantRepository;
    protected $channelRepository;
    protected $brandRepository;
    protected $output;
    
    
    // pour Ginkoia
    protected $genre_ids = [];
    protected $cycle_vie_ids = [];
    protected $mag_ids = [];
    protected $channel = [];
    protected $compteur = 0;
    
    public function __construct(EntityManagerInterface $manager, SlugGeneratorInterface $slugGenerator, ProductFactoryInterface $productFactory, ProductVariantFactoryInterface $productVariantFactory, FactoryInterface $brandFactory, ProductRepositoryInterface $productRepository, ProductVariantRepositoryInterface $productVariantRepository, ChannelRepositoryInterface $channelRepository, BrandRepositoryInterface $brandRepository)
    {
        parent::__construct();
        
        $this->manager = $manager;
        $this->slugGenerator = $slugGenerator;
        $this->productFactory = $productFactory;
        $this->productVariantFactory = $productVariantFactory;
        $this->brandFactory = $brandFactory;
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->channelRepository = $channelRepository;
        $this->brandRepository = $brandRepository;
    }
    
    protected function configure(): void
    {
        $this
            ->setDescription('Import et mise à jour du catalogue Chullanka depuis Ginkoia.')
            ->addArgument('cmdtype', InputArgument::OPTIONAL, 'Type d\'import')
            ->setHelp('Cette commande récupère les fichiers envoyés par Ginkoia pour créer et mettre à jour les produits, les prix, les promotions et les stocks.')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $importPath = 'var/ginkoia';
        
        $this->output = $output;
        $output->writeln([
            '',
            '=================',
            'Ginkoia - imports',
            '=================',
            '',
        ]);
        $output->writeln('Début import à ' . date('H:i:s d/m/Y'));
        
        // correspondance du GENRE
        $this->genre_ids = array(
            '124' => 48, // Homme
            '126' => 47, // Femme
            '128' => 46, // Enfant
            '136' => 2230 // Unisexe
        );
        
        // correspondance du cycle de vie
        $this->cycle_vie_ids = array(
            'Nouveau'           => 1,
            'Vivant'            => 2,
            'Ventes-privées'    => 3,
            'Soldes'            => 4,
            'Fin de série'      => 5,
            'Sur-commande'      => 6,
            'radié'             => 7
        );
        
        // correspondance des MAG_ID
        $this->mag_ids = array(
            '170000003' => 1, // Antibes
            '170005961' => 2, // Metz
            '170005231' => 3, // Toulouse
            '170005589' => 4, // Bordeaux
            '170006318' => 5 // Web
        );

        // canal Chullanka
        $this->channel = $this->channelRepository->findOneByCode('default');
        
        $doArt = $doPrices = $doOc = $doStock = false;
        
        $typeCmd = $input->getArgument('cmdtype');
        switch($typeCmd)
        {
            case 'art':
                $output->writeln(['', 'Gestion des Produits uniquement', '-------------------------------']);
                $doArt = true;
                break;
            
            case 'prices':
                $output->writeln('Gestion des Prix uniquement');
                $doPrices = true;
                break;
            
            case 'oc':
                $output->writeln('Gestion des Promotions uniquement');
                $doOc = true;
                break;
            
            case 'stock':
                $output->writeln('Gestion des Stocks uniquement');
                $doStock = true;
                break;
            
            default:
                $output->writeln('Gestion de tous les types de fichiers');
                $doArt = $doPrices = $doOc = $doStock = true;
                break;
        }
        
        /*if(true)
        {
            $output->writeln('Traitements des fichiers Ginkoia');
            
            $this->manageProduct(null);
        }*/
        
        $files = scandir($importPath); // liste des fichiers dans le rep. d'import
        sort($files);
        if(!count($files))
        {
            $output->writeln('Aucun fichier dans '.$importPath);
        }
        
        $return = false;
        
        // PROCESS FILE BY FILE
        for($i = 0; isset($files[$i]); $i++)
        {
            if(in_array($files[$i], ['.','..','factures'])) continue;
            
            $tmpFile = $importPath . '/' . basename($files[$i]);
            
            $output->writeln('Fichier : ' . $tmpFile);
            
            // si le fichier existe déjà dans le dossier final, on le supprime et on passe au suivant
            //if(file_exists($this->logfilesDir . DS . basename($files[$i])))
            if(false)
            {
                unlink($tmpFile);
                continue;
            }
            else
            {
                if((strpos($tmpFile, 'ARTWEB')>-1) && $doArt)
                {
                    $return = $this->manageArticles($tmpFile);
                }
                elseif((strpos($tmpFile, 'STOCK')>-1) && $doStock)
                {
                    $output->writeln('C\'est du Stock');
                    //$return = $this->manageStocks($tmpFile);
                }
                elseif((strpos($tmpFile, 'OC')>-1) && $doOc)
                {
                    $output->writeln('C\'est une Opération Commerciale');
                    //$return = $this->managePromotions($tmpFile);
                }
                elseif((strpos($tmpFile, 'PRIX')>-1) && $doPrices)
                {
                    //$return = $this->managePrices($tmpFile);
                }
                else 
                {
                    $output->writeln('==> fichier non traité');
                    //unlink($tmpFile);
                }
                
                
                if($return)
                {
                    // We move the tmp file in a logfiles dir
                    /*if(copy($tmpFile, $this->logfilesDir . DS . basename($files[$i])))
                    {
                        unlink($tmpFile);
                    }
                    else
                    {
                        echo "ERREUR : le fichier ".$files[$i]." n'a pu etre deplace dans ginkoiafiles\n";
                        $this->reportMsg[] = 'ERREUR : le fichier '.$files[$i].' n\'a pu etre deplace dans ginkoiafiles';
                    }*/
                 }
                 else
                 {
                     $output->writeln('ERREUR du traitement du fichier ' . $files[$i]);
                     
                     //return Command::FAILURE;
                 }
            }
        }
        $output->writeln(['', 'Fin import à ' . date('H:i:s d/m/Y')]);
        
        
        
        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable
        
        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;
        
        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;
        
        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }
    
    /**
     * Convert Ginkoia TXT Files into Array
     * @param string $csvfile
     * @param string $delimiter
     * @return string[][]
     */
    private static function csvToArray($csvfile, $delimiter = ';')
    {
        $keys = $rows = [];
        if($fileArray = file($csvfile))
        {
            foreach($fileArray as $line)
            {
                if(empty(trim($line))) continue;
                $line = addslashes($line);// sécurité
                $data = str_getcsv($line, $delimiter);
                if(empty($keys)) { $keys = $data; }
                else
                {
                    $row = [];
                    for($c=0; $c<count($data); $c++)
                    {
                        $row[ $keys[$c] ] = utf8_encode($data[$c]);
                    }
                    $rows[] = $row;
                }
            }
        }
        return $rows;
    }
    
    /**
     * Formatting string for URL
     *
     * @param string $string
     * @return string
     */
    private static function generateUrl($string)
    {
        $string = preg_replace('#[^0-9a-z]+#i', '-', $string);
        $string = strtolower( $string);
        $string = trim($string, '-');
        return $string;
    }
    
    /**
     * Retourne une date JJ/MM/AAAA au format Linux YYYY-MM-DD
     * @param string $dateDDMMYYYY
     * @return string
     */
    private function getSQLDate($dateDDMMYYYY)
    {
        $tmpArray = explode('/', $dateDDMMYYYY);
        //$date = $tmpArray[2] . '-' . $tmpArray[1] . '-' . $tmpArray[0];
        $date = implode('-', array_reverse($tmpArray));
        return $date;
    }

    /**
     * Retrouve la marque via son nom
     * ou bien la crée d'abord si besoin
     */
    private function getMarque(string $marque): BrandInterface
    {
        $brand_code = $this->slugGenerator->generate($marque);

        // on cherche si le produit existe
        if($brand = $this->brandRepository->findOneByCode($brand_code))
        {
            $this->output->writeln("Marque trouvée : ".$brand->getName());
        }
        else
        {
            $this->output->writeln("Marque à créer : ".$marque);
            $brand = $this->brandFactory->createNew();
            $brand->setName($marque);
            $brand->setCode($brand_code);
            $this->manager->persist($brand);
        }
        return $brand;
    }
    
    public function manageArticles($file)
    {
        $this->output->writeln(['', 'manageArticles ' . basename($file), '---------------', '']);

        $articles = self::csvToArray($file);
        $_total = count($articles);
        $this->output->writeln('Nombre d\'articles : ' . $_total);
        if($_total <= 0) return true;

        // première boucle pour regrouper les articles par CODE_CHRONO
		$productsByCodeChrono = [];
		foreach($articles as $article)
		{
		    $productsByCodeChrono[ $article['CODE_CHRONO'] ][] = $article;
		}
        
        $return = false;
		$this->compteur = 0;
		// parcours des code_chrono
		foreach($productsByCodeChrono as $code_chrono => $articles)
		{
            $return = $this->manageProduct($articles);
            if(!$return)
            {
                $this->output->writeln("error import article au {$this->compteur} eme");
                return false;
            }
		}
		return $return;
    }
    
    public function manageProduct($articles)
    {
        //https://docs.sylius.com/en/latest/book/products/products.html

        $hasConf = (count($articles) > 1);
        $article = $articles[0];
        $art_uuid = $hasConf ? $article['CODE_CHRONO'] : $article['CODE_ARTICLE'];
        $name = $article['PRODUIT'];
        
        // cherche si le produit existe
        if($product = $this->productRepository->findOneByCode($art_uuid))
        {
            $this->output->writeln("Produit trouvé : ".$product->getName());
            $this->output->writeln("marque : ".$product->getBrand()->getName());
            
            //$product->setName($name);
            //$this->manager->persist($product);
        }
        else
        {
            $this->output->writeln("Produit à créer");
            $product = $this->productFactory->createNew();
            $product->setCode($art_uuid);
            $product->setName($name);
            $product->setSlug( $this->slugGenerator->generate($name) );
            $product->addChannel($this->channel);
            $product->setEnabled(false);

            if($brand = $this->getMarque( $article['MARQUE'] ))
            {
                $product->setBrand($brand);
            }
            $this->productRepository->add($product);
        }

        // Variante(s)
        foreach($articles as $article)
        {
            $this->compteur++;
            if(($this->compteur%50) == 0)
            {
                $this->output->writeln(['', 'articles passés : ' . $this->compteur]);
            }
            
            $art_uuid = $article['CODE_ARTICLE'];

            // cherche si la variante existe
            if($productVariant = $this->productVariantRepository->findOneByCode($art_uuid))
            {
                $this->output->writeln("Variante trouvée : ".$productVariant->getName());
            }
            else 
            {
                $this->output->writeln('On créé la variante');
                $productVariant = $this->productVariantFactory->createNew();
                $productVariant->setProduct($product);
                $productVariant->setCode($art_uuid);
                $productVariant->setName($name . ' - ' . $article['TAILLE'] . ' - ' . $article['COULEUR']);
                $productVariant->setEnabled(false);
                $this->productVariantRepository->add($productVariant);
            }
        }

        //$this->manager->flush();
        return true;
    }
}