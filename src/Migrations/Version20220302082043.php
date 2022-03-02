<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220302082043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE magento_customer (id INT AUTO_INCREMENT NOT NULL, magento INT NOT NULL, email VARCHAR(50) NOT NULL, sylius INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE magento_order (id INT AUTO_INCREMENT NOT NULL, magento INT NOT NULL, code VARCHAR(20) NOT NULL, sylius INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE magento_product (id INT AUTO_INCREMENT NOT NULL, magento INT NOT NULL, code VARCHAR(20) NOT NULL, sylius INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sylius_channel_pricing ADD discount_price INT DEFAULT NULL, ADD discount_from DATE DEFAULT NULL, ADD discount_to DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE magento_customer');
        $this->addSql('DROP TABLE magento_order');
        $this->addSql('DROP TABLE magento_product');
        $this->addSql('ALTER TABLE sylius_channel_pricing DROP discount_price, DROP discount_from, DROP discount_to');
    }
}
