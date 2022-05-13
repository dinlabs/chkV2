<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220513142852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_brand_translation ADD meta_title VARCHAR(255) DEFAULT NULL, ADD meta_description VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_product DROP code_chrono');
        $this->addSql('ALTER TABLE sylius_product_translation ADD meta_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_taxon_translation ADD meta_title VARCHAR(255) DEFAULT NULL, ADD meta_description VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_brand_translation DROP meta_title, DROP meta_description');
        $this->addSql('ALTER TABLE sylius_product ADD code_chrono VARCHAR(20) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE sylius_product_translation DROP meta_title');
        $this->addSql('ALTER TABLE sylius_taxon_translation DROP meta_title, DROP meta_description');
    }
}
