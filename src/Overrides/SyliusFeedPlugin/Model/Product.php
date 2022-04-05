<?php

declare(strict_types=1);

namespace App\Overrides\SyliusFeedPlugin\Model;

use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\Availability;
use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\Condition;
use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\DateRange;
#use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\Price;
use Webmozart\Assert\Assert;

final class Product
{
    private ?string $id = null;

    private ?string $code = null;

    private ?string $title = null;

    private ?string $description = null;

    private ?string $link = null;

    private ?string $imageLink = null;

    private array $additionalImageLinks = [];

    private ?Availability $availability = null;

    private ?Price $price = null;

    private ?Price $salePrice = null;
    
    private ?DateRange $salePriceEffectiveDate = null;
    
    private ?string $brand = null;
    
    private ?string $gtin = null;

    private ?string $mpn = null;

    private ?bool $identifierExists = null;

    private ?Condition $condition = null;

    private ?string $itemGroupId = null;

    private ?string $googleProductCategory = null;

    private ?string $productType = null;

    private ?string $shipping = null;

    private ?string $size = null;
    
    private ?string $color = null;

    private ?float $taxExclPrice = null;

    private ?string $taxPercent = null;
    
    private ?string $qty = null;

    private ?string $univers = null;
    private ?string $subCat1 = null;
    private ?string $subCat2 = null;

    private ?string $year = null;

    private ?string $supplierRef = null;

    private array $customLabels = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): void
    {
        $this->link = $link;
    }

    public function getImageLink(): ?string
    {
        return $this->imageLink;
    }

    public function setImageLink(?string $imageLink): void
    {
        $this->imageLink = $imageLink;
    }

    public function getAdditionalImageLinks(): array
    {
        return $this->additionalImageLinks;
    }

    public function setAdditionalImageLinks(array $additionalImageLinks): void
    {
        $this->additionalImageLinks = $additionalImageLinks;
    }

    public function addAdditionalImageLink(string $additionalImageLink): void
    {
        $this->additionalImageLinks[] = $additionalImageLink;
    }

    public function hasAdditionalImageLinks(): bool
    {
        return count($this->additionalImageLinks) > 0;
    }

    public function getAvailability(): ?Availability
    {
        return $this->availability;
    }

    public function setAvailability(?Availability $availability): void
    {
        $this->availability = $availability;
    }

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price): void
    {
        $this->price = $price;
    }
    
    public function getSalePrice(): ?Price
    {
        return $this->salePrice;
    }

    public function setSalePrice(?Price $salePrice): void
    {
        $this->salePrice = $salePrice;
    }

    public function getSalePriceEffectiveDate(): ?DateRange
    {
        return $this->salePriceEffectiveDate;
    }

    public function setSalePriceEffectiveDate(?DateRange $salePriceEffectiveDate): void
    {
        $this->salePriceEffectiveDate = $salePriceEffectiveDate;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): void
    {
        $this->brand = $brand;
    }

    public function getGtin(): ?string
    {
        return $this->gtin;
    }

    public function setGtin(?string $gtin): void
    {
        $this->gtin = $gtin;
    }
    
    public function getMpn(): ?string
    {
        return $this->mpn;
    }

    public function setMpn(?string $mpn): void
    {
        $this->mpn = $mpn;
    }

    public function getIdentifierExists(): ?bool
    {
        return $this->identifierExists;
    }

    public function setIdentifierExists(?bool $identifierExists): void
    {
        $this->identifierExists = $identifierExists;
    }

    public function getCondition(): ?Condition
    {
        return $this->condition;
    }

    public function setCondition(?Condition $condition): void
    {
        $this->condition = $condition;
    }

    public function getItemGroupId(): ?string
    {
        return $this->itemGroupId;
    }

    public function setItemGroupId(?string $itemGroupId): void
    {
        $this->itemGroupId = $itemGroupId;
    }

    public function getGoogleProductCategory(): ?string
    {
        return $this->googleProductCategory;
    }

    public function setGoogleProductCategory(?string $googleProductCategory): void
    {
        $this->googleProductCategory = $googleProductCategory;
    }

    public function getProductType(): ?string
    {
        return $this->productType;
    }

    public function setProductType(?string $productType): void
    {
        $this->productType = $productType;
    }

    public function getShipping(): ?string
    {
        return $this->shipping;
    }
    
    public function setShipping(?string $shipping): void
    {
        $this->shipping = $shipping;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): void
    {
        $this->size = $size;
    }
    
    public function getColor(): ?string
    {
        return $this->color;
    }
    
    public function setColor(?string $color): void
    {
        $this->color = $color;
    }
    
    public function getTaxExclPrice(): ?float
    {
        return $this->taxExclPrice;
    }

    public function setTaxExclPrice(?float $taxExclPrice): void
    {
        $this->taxExclPrice = $taxExclPrice;
    }

    public function getTaxPercent(): ?string
    {
        return $this->taxPercent;
    }

    public function setTaxPercent(?string $taxPercent): void
    {
        $this->taxPercent = $taxPercent;
    }

    public function getQty(): ?string
    {
        return $this->qty;
    }

    public function setQty(?string $qty): void
    {
        $this->qty = $qty;
    }

    public function getUnivers(): ?string
    {
        return $this->univers;
    }

    public function setUnivers(?string $univers): void
    {
        $this->univers = $univers;
    }

    public function getSubCat1(): ?string
    {
        return $this->subCat1;
    }

    public function setSubCat1(?string $subCat1): void
    {
        $this->subCat1 = $subCat1;
    }

    public function getSubCat2(): ?string
    {
        return $this->subCat2;
    }

    public function setSubCat2(?string $subCat2): void
    {
        $this->subCat2 = $subCat2;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(?string $year): void
    {
        $this->year = $year;
    }

    public function getSupplierRef(): ?string
    {
        return $this->supplierRef;
    }

    public function setSupplierRef(?string $supplierRef): void
    {
        $this->supplierRef = $supplierRef;
    }

    public function getCustomLabels(): array
    {
        return $this->customLabels;
    }

    public function setCustomLabel(string $label, int $index): void
    {
        Assert::greaterThanEq($index, 0);
        Assert::lessThanEq($index, 4);

        $this->customLabels[$index] = $label;
    }
}
