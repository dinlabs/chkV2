<?php
namespace App\Command;

use App\Entity\Channel\ChannelPricing;
use App\Entity\Chullanka\Parameter;
use App\Entity\Chullanka\Stock;
use App\Entity\Chullanka\Store;
use App\Entity\Product\ProductAttribute;
use App\Entity\Product\ProductAttributeValue;
use App\Entity\Taxation\TaxCategory;
use App\Repository\Chullanka\BrandRepository;
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

class GinkoiaCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'chullanka:ginkoia:import';
    
    protected $manager;
    protected $slugGenerator;
    protected $productFactory;
    protected $productVariantFactory;
    protected $channelPricingFactory;
    protected $brandFactory;
    protected $productRepository;
    protected $productVariantRepository;
    protected $channelRepository;
    protected $brandRepository;
    protected $output;
    
    
    // pour Ginkoia
    protected $logfilesDir;
    protected $_taxCategories = [];
    protected $_attributes = [];
    protected $_stores = [];
    protected $genre_ids = [];
    protected $cycle_vie_ids = [];
    protected $store_codes = [];
    protected $channel = [];
    protected $compteur = 0;
    
    public function __construct(EntityManagerInterface $manager, SlugGeneratorInterface $slugGenerator, ProductFactoryInterface $productFactory, ProductVariantFactoryInterface $productVariantFactory, FactoryInterface $channelPricingFactory, FactoryInterface $brandFactory, ProductRepositoryInterface $productRepository, ProductVariantRepositoryInterface $productVariantRepository, ChannelRepositoryInterface $channelRepository, BrandRepository $brandRepository)
    {
        parent::__construct();
        
        $this->manager = $manager;
        $this->slugGenerator = $slugGenerator;
        $this->productFactory = $productFactory;
        $this->productVariantFactory = $productVariantFactory;
        $this->channelPricingFactory = $channelPricingFactory;
        $this->brandFactory = $brandFactory;
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->channelRepository = $channelRepository;
        $this->brandRepository = $brandRepository;

        $this->logfilesDir = 'var/chkfiles/ginkoia/';
        if(!is_dir($this->logfilesDir)) mkdir($this->logfilesDir);
    }
    private function chkParameter($slug)
    {
        return $this->manager->getRepository(Parameter::class)->getValue($slug);
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
        $importPath = $this->chkParameter('ginkoia-path-import');
        
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
        $this->store_codes = array(
            '170000003' => 'antibes',
            '170005961' => 'metz',
            '170005231' => 'toulouse',
            '170005589' => 'bordeaux',
            '170006318' => 'comptoir', // Web
            '184556263' => 'aix-en-provence',
            '184557187' => 'briancon',
            '184557771' => 'gap',
            '184558355' => 'valence',
            '184558946' => 'montpellier'
        );

        // canal Chullanka
        $this->channel = $this->channelRepository->findOneByCode('default');

        // récupération des TaxCategories
        $taxCategories = $this->manager->getRepository(TaxCategory::class)->findAll();
        foreach($taxCategories as $tc)
        {
            $this->_taxCategories[ $tc->getCode() ] = $tc;
        }

        // récupération des Stores
        $stores = $this->manager->getRepository(Store::class)->findAll();
        foreach($stores as $store)
        {
            $this->_stores[ $store->getCode() ] = $store;
        }
        
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
        
        $files = scandir($importPath); // liste des fichiers dans le rep. d'import
        sort($files);
        if(!count($files))
        {
            $output->writeln('Aucun fichier dans '.$importPath);
        }
        
        // PROCESS FILE BY FILE
        for($i = 0; isset($files[$i]); $i++)
        {
            $return = false;
            
            if(in_array($files[$i], ['.','..','factures'])) continue;
            
            $tmpFile = $importPath . DIRECTORY_SEPARATOR . basename($files[$i]);
            $output->writeln('Fichier : ' . $tmpFile);
            
            // si le fichier existe déjà dans le dossier final, on le supprime et on passe au suivant
            if(file_exists($this->logfilesDir . DIRECTORY_SEPARATOR . basename($files[$i])))
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
                    $return = $this->manageStocks($tmpFile);
                }
                elseif((strpos($tmpFile, 'OC')>-1) && $doOc)
                {
                    $output->writeln('C\'est une Opération Commerciale');
                    $return = $this->managePromotions($tmpFile);
                }
                elseif((strpos($tmpFile, 'PRIX')>-1) && $doPrices)
                {
                    $return = $this->managePrices($tmpFile);
                }
                else 
                {
                    $output->writeln('==> fichier non traité');
                    //unlink($tmpFile);
                }
                
                
                if($return)
                {
                    // We move the tmp file in a logfiles dir
                    if(copy($tmpFile, $this->logfilesDir . DIRECTORY_SEPARATOR . basename($files[$i])))
                    {
                        unlink($tmpFile);
                    }
                    else
                    {
                        $output->writeln("ERREUR : le fichier ".$files[$i]." n'a pu etre deplace dans ginkoiafiles\n");
                        //$this->reportMsg[] = 'ERREUR : le fichier '.$files[$i].' n\'a pu etre deplace dans ginkoiafiles';
                    }
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
    private static function getSQLDate($dateDDMMYYYY)
    {
        $tmpArray = explode('/', $dateDDMMYYYY);
        //$date = $tmpArray[2] . '-' . $tmpArray[1] . '-' . $tmpArray[0];
        $date = implode('-', array_reverse($tmpArray));
        return $date;
    }

    /**
     * Conversion de JJ/MM/AAAA en Objet DateTime
     * @param string $dateDDMMYYYY
     * @return \DateTime
     */
    private static function getDateTime($dateDDMMYYYY)
    {
        $dateYYYYMMDD = self::getSQLDate($dateDDMMYYYY);
        return new \DateTime($dateYYYYMMDD);
    }

    /**
     * Conversion des prix textuels (aka: 123,45) en prix Sylius (12345)
     * @param string $priceInText
     * @return int
     */
    private static function getSyliusPrice($priceInText)
    {
        return (int)((float)str_replace(',', '.', $priceInText) * 100);
    }

    /**
     * Retrouve la marque via son nom
     * ou bien la crée d'abord si besoin
     */
    private function getMarque(string $marque)
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
    
    private function manageArticles($file)
    {
        $this->output->writeln(['', 'manageArticles ' . basename($file), '---------------', '']);

        // récupération des attributs
        $attributes = $this->manager->getRepository(ProductAttribute::class)->findAll();
        foreach($attributes as $attr)
        {
            $this->_attributes[ $attr->getCode() ] = $attr;
        }


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
    
    private function manageProduct($articles)
    {
        //https://docs.sylius.com/en/latest/book/products/products.html

        $article = $articles[0];
        $code_chrono = $article['CODE_CHRONO'];
        $name = $article['PRODUIT'];
        
        // cherche si le produit existe
        if($product = $this->productRepository->findOneByCode($code_chrono))
        {
            $this->output->writeln("Produit trouvé : ".$product->getName());
            //$this->output->writeln("marque : ".$product->getBrand()->getName());
            
            //$product->setName($name);
            //$this->manager->persist($product);
        }
        else
        {
            $this->output->writeln("Produit à créer : $code_chrono");
            $name .= ' ' . $article['CODE_CHRONO'];
            $product = $this->productFactory->createNew();
            $product->setCode($code_chrono);
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
        $product->setImportedData($article);
        

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
                $productVariant->setShippingRequired(true);
                $productVariant->setEnabled(false);
                $this->productVariantRepository->add($productVariant);
            }

            if($article['TVA'] == '20')
            {
                $taxCat = $this->_taxCategories['tva1'];
            }
            elseif($article['TVA'] == '5,5')
            {
                $taxCat = $this->_taxCategories['tva3'];
            }
            $productVariant->setTaxCategory($taxCat);
        }

        // attributes:
        $this->addOrUpdateAttrValue($product, 'code_ean', $article['CODE_EAN']);
        $this->addOrUpdateAttrValue($product, 'supplier_ref', $article['CODE_FOURN']);
        $this->addOrUpdateAttrValue($product, 'genre', $article['GENRE']);
        $this->addOrUpdateAttrValue($product, 'typologie', $article['CLASSEMENT1']);
        $this->addOrUpdateAttrValue($product, 'cycle_vie', $article['CLASSEMENT2']);
        //$this->addOrUpdateAttrValue($product, 'ginkoia_class3', $article['CLASSEMENT3']);
        $this->addOrUpdateAttrValue($product, 'annee', $article['CLASSEMENT4']);
        
        $this->manager->flush();
        return true;
    }

    private function addOrUpdateAttrValue($product, $attr, $value)
    {
        $this->output->writeln('addOrUpdateAttrValue');

        // recherche l'attribute
        if(isset($this->_attributes[ $attr ]) && ($attribute = $this->_attributes[ $attr ]))
        {
            //search
            $repo = $this->manager->getRepository(ProductAttributeValue::class);
            $attrValue = $repo->findOneBy([
                'subject' => $product,
                'attribute' => $attribute,
            ]);
            if(!$attrValue)
            {
                $attrValue = new ProductAttributeValue();
                $attrValue->setSubject($product);
                $attrValue->setAttribute($attribute);
            }
            
            switch($attribute->getType())
            {
                case 'select':
                    $conf = $attribute->getConfiguration();
                    $choices = $conf['choices'];
                    foreach($choices as $key => $choice)
                    {
                        if($choice['fr_FR'] == $value)
                        {
                            $attrValue->setValue([$key]);
                            break;
                        }
                    }
                    break;
                
                case 'checkbox':
                    if((bool)$value)
                        $attrValue->setValue( (bool)$value );
                    else 
                        return;
                    break;
            
                case 'integer':
                    if(strpos($attr, 'rank') == 0)
                    {
                        $value *= 100;
                    }
                    $attrValue->setValue( (int)$value );
                    break;
                
                case 'text':
                default:
                    $attrValue->setValue($value);
            }

            $this->manager->persist($attrValue);
        }
    }

    private function managePrices($file)
    {
        $this->output->writeln(['', 'managePrices ' . basename($file), '---------------', '']);

		$prices = self::csvToArray($file);
		foreach($prices as $artSite)
		{
            $this->output->writeln($artSite['CODE_ARTICLE']);
            if($productVariant = $this->productVariantRepository->findOneByCode($artSite['CODE_ARTICLE']))
			{
                $this->output->writeln($productVariant->getId() . ' : ' . $artSite['PXVTE']);

                if($artSite['TVA'] == '20')
                {
                    $taxCat = $this->_taxCategories['tva1'];
                }
                elseif($artSite['TVA'] == '5,5')
                {
                    $taxCat = $this->_taxCategories['tva3'];
                }
                $productVariant->setTaxCategory($taxCat);

                if($priceChannels = $productVariant->getChannelPricings())
                {
                    if(count($priceChannels) == 0)
                    {
                        $priceChannel = $this->channelPricingFactory->createNew();
                        $priceChannel->setChannelCode('default');
                        $priceChannel->setProductVariant($productVariant);
                        $productVariant->addChannelPricing($priceChannel);
                    }
                    else
                    {
                        $priceChannel = $priceChannels->first();
                    }
                    $price = self::getSyliusPrice($artSite['PXVTE']);
                    $priceChannel->setOriginalPrice($price);
                    $priceChannel->setPrice($price);
                    $this->manager->persist($priceChannel);
                }
            }
		}

        $this->manager->flush();
		return true;
    }

    private function managePromotions($file)
	{
        $this->output->writeln(['', 'managePromotions ' . basename($file), '---------------', '']);

        $now = new \DateTime();
		$promos = self::csvToArray($file);
		
		//echo  "Nombre d'articles : " . count($promos) . "\n";
		
		$count = 0;
		foreach($promos as $artSite)
		{			
    		$count++;
    		if(($count%50) == 0)
    		{
    			//echo "--------------------------------------------------articles passés : $count\n";
    		}
    		
            //$artSite['']

    		if($productVariant = $this->productVariantRepository->findOneByCode($artSite['CODE_ARTICLE']))
    		{
    			$this->output->writeln("promo '{$artSite['NOM_OC']}' pour " . $productVariant->getId());

                if($priceChannels = $productVariant->getChannelPricings())
                {
                    if(count($priceChannels) == 0)
                    {
                        $priceChannel = $this->channelPricingFactory->createNew();
                        $priceChannel->setChannelCode('default');
                        $priceChannel->setProductVariant($productVariant);
                        $productVariant->addChannelPricing($priceChannel);
                    }
                    else
                    {
                        $priceChannel = $priceChannels->first();
                    }
                    
                    $specialPrice = self::getSyliusPrice($artSite['PRIX_ARTICLE']);
                    $priceChannel->setDiscountPrice($specialPrice);

                    if(isset($artSite['DATE_DEBUT']))
                    {
                        $specialDateFrom = self::getDateTime($artSite['DATE_DEBUT']);
                        $priceChannel->setDiscountFrom($specialDateFrom);
                    }
                    if(isset($artSite['DATE_FIN']))
                    {
                        $specialDateTo = self::getSQLDate($artSite['DATE_FIN']);
                        $specialDateTo = new \DateTime($specialDateTo);
                        $priceChannel->setDiscountTo($specialDateTo);
                    }

                    // test s'il faut changer le prix aujourd'hui
                    if(!empty($priceChannel->getDiscountPrice())
                        && !empty($priceChannel->getDiscountFrom())
                        && !empty($priceChannel->getDiscountTo())
                        && ($now >= $priceChannel->getDiscountFrom())
                        && ($now < $priceChannel->getDiscountTo())
                        )
                    {
                        $priceChannel->setPrice( $priceChannel->getDiscountPrice() );
                    }
                    else
                    {
                        $priceChannel->setPrice( $priceChannel->getOriginalPrice() );
                    }

                    $this->manager->persist($priceChannel);
                }
    		}
		}
		
        $this->manager->flush();
		return true;
	}

    private function manageStocks($file)
	{
        $this->output->writeln(['', 'manageStocks ' . basename($file), '---------------', '']);

        $stock = self::csvToArray($file);
	    foreach($stock as $artSite)
	    {
	        $this->output->writeln("Sku : " . $artSite['CODE_ARTICLE']);

            //$this->store_codes
            if($productVariant = $this->productVariantRepository->findOneByCode($artSite['CODE_ARTICLE']))
    		{
                $qty = (int)$artSite['QTE_STOCK'];
                $storeCode = $this->store_codes[ (int)$artSite['MAG_ID'] ];
                $this->output->writeln("Store $storeCode : $qty");

                if($storeCode == 'comptoir')
                {
                    $productVariant->setOnHand($qty);
                }
                else
                {
                    $store = $this->_stores[ $storeCode ];

                    $storeFound = false;
                    $stocks = $productVariant->getStocks();
                    if(count($stocks))
                    {
                        foreach($stocks as $stock)
                        {
                            if($storeFound) continue;
                            if($stock->getStore() == $store)
                            {
                                $storeFound = true;
                                $stock->setOnHand($qty);
                                $this->manager->persist($stock);
                                continue;
                            }
                        }
                    }
                    if(!$storeFound)
                    {
                        $stock = new Stock();
                        $stock->setVariant($productVariant);
                        $stock->setStore($store);
                        $stock->setOnHand($qty);
                        $this->manager->persist($stock);
                    }
                }
	        }
	    }

        $this->manager->flush();
	    return true;
	}
}