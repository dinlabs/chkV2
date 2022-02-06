<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220121174648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_chulli ADD enabled TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE nan_chk_store ADD enabled TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE sylius_product CHANGE mounting mounting SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_chulli DROP enabled');
        $this->addSql('ALTER TABLE nan_chk_store DROP enabled');
        $this->addSql('ALTER TABLE sylius_product CHANGE mounting mounting TINYINT(1) DEFAULT NULL');
    }
}
