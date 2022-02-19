<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220218104555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_faq (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, enabled TINYINT(1) NOT NULL, INDEX IDX_7B6CF6F94584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_faq_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT NOT NULL, question VARCHAR(255) NOT NULL, answer TEXT NOT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_EBAB37CD2C2AC5D3 (translatable_id), UNIQUE INDEX nan_chk_faq_translation_uniq_trans (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_recall (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, product_id INT DEFAULT NULL, state SMALLINT NOT NULL, phone_number VARCHAR(20) NOT NULL, comment TEXT DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, INDEX IDX_10382BE89395C3F3 (customer_id), INDEX IDX_10382BE84584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_faq ADD CONSTRAINT FK_7B6CF6F94584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id)');
        $this->addSql('ALTER TABLE nan_chk_faq_translation ADD CONSTRAINT FK_EBAB37CD2C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES nan_chk_faq (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nan_chk_recall ADD CONSTRAINT FK_10382BE89395C3F3 FOREIGN KEY (customer_id) REFERENCES sylius_customer (id)');
        $this->addSql('ALTER TABLE nan_chk_recall ADD CONSTRAINT FK_10382BE84584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id)');
        $this->addSql('ALTER TABLE nan_chk_brand DROP size_guide');
        $this->addSql('ALTER TABLE nan_chk_brand_translation ADD size_guide TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_faq_translation DROP FOREIGN KEY FK_EBAB37CD2C2AC5D3');
        $this->addSql('DROP TABLE nan_chk_faq');
        $this->addSql('DROP TABLE nan_chk_faq_translation');
        $this->addSql('DROP TABLE nan_chk_recall');
        $this->addSql('ALTER TABLE nan_chk_brand ADD size_guide VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE nan_chk_brand_translation DROP size_guide');
    }
}
