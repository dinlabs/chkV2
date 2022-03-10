<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220310094909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_brand_top_product (brand_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_3F23B24044F5D008 (brand_id), INDEX IDX_3F23B2404584665A (product_id), PRIMARY KEY(brand_id, product_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_brand_top_product ADD CONSTRAINT FK_3F23B24044F5D008 FOREIGN KEY (brand_id) REFERENCES nan_chk_brand (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_brand_top_product ADD CONSTRAINT FK_3F23B2404584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE nan_chk_brand_top_product');
    }
}
