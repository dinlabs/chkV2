<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Webmozart\Assert\Assert;

final class BrandsToCodesTransformer implements DataTransformerInterface
{
    private $brandRepository;

    public function __construct($brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function transform($value): Collection
    {
        Assert::nullOrIsArray($value);

        if (empty($value)) {
            return new ArrayCollection();
        }

        return new ArrayCollection($this->brandRepository->findBy(['code' => $value]));
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function reverseTransform($brands): array
    {
        Assert::isInstanceOf($brands, Collection::class);

        $brandCodes = [];

        /** @var ProductInterface $product */
        foreach ($brands as $brand) {
            $brandCodes[] = $brand->getCode();
        }

        return $brandCodes;
    }
}
