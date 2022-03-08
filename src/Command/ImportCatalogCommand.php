<?php
namespace App\Command;

use App\Entity\Chullanka\Brand;
use App\Entity\Chullanka\MagentoProduct;
use App\Entity\Chullanka\Stock;
use App\Entity\Chullanka\Store;
use App\Entity\Product\ProductAttribute;
use App\Entity\Product\ProductAttributeValue;
use App\Entity\Product\ProductImage;
use App\Entity\Product\ProductOption;
use App\Entity\Product\ProductTranslation;
use App\Entity\Taxation\TaxCategory;
use App\Entity\Taxonomy\Taxon;
use App\Repository\Chullanka\BrandRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Symfony\Component\Console\Input\InputArgument;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\File\File;

class ImportCatalogCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'chullanka:import:catalog';
    
    protected $manager;
    protected $slugGenerator;
    protected $productFactory;
    protected $productVariantFactory;
    protected $productTaxonFactory;
    protected $channelPricingFactory;
    protected $brandFactory;
    private FactoryInterface $productImageFactory;
    private ImageUploaderInterface $imageUploader;
    protected $productRepository;
    protected $productVariantRepository;
    protected $channelRepository;
    protected $brandRepository;
    protected $_taxons = [];
    protected $_mapping = [];
    protected $_taxCategories = [];
    protected $_attributes = [];
    protected $_options = [];
    protected $all_valid_options = [];
    protected $_stores;
    protected $output;
    
    public function __construct(EntityManagerInterface $manager, SlugGeneratorInterface $slugGenerator, ProductFactoryInterface $productFactory, ProductVariantFactoryInterface $productVariantFactory, FactoryInterface $productTaxonFactory, FactoryInterface $channelPricingFactory, FactoryInterface $brandFactory, FactoryInterface $productImageFactory, ImageUploaderInterface $imageUploader, ProductRepositoryInterface $productRepository, ProductVariantRepositoryInterface $productVariantRepository, ChannelRepositoryInterface $channelRepository, BrandRepository $brandRepository)
    {
        parent::__construct();
        
        $this->manager = $manager;
        $this->slugGenerator = $slugGenerator;
        $this->productFactory = $productFactory;
        $this->productVariantFactory = $productVariantFactory;
        $this->channelPricingFactory = $channelPricingFactory;
        $this->productTaxonFactory = $productTaxonFactory;
        $this->brandFactory = $brandFactory;
        $this->productImageFactory = $productImageFactory;
        $this->imageUploader = $imageUploader;
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->channelRepository = $channelRepository;
        $this->brandRepository = $brandRepository;
    }
    
    protected function configure(): void
    {
        $this
            ->setDescription('Import données Magento')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $output->writeln([
            '',
            '=================',
            'Chullanka - Import',
            '=================',
            '',
        ]);

        $now = new \DateTime();

        // récupération des Taxonomies
        $taxons = $this->manager->getRepository(Taxon::class)->findAll();
        foreach($taxons as $taxon)
        {
            $this->_taxons[ $taxon->getId() ] = $taxon;
        }
        $mapping = self::convertCsvToArray('var/imports/taxon_sylius_arbo_final.csv');
        foreach($mapping as $tx)
        {
            $this->_mapping[ $tx['magento_id'] ] = $tx['sylius_id'];
        }

        // récupération des TaxCategories
        $taxCategories = $this->manager->getRepository(TaxCategory::class)->findAll();
        foreach($taxCategories as $tc)
        {
            $this->_taxCategories[ $tc->getCode() ] = $tc;
        }

        // récupération des attributs
        $attributes = $this->manager->getRepository(ProductAttribute::class)->findAll();
        foreach($attributes as $attr)
        {
            $this->_attributes[ $attr->getCode() ] = $attr;
        }

        // récupération des options
        $options = $this->manager->getRepository(ProductOption::class)->findAll();
        foreach($options as $opt)
        {
            $this->_options[ $opt->getCode() ] = $opt;
        }

        $this->all_valid_options = ['couleurs','pointure','tailles','tailles_ski_snow','pointure_ski_snow','largeur_frein_ski','fermeture_zip_sdc','tailles_velo','tete_piolet','longueur_potence_cycle','diametre_tige_selle_cycle','nombre_dent_pedalier','taille_grip_cycle','sections_velo_route','taille_batons'];


        // récupération des magasins
        $this->_stores = $this->manager->getRepository(Store::class)->findAll();

        // canal Chullanka
        $this->channel = $this->channelRepository->findOneByCode('default');

        // conversion du CSV en Array
        $articles = self::convertCsvToArray('var/imports/catalog.csv');

        
        //$arts = [3];
        //$arts = [86,87,88,89,90];
        //foreach($arts as $artId)
        //$art = $articles[ $artId ];
        foreach($articles as $art)
        {
            $art_uuid = $art['code'];
            $found = $this->productVariantRepository->findOneByCode($art_uuid);
            if(!$found)
            {   
                $name = $art['name'];
                $parent_code = isset($art['parent_code']) ? $art['parent_code'] : '';
        
                // Variante(s)
                $this->output->writeln('On créé la variante');
                $productVariant = $this->productVariantFactory->createNew();
                $productVariant->setCode($art_uuid);
                $productVariant->setName($name);
                $createdAt = new \DateTime($art['created_at']);
                $productVariant->setCreatedAt($createdAt);
                $updatedAt = new \DateTime($art['updated_at']);
                $productVariant->setUpdatedAt($updatedAt);
                $productVariant->setOnHand($art['qty']);
                if(isset($art['weight'])) $productVariant->setWeight($art['weight']);
                $productVariant->setShippingRequired(true);
                $productVariant->setEnabled($art['status'] == 'Enabled');

                //tax_class_id 
                
                if(isset($art['tax_class_id']) && ($art['tax_class_id'] != 'Taux normal produit non alimentaire'))
                {
                    $taxCat = $this->_taxCategories['tva3'];
                    $rate = 55;
                }
                else
                {
                    $taxCat = $this->_taxCategories['tva1'];
                    $rate = 120;
                }
                $productVariant->setTaxCategory($taxCat);


                //Price
                if(isset($art['price']))
                {
                    $price = round($art['price'] * $rate);

                    $priceChannel = $this->channelPricingFactory->createNew();
                    $priceChannel->setChannelCode('default');
                    $priceChannel->setProductVariant($productVariant);
                    $priceChannel->setOriginalPrice($price);
                    $priceChannel->setPrice($price);

                    if(isset($art['special_price']))
                    {
                        $specialPrice = round($art['special_price'] * $rate);
                        $priceChannel->setDiscountPrice($specialPrice);
                    }
                    if(isset($art['special_from_date']))
                    {
                        $specialDateFrom = new \DateTime($art['special_from_date']);
                        $priceChannel->setDiscountFrom($specialDateFrom);
                    }
                    if(isset($art['special_to_date']))
                    {
                        $specialDateTo = new \DateTime($art['special_to_date']);
                        $priceChannel->setDiscountTo($specialDateTo);
                    }
                    //appliquer ?
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

                    $productVariant->addChannelPricing($priceChannel);
                }
                
                // ajout option de variantes
                foreach($this->_options as $opt => $option)
                {
                    if(isset($art[ $opt ]))
                    {
                        $optValues = $option->getValues();
                        $artVals = explode('|', $art[ $opt ]);
                        foreach($artVals as $artVal)
                        {
                            foreach($optValues as $optValue)
                            {
                                if($optValue->getValue() == $artVal)
                                {
                                    // ajoute l'option
                                    $productVariant->addOptionValue($optValue);
                                }
                            }
                        }
                    }
                }

                /*foreach($this->all_valid_options as $opt)
                {
                    if(isset($art[ $opt ]))
                    {
                        $option = $this->_options[ $opt ];
                        $optValues = $option->getValues();
                        foreach($optValues as $optValue)
                        {
                            if($optValue->getValue() == $art[ $opt ])
                            {
                                // ajoute l'option
                                $productVariant->addOptionValue($optValue);
                            }
                        }
                    }
                }*/
        
                // si c'est une variante de configurable...
                $product = !empty($parent_code) ? $this->getOrCreateProduct($parent_code, $art) : $this->getOrCreateProduct($art_uuid, $art);

                if($art['type_product'] == 'simple')
                {
                    $productVariant->setProduct($product);
                    $this->productVariantRepository->add($productVariant);
                }

                if($productVariant->getId())
                {
                    //stocks mag
                    foreach($this->_stores as $store)
                    {
                        $sid = $store->getId();
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
                                }
                            }
                        }
                        
                        if(!$storeFound)
                        {
                            $quantity = 0;
                            if(isset($art['qty_' . $sid]))
                            {
                                $quantity = $art['qty_' . $sid];
                            }

                            $output->writeln("Variante {$productVariant->getId()} : on va créer un stock pour : ".$store->getName());
                            $stock = new Stock();
                            $stock->setVariant($productVariant);
                            $stock->setStore($store);
                            $stock->setOnHand($quantity);
                            $this->manager->persist($stock);
                        }
                    }
            
                    $this->output->writeln('VariantID : '.$productVariant->getId());
                    // on remplit la table relationnelle MagentoID - SKU - SyliusID pour mettre à jour les commandes importées
                    $mp = new MagentoProduct();
                    $mp->setMagento( $art['id'] );
                    $mp->setCode( $art['code'] );
                    $mp->setSylius( $productVariant->getId() );
                    $this->manager->persist($mp);
                }
            }
        }
        
        $this->manager->flush();

        $end = new \DateTime();
        print_r($end->diff($now));
        echo $end->diff($now)->format("%hh%i et %ss");
        return Command::SUCCESS;
    }

    private function getOrCreateProduct($code, $article)
    {
        $product = $this->productRepository->findOneByCode($code);
        if(!$product)
        {
            $name = $article['name'];
            $product = $this->productFactory->createNew();
            $product->setCode($code);
            $product->setName($name);

            // test slug
            if($article['visibility'] == 'Not Visible Individually')
            {
                $slug = $this->slugGenerator->generate($name) . '-' . $article['code'];
            }
            else 
            {
                if(isset($article['url_key'])) $slug = $article['url_key'];
                //else $slug = $this->slugGenerator->generate($name);
            }
            while($this->manager->getRepository(ProductTranslation::class)->findOneBySlug($slug))
            {
                $slug .= '-1';
            }
            $product->setSlug($slug);

            $product->addChannel($this->channel);
            $createdAt = new \DateTime($article['created_at']);
            $product->setCreatedAt($createdAt);
            $updatedAt = new \DateTime($article['updated_at']);
            $product->setUpdatedAt($updatedAt);
            //$product->setEnabled(false);
            //$product->setEnabled($article['visibility'] == 'Catalog, Search') && ($article['status'] == 'Enabled');
            $product->setEnabled($article['status'] == 'Enabled');

            //taxonomie
            if(isset($article['taxonomy_ids']))
            {
                $taxos = explode('|', $article['taxonomy_ids']);
                $i = 0;
                $allreadyDone = [];
                foreach($taxos as $t)
                {
                    if(isset($this->_mapping[$t]))
                    {
                        $sylius_id = $this->_mapping[$t];
                        $taxon = $this->_taxons[ $sylius_id ];
                        
                        echo "Ajout de la taxo : ".$taxon->getId()."\n";

                        if(!in_array($taxon->getId(), $allreadyDone))
                        {
                            /** @var ProductTaxonInterface $productTaxon */
                            $productTaxon = $this->productTaxonFactory->createNew();
                            $productTaxon->setTaxon($taxon);
                            $productTaxon->setProduct($product);

                            $product->addProductTaxon($productTaxon);
                            
                            $allreadyDone[] = $taxon->getId();//evite les doublons de catégories
                            
                            if($i == 0) $product->setMainTaxon($taxon);
                            $i++;
                        }
                    }
                }
            }
            

            // ajout attributs du produit
            if(isset($article['description'])) $product->setDescription( $article['description'] );
            if(isset($article['short_description'])) $product->setShortDescription( $article['short_description'] );

            if(isset($article['brand_id']) && ($brand = $this->getMarque( $article['brand_id'] )))
            {
                $product->setBrand($brand);
            }

            if(isset($article['super_option']))
            {
                $superOptions = explode('|', $article['super_option']);
                foreach($superOptions as $supOption)
                {
                    if(isset($this->_options[ $supOption ]))
                    {
                        // ajoute l'option
                        $option = $this->_options[ $supOption ];
                        $product->addOption($option);
                        $product->setVariantSelectionMethod('match');
                    }
                }
            }

            // images
            if(isset($article['product_images']))
            {
                $images = explode('|', $article['product_images']);
                foreach($images as $img)
                {
                    if(is_file($img))
                    {
                        $decodedData = file_get_contents($img);// on récupère l'image
                        $tmpPath = sys_get_temp_dir().'/sf_upload'.uniqid();// qu'on place dans le dossier tmp
                        file_put_contents($tmpPath, $decodedData);

                        //$uploadedImage = new UploadedFile($imagePath, basename($imagePath));
                        $uploadedImage = new File($tmpPath);

                        /** @var ImageInterface $productImage */
                        $productImage = $this->productImageFactory->createNew();
                        $productImage->setFile($uploadedImage);
                        //$productImage->setType($imageType);

                        $this->imageUploader->upload($productImage);

                        $product->addImage($productImage);

                        unlink($tmpPath);//on supprime le fichier temporaire
                    }
                }
            }


            $removeAttributes = ['id','code','parent_code','super_option','product_images','name','qty','qty_1','qty_2','qty_3','qty_4','brand_id','created_at','short_description','special_from_date','special_to_date','special_price','status','updated_at','url_key','url_path','visibility','weight'];
            foreach($removeAttributes as $attr)
            {
                unset($article[$attr]);
            }

            foreach($article as $attr => $value)
            {
                // recherche l'attribute
                if(isset($this->_attributes[ $attr ]) && ($attribute = $this->_attributes[ $attr ]))
                {
                    $shouldAddIt = true;
                    $attrValue = new ProductAttributeValue();
                    $attrValue->setAttribute($attribute);
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
                                $shouldAddIt = false;
                            break;
                        case 'text':
                        default:
                            $attrValue->setValue($value);
                    }
                    if($shouldAddIt)
                        $product->addAttribute($attrValue);
                }
            }

            $this->productRepository->add($product);
        }
        return $product;
    }

    /**
     * Retrouve la marque via son nom
     * ou bien la crée d'abord si besoin
     */
    private function getMarque(string $marque)
    {
        $brand_code = $this->slugGenerator->generate($marque);

        // on cherche si la marque existe
        //if($brand = $this->manager->getRepository(Brand::class)->findOneByCode($brand_code))
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

    //PHP Function to convert CSV into array
    private static function convertCsvToArray($csvfile, $delimiter = ';') 
    {
        $data = [];
        $keys = [];
        $newArray = [];
        if(($handle = fopen($csvfile, 'r')) !== FALSE) 
        {
            $i = 0;
            while(($lineArray = fgetcsv($handle, 8000, $delimiter, '"')) !== FALSE)
            {
                for($j = 0; $j < count($lineArray); $j++) 
                {
                    $data[$i][$j] = $lineArray[$j]; 
                }
                $i++;
                //if($i == 10) break;//tmp pour test
            }
            fclose($handle); 
        }
        
        // Set number of elements (minus 1 because we shift off the first row)
        $count = count($data) - 1;
        
        //First row for label or name
        $labels = array_shift($data);
        foreach($labels as $label) 
        {
            $keys[] = $label;
        }
        
        // combine both array
        for($j = 0; $j < $count; $j++) 
        {
            $d = array_combine($keys, $data[$j]);
            $newArray[$j] = $d;
        }

        //remove empty values
        foreach($newArray as &$row)
        {
            foreach($row as $k=>$v)
            {
                if($v == '') unset($row[$k]);
            }
        }
        return $newArray;
    }

    /**
     * @deprecated
     * Cette fonction ne parcours pas les lignes complètes à cause des champs avec saut-de-lligne
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
                $data = str_getcsv($line, $delimiter);//casse les lignes avec les sauts de ligne des textarea
                if(empty($keys)) { $keys = $data; }
                else
                {
                    $row = [];
                    for($c=0; $c<count($data); $c++)
                    {
                        if(isset($keys[$c]) && !empty($data[$c]))
                        {
                            $val = $data[$c];
                            if(is_array($val)) $val = implode('', $val);
                            $row[ $keys[$c] ] = utf8_encode($val);
                        }
                    }
                    $rows[] = $row;
                }
            }
        }
        return $rows;
    }
}