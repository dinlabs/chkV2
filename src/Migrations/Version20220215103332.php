<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220215103332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_brand_translation ADD advertising TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE nan_chk_store DROP website');
        $this->addSql('ALTER TABLE nan_chk_store_translation ADD advertising TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_brand_translation DROP advertising');
        $this->addSql('ALTER TABLE nan_chk_store ADD website VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE nan_chk_store_translation DROP advertising');
    }
}
