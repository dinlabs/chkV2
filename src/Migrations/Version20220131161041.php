<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220131161041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_sport (id INT AUTO_INCREMENT NOT NULL, enabled TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_customer_store (customer_id INT NOT NULL, store_id INT NOT NULL, INDEX IDX_377AB5F99395C3F3 (customer_id), INDEX IDX_377AB5F9B092A811 (store_id), PRIMARY KEY(customer_id, store_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_customer_sport (customer_id INT NOT NULL, sport_id INT NOT NULL, INDEX IDX_D2A8025C9395C3F3 (customer_id), INDEX IDX_D2A8025CAC78BCF8 (sport_id), PRIMARY KEY(customer_id, sport_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_customer_store ADD CONSTRAINT FK_377AB5F99395C3F3 FOREIGN KEY (customer_id) REFERENCES sylius_customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_customer_store ADD CONSTRAINT FK_377AB5F9B092A811 FOREIGN KEY (store_id) REFERENCES nan_chk_store (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_customer_sport ADD CONSTRAINT FK_D2A8025C9395C3F3 FOREIGN KEY (customer_id) REFERENCES sylius_customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_customer_sport ADD CONSTRAINT FK_D2A8025CAC78BCF8 FOREIGN KEY (sport_id) REFERENCES nan_chk_sport (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_customer ADD chullpoints INT DEFAULT NULL, ADD licence_name VARCHAR(255) DEFAULT NULL, ADD licence_number VARCHAR(255) DEFAULT NULL, ADD licence_file VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_customer_sport DROP FOREIGN KEY FK_D2A8025CAC78BCF8');
        $this->addSql('DROP TABLE nan_chk_sport');
        $this->addSql('DROP TABLE nan_chk_customer_store');
        $this->addSql('DROP TABLE nan_chk_customer_sport');
        $this->addSql('ALTER TABLE sylius_customer DROP chullpoints, DROP licence_name, DROP licence_number, DROP licence_file');
    }
}
