<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220503154118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_link (id INT AUTO_INCREMENT NOT NULL, taxon_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, INDEX IDX_608CCE51DE13F470 (taxon_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8MB4 COLLATE `UTF8MB4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_task (id INT AUTO_INCREMENT NOT NULL, command VARCHAR(255) NOT NULL, done TINYINT(1) NOT NULL, executed_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8MB4 COLLATE `UTF8MB4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_link ADD CONSTRAINT FK_608CCE51DE13F470 FOREIGN KEY (taxon_id) REFERENCES sylius_taxon (id)');
        $this->addSql('ALTER TABLE sylius_taxon ADD redirection_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_taxon ADD CONSTRAINT FK_CFD811CA1DC0789A FOREIGN KEY (redirection_id) REFERENCES sylius_taxon (id)');
        $this->addSql('CREATE INDEX IDX_CFD811CA1DC0789A ON sylius_taxon (redirection_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE nan_chk_link');
        $this->addSql('DROP TABLE nan_chk_task');
        $this->addSql('ALTER TABLE sylius_taxon DROP FOREIGN KEY FK_CFD811CA1DC0789A');
        $this->addSql('DROP INDEX IDX_CFD811CA1DC0789A ON sylius_taxon');
        $this->addSql('ALTER TABLE sylius_taxon DROP redirection_id');
    }
}
