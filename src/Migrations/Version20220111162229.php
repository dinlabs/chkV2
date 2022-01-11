<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220111162229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_pack_element (id INT AUTO_INCREMENT NOT NULL, parent_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_86C9C1AC727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_pack_element_product (packelement_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_38CB8962381D248F (packelement_id), INDEX IDX_38CB89624584665A (product_id), PRIMARY KEY(packelement_id, product_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_stock (id INT AUTO_INCREMENT NOT NULL, variant_id INT NOT NULL, store_id INT NOT NULL, onHand INT DEFAULT NULL, INDEX IDX_9DB6D5DF3B69A9AF (variant_id), INDEX IDX_9DB6D5DFB092A811 (store_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_pack_element ADD CONSTRAINT FK_86C9C1AC727ACA70 FOREIGN KEY (parent_id) REFERENCES sylius_product (id)');
        $this->addSql('ALTER TABLE nan_chk_pack_element_product ADD CONSTRAINT FK_38CB8962381D248F FOREIGN KEY (packelement_id) REFERENCES nan_chk_pack_element (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_pack_element_product ADD CONSTRAINT FK_38CB89624584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_stock ADD CONSTRAINT FK_9DB6D5DF3B69A9AF FOREIGN KEY (variant_id) REFERENCES sylius_product_variant (id)');
        $this->addSql('ALTER TABLE nan_chk_stock ADD CONSTRAINT FK_9DB6D5DFB092A811 FOREIGN KEY (store_id) REFERENCES nan_chk_store (id)');
        $this->addSql('ALTER TABLE sylius_order_item ADD further JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_product ADD is_pack TINYINT(1) DEFAULT \'0\' NOT NULL, ADD mounting TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_pack_element_product DROP FOREIGN KEY FK_38CB8962381D248F');
        $this->addSql('DROP TABLE nan_chk_pack_element');
        $this->addSql('DROP TABLE nan_chk_pack_element_product');
        $this->addSql('DROP TABLE nan_chk_stock');
        $this->addSql('ALTER TABLE sylius_order_item DROP further');
        $this->addSql('ALTER TABLE sylius_product DROP is_pack, DROP mounting');
    }
}
