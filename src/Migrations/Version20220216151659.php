<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220216151659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_complementary (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, chulli_id INT DEFAULT NULL, enabled TINYINT(1) NOT NULL, background VARCHAR(255) DEFAULT NULL, show_from DATETIME DEFAULT NULL, show_to DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_A3115364584665A (product_id), INDEX IDX_A311536C2631C8B (chulli_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_complementary_product (complementaryproduct_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_54D0C0EA57D78494 (complementaryproduct_id), INDEX IDX_54D0C0EA4584665A (product_id), PRIMARY KEY(complementaryproduct_id, product_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_complementary_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_DC7E6F642C2AC5D3 (translatable_id), UNIQUE INDEX nan_chk_complementary_translation_uniq_trans (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_complementary ADD CONSTRAINT FK_A3115364584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id)');
        $this->addSql('ALTER TABLE nan_chk_complementary ADD CONSTRAINT FK_A311536C2631C8B FOREIGN KEY (chulli_id) REFERENCES nan_chk_chulli (id)');
        $this->addSql('ALTER TABLE nan_chk_complementary_product ADD CONSTRAINT FK_54D0C0EA57D78494 FOREIGN KEY (complementaryproduct_id) REFERENCES nan_chk_complementary (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_complementary_product ADD CONSTRAINT FK_54D0C0EA4584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_complementary_translation ADD CONSTRAINT FK_DC7E6F642C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES nan_chk_complementary (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_brand RENAME INDEX idx_cad27ae74584665a TO IDX_CAD27AE762F9ECC8');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_complementary_product DROP FOREIGN KEY FK_54D0C0EA57D78494');
        $this->addSql('ALTER TABLE nan_chk_complementary_translation DROP FOREIGN KEY FK_DC7E6F642C2AC5D3');
        $this->addSql('DROP TABLE nan_chk_complementary');
        $this->addSql('DROP TABLE nan_chk_complementary_product');
        $this->addSql('DROP TABLE nan_chk_complementary_translation');
        $this->addSql('ALTER TABLE nan_chk_brand RENAME INDEX idx_cad27ae762f9ecc8 TO IDX_CAD27AE74584665A');
    }
}
