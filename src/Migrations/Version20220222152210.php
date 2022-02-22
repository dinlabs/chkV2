<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220222152210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_taxon_brand (taxon_id INT NOT NULL, brand_id INT NOT NULL, INDEX IDX_DC4F3B8DDE13F470 (taxon_id), INDEX IDX_DC4F3B8D44F5D008 (brand_id), PRIMARY KEY(taxon_id, brand_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_taxon_brand ADD CONSTRAINT FK_DC4F3B8DDE13F470 FOREIGN KEY (taxon_id) REFERENCES sylius_taxon (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_taxon_brand ADD CONSTRAINT FK_DC4F3B8D44F5D008 FOREIGN KEY (brand_id) REFERENCES nan_chk_brand (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_taxon_translation ADD content TEXT DEFAULT NULL');
        $this->addSql('CREATE TABLE nan_chk_taxon_top_product (taxon_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_D5E91929DE13F470 (taxon_id), INDEX IDX_D5E919294584665A (product_id), PRIMARY KEY(taxon_id, product_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_taxon_top_product ADD CONSTRAINT FK_D5E91929DE13F470 FOREIGN KEY (taxon_id) REFERENCES sylius_taxon (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_taxon_top_product ADD CONSTRAINT FK_D5E919294584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE nan_chk_taxon_brand');
        $this->addSql('ALTER TABLE sylius_taxon_translation DROP content');
        $this->addSql('DROP TABLE nan_chk_taxon_top_product');
    }
}
