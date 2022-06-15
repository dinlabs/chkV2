<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220615155514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE setono_sylius_redirect__redirect (id INT AUTO_INCREMENT NOT NULL, source VARCHAR(255) NOT NULL, destination VARCHAR(255) NOT NULL, permanent TINYINT(1) NOT NULL, count INT NOT NULL, last_accessed DATETIME DEFAULT NULL, enabled TINYINT(1) NOT NULL, only_404 TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_FC2D63FE19860ABB (last_accessed), INDEX IDX_FC2D63FE50F9BB84 (enabled), INDEX IDX_FC2D63FE726A87A7 (only_404), INDEX findOneEnabledBySource_idx (source, enabled), INDEX findOne404EnabledBySource_idx (source, enabled, only_404), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8MB4 COLLATE `UTF8MB4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE setono_sylius_redirect__redirect_channels (redirect_id INT NOT NULL, channel_id INT NOT NULL, INDEX IDX_11BE99B8B42D874D (redirect_id), INDEX IDX_11BE99B872F5A1AA (channel_id), PRIMARY KEY(redirect_id, channel_id)) DEFAULT CHARACTER SET UTF8MB4 COLLATE `UTF8MB4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE setono_sylius_redirect__redirect_channels ADD CONSTRAINT FK_11BE99B8B42D874D FOREIGN KEY (redirect_id) REFERENCES setono_sylius_redirect__redirect (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE setono_sylius_redirect__redirect_channels ADD CONSTRAINT FK_11BE99B872F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE setono_sylius_redirect__redirect_channels DROP FOREIGN KEY FK_11BE99B8B42D874D');
        $this->addSql('DROP TABLE setono_sylius_redirect__redirect');
        $this->addSql('DROP TABLE setono_sylius_redirect__redirect_channels');
    }
}
