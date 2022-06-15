<?php

namespace App\Repository;

use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductVariantRepository as BaseProductVariantRepository;

class ProductVariantRepository extends BaseProductVariantRepository
{
    public function findOptionValueByBrand($option, $brand)
    {
        $res = $this->createQueryBuilder('pv')
            ->join('pv.product', 'p')
            ->join('p.brand', 'b')
            ->join('pv.optionValues', 'ov')
            ->where('ov.option = :option')
            ->setParameter('option', $option)
            ->andWhere('b.code = :brand')
            ->setParameter('brand', $brand)
            ->andWhere('p.enabled = 1')
            ->getQuery()
            ->getResult()
        ;

        $values = [];
        foreach ($res as $a) {
            foreach ($a->getOptionValues() as $optionValue) {
                if ($optionValue->getOption() === $option) {
                    $values[] = $optionValue->getValue();
                    break;
                }
            }
        }

        $values = array_unique($values);
        sort($values);

        return $values;
    }

    public function findOptionValueByTaxon($option, $taxon)
    {
        $res = $this->createQueryBuilder('pv')
            ->join('pv.product', 'p')
            ->join('p.productTaxons', 'pt')
            ->join('pv.optionValues', 'ov')
            ->where('ov.option = :option')
            ->setParameter('option', $option)
            ->andWhere('pt.taxon = :taxon')
            ->setParameter('taxon', $taxon)
            ->andWhere('p.enabled = 1')
            ->getQuery()
            ->getResult()
        ;

        $values = [];
        foreach ($res as $a) {
            foreach ($a->getOptionValues() as $optionValue) {
                if ($optionValue->getOption() === $option) {
                    $values[] = $optionValue->getValue();
                    break;
                }
            }
        }

        $values = array_unique($values);
        sort($values);

        return $values;
    }
}