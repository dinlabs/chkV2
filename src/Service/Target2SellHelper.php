<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Chullanka\Parameter;
use App\Entity\Product\Product;
use App\Entity\Product\ProductAttribute;
use App\Entity\Product\ProductAttributeValue;
use App\Entity\Taxonomy\Taxon;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Target2SellHelper
{
    private $entityManager;
    private $router;
    private $cacheManager;
    private $projectDir;
    private $targetToSellDir;
    private $exportDir;
    private $doc;

    public function __construct(EntityManagerInterface $entityManager, RouterInterface $router, CacheManager $cacheManager, string $projectDir)
    {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->cacheManager = $cacheManager;
        $this->projectDir = $projectDir;
        $this->targetToSellDir = $this->projectDir . '/var/chkfiles/target2sell/';
        if(!is_dir($this->targetToSellDir)) mkdir($this->targetToSellDir);
        $this->exportDir = $this->projectDir . '/public/media/exports/';
    }
    private function chkParameter($slug)
    {
        return $this->entityManager->getRepository(Parameter::class)->getValue($slug);
    }

    /**
     * Convert TXT Files into Array
     * @param string $csvfile
     * @param string $delimiter
     * @return string[][]
     */
    public static function csvToArray($csvfile, $delimiter = ';')
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
     * 
     */
    public function exportCatalog()
    {
        $context = $this->router->getContext();
        $baseUrl = $context->getScheme() . '://' . $context->getHost();

        $localCode = 'fr_FR';

        $this->doc = new \DOMDocument('1.0', 'UTF-8');
        $this->doc->xmlStandalone = true;
        $this->doc->formatOutput = true;

        $catalogNode = $this->doc->createElement('catalog');
        $catalogAttribute = $this->doc->createAttribute('xmlns:xsi');
        $catalogAttribute->value = 'http://www.w3.org/2001/XMLSchemainstance';
        $catalogNode->appendChild($catalogAttribute);
        $catalogAttribute = $this->doc->createAttribute('xsi:schemaLocation');
        $catalogAttribute->value = 'http://static.target2sell.com/target2sell-catalog-v1.0.xsd';
        $catalogNode->appendChild($catalogAttribute);

        //<price_format currency_symbol='€' dec_sep=',' k_sep=' '>9 999,99 €</price_format>

        // Categories
        $taxons = $this->entityManager->getRepository(Taxon::class)->matching(
            Criteria::create()->where(
                Criteria::expr()->gt('level', 0)
            )
        );
        $countTaxons = count($taxons);
        if($countTaxons)
        {
            $categoriesNode = $this->doc->createElement('categories');
            $categoriesAttr = $this->doc->createAttribute('size');
            $categoriesAttr->value = $countTaxons;
            $categoriesNode->appendChild($categoriesAttr);
            foreach($taxons as $taxon)
            {
                $categoryNode = $this->doc->createElement('category');
                $categoryAttr = $this->doc->createAttribute('id');
                $categoryAttr->value = $taxon->getId();
                $categoryNode->appendChild($categoryAttr);

                $parent_id = $taxon->getParent()->getId();
                if($parent_id == 1) $parent_id = 0;
                $categoryNode->appendChild($this->addKeyVal('parent_id', $parent_id));
                $categoryNode->appendChild($this->addKeyVal('is_active', (int)$taxon->isEnabled()));
                $categoryNode->appendChild($this->addKeyVal('name', $taxon->getName(), true));

                $categoriesNode->appendChild($categoryNode);
            }
            $catalogNode->appendChild($categoriesNode);
        }

        // Products
        $products = $this->entityManager->getRepository(Product::class)->findAll();
        $countProducts = count($products);
        if($countProducts)
        {
            $productsNode = $this->doc->createElement('products');
            $productsAttr = $this->doc->createAttribute('size');
            $productsAttr->value = $countProducts;
            $productsNode->appendChild($productsAttr);
            foreach($products as $product)
            {
                $productNode = $this->doc->createElement('product');
                $productAttr = $this->doc->createAttribute('id');
                $productAttr->value = $product->getId();
                $productNode->appendChild($productAttr);
                
                $productNode->appendChild($this->addKeyVal('sku', $product->getCode()));
                $productNode->appendChild($this->addKeyVal('name', $product->getName(), true));
                $productNode->appendChild($this->addKeyVal('short_desc', $product->getShortDescription(), true));
                $productNode->appendChild($this->addKeyVal('long_desc', $product->getDescription(), true));

                $url = $this->router->generate(
                    'sylius_shop_product_show',
                    ['slug' => $product->getSlug(), '_locale' => $localCode],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $productNode->appendChild($this->addKeyVal('url', $url));

                // image
                $images = $product->getImagesByType('main');
                if ($images->count() === 0)  $images = $product->getImages();
                if($images->count() && ($image = $images->first()))
                {
                    $imgUrl = $this->cacheManager->getBrowserPath((string) $image->getPath(), 'sylius_shop_product_large_thumbnail');
                    $productNode->appendChild($this->addKeyVal('img_url', $imgUrl));
                }

                // declinaison
                $variants = $product->getVariants();
                if($variant = $variants->first())
                {
                    if($channelPricing = $variant->getChannelPricings()->first())
                    {
                        $orig = $channelPricing->getOriginalPrice() / 100;
                        $productNode->appendChild($this->addKeyVal('price', $orig));
                        $price = $channelPricing->getPrice() / 100;
                        if($price != $orig)
                        {
                            $productNode->appendChild($this->addKeyVal('promotion', $price));
                        }
                    }
                }
                $productNode->appendChild($this->addKeyVal('salable', (int)$product->isEnabled()));

                $totalQty = $product->getTotalQuantities();
                $productNode->appendChild($this->addKeyVal('in_stock', (int)(bool)$totalQty));
                $productNode->appendChild($this->addKeyVal('stock_qty', (int)$totalQty));
                
                if($productTaxons = $product->getProductTaxons())
                {
                    $prodCategoriesNode = $this->doc->createElement('categories');

                    foreach($productTaxons as $productTaxon)
                    {
                        if($taxon = $productTaxon->getTaxon())
                        {
                            $prodCatNode = $this->doc->createElement('id', (string)$taxon->getId());
                            $prodCategoriesNode->appendChild($prodCatNode);
                        }
                    }
                    $productNode->appendChild($prodCategoriesNode);
                }

                //attributes
                $attributesNode = $this->doc->createElement('attributes');

                // news from / to
                if($product->getNewFrom())
                {
                    $code = 'news_from_date';
                    $_value = $product->getNewFrom()->format('Y-m-d');
                    $attributesNode->appendChild(
                        $this->addAttribute($code, $code, $_value, strtolower($_value))
                    );
                }
                if($product->getNewTo())
                {
                    $code = 'news_to_date';
                    $_value = $product->getNewTo()->format('Y-m-d');
                    $attributesNode->appendChild(
                        $this->addAttribute($code, $code, $_value, strtolower($_value))
                    );
                }

                foreach(['genre', 'cycle_vie', 'exclu_mag', 'exclu_web'] as $code)
                {
                    if($attributeValue = $product->getAttributeByCodeAndLocale($code))
                    {
                        $attribute = $attributeValue->getAttribute();
                        
                        $_value = $attributeValue->getValue();
                        if(is_array($_value)) $_value =  implode(' ', $attributeValue->getValue());

                        $conf = $attribute->getConfiguration();
                        if(isset($conf['choices']))
                        {
                            $choices = $conf['choices'];
                            if(isset($choices[ $_value ]) 
                            && isset($choices[ $_value ][ $localCode ])
                            )
                            {
                                $_value =  $choices[ $_value ][ $localCode ];
                            }
                        }
                        $_value = (string)$_value;
                        $attributesNode->appendChild(
                            $this->addAttribute($code, $attribute->getName(), $_value, strtolower($_value))
                        );
                    }
                }

                if($brand = $product->getBrand())
                {
                    $attributesNode->appendChild(
                        $this->addAttribute('brand', 'Brand', $brand->getName(), 'B_' . $brand->getId())
                    );
                    if($logo = $brand->getLogo())
                    {
                        $_logoUrl = $baseUrl . '/media/brand/logos/' . $logo;
                        $attributesNode->appendChild(
                            $this->addAttribute('logo_marque', 'logo_marque', $_logoUrl, '')
                        );
                    }
                    $brandUrl = $this->router->generate(
                        'brand_view',
                        ['code' => $brand->getCode(), '_locale' => $localCode],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                    $attributesNode->appendChild(
                        $this->addAttribute('lien_marque', 'lien_marque', $brandUrl, '')
                    );
                }

                if($availabilities = $product->getAvailabilities())
                {
                    foreach($availabilities as $store => $availability)
                    {
                        if($store == 'web') continue;
                        $attributesNode->appendChild(
                            $this->addAttribute('Quantite_' . ucFirst($store), 'Quantite ' . ucFirst($store), (string)$availability)
                        );
                    }
                }

                $productNode->appendChild($attributesNode);
                
                if($product->isConfigurable() && (count($variants) > 1))
                {
                    $declinationsNode = $this->doc->createElement('declinations');
                    foreach($variants as $variant)
                    {
                        $declinationNode = $this->doc->createElement('declination');
                        $declinationAttr = $this->doc->createAttribute('id');
                        $declinationAttr->value = 'v-' . $variant->getId();
                        $declinationNode->appendChild($declinationAttr);

                        if($channelPricing = $variant->getChannelPricings()->first())
                        {
                            $declinationAttr = $this->doc->createAttribute('price');
                            $price = $channelPricing->getPrice() / 100;
                            $declinationAttr->value = $price;
                            $declinationNode->appendChild($declinationAttr);
                        }
                        
                        foreach($variant->getOptionValues() as $option) 
                        {
                            $attributeNode = $this->doc->createElement('attribute');
                            $attributeAttr = $this->doc->createAttribute('id');
                            $attributeAttr->value = $option->getOption()->getCode();
                            $attributeNode->appendChild($attributeAttr);

                            $attributeAttr = $this->doc->createAttribute('name');
                            $attributeAttr->value = $option->getOption()->getName();
                            $attributeNode->appendChild($attributeAttr);

                            $attributeAttr = $this->doc->createAttribute('type');
                            $attributeAttr->value = 'string';
                            $attributeNode->appendChild($attributeAttr);
                            
                            $valueNode = $this->doc->createElement('value');
                            $valueAttr = $this->doc->createAttribute('id');
                            $valueAttr->value = $option->getId();
                            $valueNode->appendChild($valueAttr);
                            $valueNode->appendChild( $this->doc->createCDATASection( $option->getValue() ) );
                            $attributeNode->appendChild($valueNode);

                            $declinationNode->appendChild($attributeNode);
                        }

                        $declinationsNode->appendChild($declinationNode);
                    }
                    /*<declination id='69325' price='241.6583' ><attribute id="tailles" name="Tailles" type="string">
                        <value id="569"><![CDATA[L]]></value>
                        </attribute>
                        </declination></declinations>*/

                    $productNode->appendChild($declinationsNode);
                }
                
                $productsNode->appendChild($productNode);
            }
            $catalogNode->appendChild($productsNode);
        }

        $this->doc->appendChild($catalogNode);
        
        $filename = 'export_catalog_default.xml';
        $file = $this->exportDir . $filename;
        if (file_exists($file)) { unlink ($file); }

        if(file_put_contents($file, $this->doc->saveXML(), FILE_APPEND))
        {
            echo "XML bien généré : ".$filename;
        }
        else echo "Pb pour générer le fichier $filename";
    }
    /**
     * Return an XML node
     */
    private function addKeyVal(string $key, mixed $val, bool $cdata = false)
    {
        $val = trim((string)$val);
        $node = $this->doc->createElement($key);
        $node->appendChild( $cdata ? $this->doc->createCDATASection($val) : $this->doc->createTextNode($val) );
        return $node;
    }
    /**
     * Return an XML node
     */
    private function addAttribute(string $code, string $name, mixed $val, string $val_id = null)
    {
        $node = $this->doc->createElement('attribute');
        
        $attrIdAttr = $this->doc->createAttribute('id');
        $attrIdAttr->value = $code;
        $node->appendChild($attrIdAttr);
        $attrNameAttr = $this->doc->createAttribute('name');
        $attrNameAttr->value = $name;
        $node->appendChild($attrNameAttr);
        $attrTypeAttr = $this->doc->createAttribute('type');
        $attrTypeAttr->value = 'string';
        $node->appendChild($attrTypeAttr);
        
        $val = trim((string)$val);
        if($val != '')
        {
            $valueNode = $this->doc->createElement('value');
            $attrValueNode = $this->doc->createAttribute('id');
            $attrValueNode->value = !is_null($val_id) ? $val_id : $val;
            $valueNode->appendChild($attrValueNode);
            $valueNode->appendChild($this->doc->createCDATASection($val));
            $node->appendChild($valueNode);
        }
        return $node;
    }


    public function updateProductRanks()
    {
        $repoProduct = $this->entityManager->getRepository(Product::class);

        // récupération des attributs
        $_attributes = [];
        $attributes = $this ->entityManager
                            ->getRepository(ProductAttribute::class)
                            ->createQueryBuilder('pa')
                            ->where('pa.code IN (:ranks)')
                            ->setParameter(':ranks', ['rank1','rank2','rank3','rank4','rank5','rank6'])
                            ->getQuery()
                            ->getResult()
        ;
        foreach($attributes as $attr) $_attributes[ $attr->getCode() ] = $attr;

        //curl -X GET -H "t2s-customer-id: JINRXA62YWCZ2V" https://api.target2sell.com/catalog/indexes/ > chullanka.csv.gz
        try {
            $file = $this->getRankFile();
            $lines = self::csvToArray($file);
            foreach($lines as $line)
            {
                extract($line);//fwProductID/rank1/rank2/.../rank6

                // recherche le produit
                if($product = $repoProduct->findOneByCode($fwProductID))
                {
                    // recherche chaque attribut "rank"
                    foreach($_attributes as $rank => $attribute)
                    {
                        if(isset($$rank))
                        {
                            //recherche si la valeur existe
                            $attrValue = $product->getAttributeByCodeAndLocale($rank);
                            if(!$attrValue)
                            {
                                $attrValue = new ProductAttributeValue();
                                $attrValue->setAttribute($attribute);
                                $product->addAttribute($attrValue);
                            }
                            $_neoval = (int)($$rank * 100);
                            $attrValue->setValue($_neoval);
                        }
                    }
                }
            }
            $this->entityManager->flush();

            // suppression du fichier
            unlink($file);
        } catch (\Exception $e) {
            error_log(print_r($e, true));
        }
    }

    private function getRankFile()
    {
        $filename = 'rankings.csv';
        $filePath = $this->targetToSellDir . $filename;
        if(!is_file($filePath))
        {
            $serverUrl = $this->chkParameter('t2s-server-url');//'https://api.target2sell.com/catalog/indexes/';
            $customerId = $this->chkParameter('t2s-customer-id');//'JINRXA62YWCZ2V';

            // WS
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $serverUrl);//set URL and other appropriate options
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//make curl follow a location
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("t2s-customer-id: $customerId"));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            
            $response = curl_exec($ch);
            $curlHttpInfo = curl_getinfo($ch);

            if (curl_errno($ch) || $curlHttpInfo['http_code'] != 200 || empty($response)) {
                if (curl_errno($ch)) {
                    $message = 'Catalog curl Error : ' . curl_error($ch);
                } elseif ($curlHttpInfo['http_code'] != 200) {
                    $message = 'Bad response from Target2sell API, store returned HTTP_CODE = ' . $curlHttpInfo['http_code'] . ' content: ' . $response;
                } elseif (empty($response)) {
                    $message = 'Empty data recieved from Target2sell API';
                }
                throw new \Exception($message);
            }

            // close cURL resource, and free up system resources
            curl_close($ch);


            // save GZ file
            $tmpFilePath = $filePath . '.gz';
            if(file_exists($tmpFilePath)) unlink ($tmpFilePath);

            if(file_put_contents($tmpFilePath, $response, FILE_APPEND))
            {
                //error_log("CSZ.GZ bien récupéré : ".$filename);

                $bufferSize = 4096; // read 4kb at a time

                //Open our files (in binary mode)
                $file = gzopen($tmpFilePath, 'rb');
                $outFile = fopen($filePath, 'wb');

                //Keep repeating until the end of the input file
                while (!gzeof($file)) 
                {
                    // Both fwrite and gzread and binary-safe
                    fwrite($outFile, gzread($file, $bufferSize));
                }
                //Files are done, close files
                fclose($outFile);
                gzclose($file);

                // Delete csv.gz file
                unlink($tmpFilePath);
            }
            else 
            {
                throw new \Exception("Pb pour enregistrer le fichier $filename");
            }
        }

        return $filePath;
    }
}