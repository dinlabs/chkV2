<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220314135437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_taxon_other_taxon (taxon_source INT NOT NULL, taxon_target INT NOT NULL, INDEX IDX_D129FB2055334B02 (taxon_source), INDEX IDX_D129FB204CD61B8D (taxon_target), PRIMARY KEY(taxon_source, taxon_target)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_taxon_other_taxon ADD CONSTRAINT FK_D129FB2055334B02 FOREIGN KEY (taxon_source) REFERENCES sylius_taxon (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_taxon_other_taxon ADD CONSTRAINT FK_D129FB204CD61B8D FOREIGN KEY (taxon_target) REFERENCES sylius_taxon (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE nan_chk_taxon_other_taxon');
    }
}
