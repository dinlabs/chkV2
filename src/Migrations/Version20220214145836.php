<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220214145836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_brand_translation ADD translatable_id INT NOT NULL, ADD locale VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE nan_chk_brand_translation ADD CONSTRAINT FK_BC59C1232C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES nan_chk_brand (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_BC59C1232C2AC5D3 ON nan_chk_brand_translation (translatable_id)');
        $this->addSql('CREATE UNIQUE INDEX nan_chk_brand_translation_uniq_trans ON nan_chk_brand_translation (translatable_id, locale)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_brand_translation DROP FOREIGN KEY FK_BC59C1232C2AC5D3');
        $this->addSql('DROP INDEX IDX_BC59C1232C2AC5D3 ON nan_chk_brand_translation');
        $this->addSql('DROP INDEX nan_chk_brand_translation_uniq_trans ON nan_chk_brand_translation');
        $this->addSql('ALTER TABLE nan_chk_brand_translation DROP translatable_id, DROP locale');
    }
}
