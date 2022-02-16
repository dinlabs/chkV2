<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220215115631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_brand ADD top_product_id INT DEFAULT NULL, ADD product_background VARCHAR(255) DEFAULT NULL, ADD top_position SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE nan_chk_brand ADD CONSTRAINT FK_CAD27AE74584665A FOREIGN KEY (top_product_id) REFERENCES sylius_product (id)');
        $this->addSql('CREATE INDEX IDX_CAD27AE74584665A ON nan_chk_brand (top_product_id)');
        $this->addSql('ALTER TABLE nan_chk_store ADD background VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_brand DROP FOREIGN KEY FK_CAD27AE74584665A');
        $this->addSql('DROP INDEX IDX_CAD27AE74584665A ON nan_chk_brand');
        $this->addSql('ALTER TABLE nan_chk_brand DROP top_product_id, DROP product_background, DROP top_position');
        $this->addSql('ALTER TABLE nan_chk_store DROP background');
    }
}
