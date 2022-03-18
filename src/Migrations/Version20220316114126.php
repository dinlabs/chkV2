<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220316114126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_store_exclusive_product (store_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_FA463136B092A811 (store_id), INDEX IDX_FA4631364584665A (product_id), PRIMARY KEY(store_id, product_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_store_other_product (store_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_5081C3EB092A811 (store_id), INDEX IDX_5081C3E4584665A (product_id), PRIMARY KEY(store_id, product_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_store_taxon (store_id INT NOT NULL, taxon_id INT NOT NULL, INDEX IDX_5EDEE1E9B092A811 (store_id), INDEX IDX_5EDEE1E9DE13F470 (taxon_id), PRIMARY KEY(store_id, taxon_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_store_service (id INT AUTO_INCREMENT NOT NULL, enabled TINYINT(1) NOT NULL, show_home TINYINT(1) NOT NULL, title VARCHAR(255) NOT NULL, thumbnail VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_store_to_service (storeservice_id INT NOT NULL, store_id INT NOT NULL, INDEX IDX_268ABB59FD966486 (storeservice_id), INDEX IDX_268ABB59B092A811 (store_id), PRIMARY KEY(storeservice_id, store_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_store_exclusive_product ADD CONSTRAINT FK_FA463136B092A811 FOREIGN KEY (store_id) REFERENCES nan_chk_store (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_store_exclusive_product ADD CONSTRAINT FK_FA4631364584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_store_other_product ADD CONSTRAINT FK_5081C3EB092A811 FOREIGN KEY (store_id) REFERENCES nan_chk_store (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_store_other_product ADD CONSTRAINT FK_5081C3E4584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_store_taxon ADD CONSTRAINT FK_5EDEE1E9B092A811 FOREIGN KEY (store_id) REFERENCES nan_chk_store (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_store_taxon ADD CONSTRAINT FK_5EDEE1E9DE13F470 FOREIGN KEY (taxon_id) REFERENCES sylius_taxon (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_store_to_service ADD CONSTRAINT FK_268ABB59FD966486 FOREIGN KEY (storeservice_id) REFERENCES nan_chk_store_service (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_store_to_service ADD CONSTRAINT FK_268ABB59B092A811 FOREIGN KEY (store_id) REFERENCES nan_chk_store (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_store_to_service DROP FOREIGN KEY FK_268ABB59FD966486');
        $this->addSql('DROP TABLE nan_chk_store_exclusive_product');
        $this->addSql('DROP TABLE nan_chk_store_other_product');
        $this->addSql('DROP TABLE nan_chk_store_taxon');
        $this->addSql('DROP TABLE nan_chk_store_service');
        $this->addSql('DROP TABLE nan_chk_store_to_service');
    }
}
