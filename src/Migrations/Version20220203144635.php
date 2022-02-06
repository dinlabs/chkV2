<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220203144635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        //$this->addSql('ALTER TABLE loevgaard_brand_image DROP FOREIGN KEY FK_95D3C8B97E3C61F9');
        //$this->addSql('ALTER TABLE sylius_product DROP FOREIGN KEY FK_677B9B7444F5D008');
        $this->addSql('CREATE TABLE nan_chk_rma (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, customer_id INT NOT NULL, address_id INT NOT NULL, number VARCHAR(20) DEFAULT NULL, phone_number VARCHAR(50) DEFAULT NULL, customer_email VARCHAR(255) DEFAULT NULL, state VARCHAR(20) NOT NULL, INDEX IDX_D141A23D8D9F6D38 (order_id), INDEX IDX_D141A23D9395C3F3 (customer_id), INDEX IDX_D141A23DF5B7AF75 (address_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_rma_product (id INT AUTO_INCREMENT NOT NULL, rma_id INT NOT NULL, orderitem_id INT NOT NULL, quantity INT NOT NULL, reason LONGTEXT DEFAULT NULL, INDEX IDX_196FEE1B8C1032D1 (rma_id), INDEX IDX_196FEE1B28D3A508 (orderitem_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_rma ADD CONSTRAINT FK_D141A23D8D9F6D38 FOREIGN KEY (order_id) REFERENCES sylius_order (id)');
        $this->addSql('ALTER TABLE nan_chk_rma ADD CONSTRAINT FK_D141A23D9395C3F3 FOREIGN KEY (customer_id) REFERENCES sylius_customer (id)');
        $this->addSql('ALTER TABLE nan_chk_rma ADD CONSTRAINT FK_D141A23DF5B7AF75 FOREIGN KEY (address_id) REFERENCES sylius_address (id)');
        $this->addSql('ALTER TABLE nan_chk_rma_product ADD CONSTRAINT FK_196FEE1B8C1032D1 FOREIGN KEY (rma_id) REFERENCES nan_chk_rma (id)');
        $this->addSql('ALTER TABLE nan_chk_rma_product ADD CONSTRAINT FK_196FEE1B28D3A508 FOREIGN KEY (orderitem_id) REFERENCES sylius_order_item (id)');
        //$this->addSql('DROP TABLE loevgaard_brand');
        //$this->addSql('DROP TABLE loevgaard_brand_image');
        //$this->addSql('DROP INDEX IDX_677B9B7444F5D008 ON sylius_product');
        //$this->addSql('ALTER TABLE sylius_product DROP brand_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_rma_product DROP FOREIGN KEY FK_196FEE1B8C1032D1');
        $this->addSql('CREATE TABLE loevgaard_brand (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, name VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, UNIQUE INDEX UNIQ_680CAA0877153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE loevgaard_brand_image (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, type VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, path VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, INDEX IDX_95D3C8B97E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE loevgaard_brand_image ADD CONSTRAINT FK_95D3C8B97E3C61F9 FOREIGN KEY (owner_id) REFERENCES loevgaard_brand (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE nan_chk_rma');
        $this->addSql('DROP TABLE nan_chk_rma_product');
        $this->addSql('ALTER TABLE sylius_product ADD brand_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_product ADD CONSTRAINT FK_677B9B7444F5D008 FOREIGN KEY (brand_id) REFERENCES loevgaard_brand (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_677B9B7444F5D008 ON sylius_product (brand_id)');
    }
}
