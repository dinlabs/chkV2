<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211221110325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_chulli (id INT AUTO_INCREMENT NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) DEFAULT NULL, expertise VARCHAR(255) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_chulltest (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, chulli_id INT DEFAULT NULL, date DATE DEFAULT NULL, note SMALLINT DEFAULT NULL, UNIQUE INDEX UNIQ_16270D8F4584665A (product_id), INDEX IDX_16270D8FC2631C8B (chulli_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_chulltest_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT NOT NULL, description LONGTEXT DEFAULT NULL, pros LONGTEXT DEFAULT NULL, cons LONGTEXT DEFAULT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_414869552C2AC5D3 (translatable_id), UNIQUE INDEX nan_chk_chulltest_translation_uniq_trans (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_store (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, surface VARCHAR(10) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_store_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT NOT NULL, description LONGTEXT DEFAULT NULL, joinus LONGTEXT DEFAULT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_39D96482C2AC5D3 (translatable_id), UNIQUE INDEX nan_chk_store_translation_uniq_trans (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_chulltest ADD CONSTRAINT FK_16270D8F4584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id)');
        $this->addSql('ALTER TABLE nan_chk_chulltest ADD CONSTRAINT FK_16270D8FC2631C8B FOREIGN KEY (chulli_id) REFERENCES nan_chk_chulli (id)');
        $this->addSql('ALTER TABLE nan_chk_chulltest_translation ADD CONSTRAINT FK_414869552C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES nan_chk_chulltest (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_store_translation ADD CONSTRAINT FK_39D96482C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES nan_chk_store (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_chulltest DROP FOREIGN KEY FK_16270D8FC2631C8B');
        $this->addSql('ALTER TABLE nan_chk_chulltest_translation DROP FOREIGN KEY FK_414869552C2AC5D3');
        $this->addSql('ALTER TABLE nan_chk_store_translation DROP FOREIGN KEY FK_39D96482C2AC5D3');
        $this->addSql('DROP TABLE nan_chk_chulli');
        $this->addSql('DROP TABLE nan_chk_chulltest');
        $this->addSql('DROP TABLE nan_chk_chulltest_translation');
        $this->addSql('DROP TABLE nan_chk_store');
        $this->addSql('DROP TABLE nan_chk_store_translation');
    }
}
