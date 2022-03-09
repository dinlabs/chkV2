<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220307164957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_wishlist (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_AFF5DB629395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nan_chk_wishlist_product (id INT AUTO_INCREMENT NOT NULL, wishlist_id INT NOT NULL, product_id INT NOT NULL, variant_id INT DEFAULT NULL, quantity INT DEFAULT NULL, INDEX IDX_2B209544FB8E54CD (wishlist_id), INDEX IDX_2B2095444584665A (product_id), INDEX IDX_2B2095443B69A9AF (variant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nan_chk_wishlist ADD CONSTRAINT FK_AFF5DB629395C3F3 FOREIGN KEY (customer_id) REFERENCES sylius_customer (id)');
        $this->addSql('ALTER TABLE nan_chk_wishlist_product ADD CONSTRAINT FK_2B209544FB8E54CD FOREIGN KEY (wishlist_id) REFERENCES nan_chk_wishlist (id)');
        $this->addSql('ALTER TABLE nan_chk_wishlist_product ADD CONSTRAINT FK_2B2095444584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id)');
        $this->addSql('ALTER TABLE nan_chk_wishlist_product ADD CONSTRAINT FK_2B2095443B69A9AF FOREIGN KEY (variant_id) REFERENCES sylius_product_variant (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_wishlist_product DROP FOREIGN KEY FK_2B209544FB8E54CD');
        $this->addSql('DROP TABLE nan_chk_wishlist');
        $this->addSql('DROP TABLE nan_chk_wishlist_product');
    }
}
