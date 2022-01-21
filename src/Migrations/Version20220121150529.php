<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220121150529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_store ADD street VARCHAR(255) NOT NULL, ADD postcode VARCHAR(20) NOT NULL, ADD city VARCHAR(255) NOT NULL, ADD phone_number VARCHAR(20) DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_product CHANGE mounting mounting TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_store DROP street, DROP postcode, DROP city, DROP phone_number, DROP email');
        $this->addSql('ALTER TABLE sylius_product CHANGE mounting mounting TINYINT(1) DEFAULT NULL');
    }
}
