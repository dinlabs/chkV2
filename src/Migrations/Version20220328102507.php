<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220328102507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_cms_block_brands (block_id INT NOT NULL, brand_id INT NOT NULL, INDEX IDX_D149A256E9ED820C (block_id), INDEX IDX_D149A25644F5D008 (brand_id), PRIMARY KEY(block_id, brand_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_cms_block_chullis (block_id INT NOT NULL, chulli_id INT NOT NULL, INDEX IDX_1CBA0A8BE9ED820C (block_id), INDEX IDX_1CBA0A8BC2631C8B (chulli_id), PRIMARY KEY(block_id, chulli_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_cms_block_sports (block_id INT NOT NULL, sport_id INT NOT NULL, INDEX IDX_DC221F7EE9ED820C (block_id), INDEX IDX_DC221F7EAC78BCF8 (sport_id), PRIMARY KEY(block_id, sport_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_cms_block_stores (block_id INT NOT NULL, store_id INT NOT NULL, INDEX IDX_7A7B9AAEE9ED820C (block_id), INDEX IDX_7A7B9AAEB092A811 (store_id), PRIMARY KEY(block_id, store_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_cms_block_brands ADD CONSTRAINT FK_D149A256E9ED820C FOREIGN KEY (block_id) REFERENCES bitbag_cms_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_cms_block_brands ADD CONSTRAINT FK_D149A25644F5D008 FOREIGN KEY (brand_id) REFERENCES nan_chk_brand (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_cms_block_chullis ADD CONSTRAINT FK_1CBA0A8BE9ED820C FOREIGN KEY (block_id) REFERENCES bitbag_cms_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_cms_block_chullis ADD CONSTRAINT FK_1CBA0A8BC2631C8B FOREIGN KEY (chulli_id) REFERENCES nan_chk_chulli (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_cms_block_sports ADD CONSTRAINT FK_DC221F7EE9ED820C FOREIGN KEY (block_id) REFERENCES bitbag_cms_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_cms_block_sports ADD CONSTRAINT FK_DC221F7EAC78BCF8 FOREIGN KEY (sport_id) REFERENCES nan_chk_sport (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_cms_block_stores ADD CONSTRAINT FK_7A7B9AAEE9ED820C FOREIGN KEY (block_id) REFERENCES bitbag_cms_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_cms_block_stores ADD CONSTRAINT FK_7A7B9AAEB092A811 FOREIGN KEY (store_id) REFERENCES nan_chk_store (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bitbag_cms_block ADD datefrom DATETIME DEFAULT NULL, ADD dateto DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE bitbag_cms_block_translation ADD extra VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE nan_chk_cms_block_brands');
        $this->addSql('DROP TABLE nan_chk_cms_block_chullis');
        $this->addSql('DROP TABLE nan_chk_cms_block_sports');
        $this->addSql('DROP TABLE nan_chk_cms_block_stores');
        $this->addSql('ALTER TABLE bitbag_cms_block DROP datefrom, DROP dateto');
        $this->addSql('ALTER TABLE bitbag_cms_block_translation DROP extra');
    }
}
