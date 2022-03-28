<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220328142231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sylius_customer ADD store_id INT DEFAULT NULL, ADD notice SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_customer ADD CONSTRAINT FK_7E82D5E6B092A811 FOREIGN KEY (store_id) REFERENCES nan_chk_store (id)');
        $this->addSql('CREATE INDEX IDX_7E82D5E6B092A811 ON sylius_customer (store_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sylius_customer DROP FOREIGN KEY FK_7E82D5E6B092A811');
        $this->addSql('DROP INDEX IDX_7E82D5E6B092A811 ON sylius_customer');
        $this->addSql('ALTER TABLE sylius_customer DROP store_id, DROP notice');
    }
}
