<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220314141406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE setono_sylius_feed__feed (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, state VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, feedType VARCHAR(255) NOT NULL, batches INT NOT NULL, finishedBatches INT NOT NULL, enabled TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_43E1F89A77153098 (code), INDEX IDX_43E1F89AA393D2FB (state), INDEX IDX_43E1F89A50F9BB84 (enabled), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE setono_sylius_feed__feed_channels (feed_id INT NOT NULL, channel_id INT NOT NULL, INDEX IDX_82FAB5E851A5BC03 (feed_id), INDEX IDX_82FAB5E872F5A1AA (channel_id), PRIMARY KEY(feed_id, channel_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE setono_sylius_feed__violation (id INT AUTO_INCREMENT NOT NULL, feed_id INT NOT NULL, channel_id INT NOT NULL, locale_id INT NOT NULL, severity VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, data LONGTEXT DEFAULT NULL, INDEX IDX_9118780651A5BC03 (feed_id), INDEX IDX_9118780672F5A1AA (channel_id), INDEX IDX_91187806E559DFD1 (locale_id), INDEX IDX_91187806F660D16B (severity), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE setono_sylius_feed__feed_channels ADD CONSTRAINT FK_82FAB5E851A5BC03 FOREIGN KEY (feed_id) REFERENCES setono_sylius_feed__feed (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE setono_sylius_feed__feed_channels ADD CONSTRAINT FK_82FAB5E872F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE setono_sylius_feed__violation ADD CONSTRAINT FK_9118780651A5BC03 FOREIGN KEY (feed_id) REFERENCES setono_sylius_feed__feed (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE setono_sylius_feed__violation ADD CONSTRAINT FK_9118780672F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE setono_sylius_feed__violation ADD CONSTRAINT FK_91187806E559DFD1 FOREIGN KEY (locale_id) REFERENCES sylius_locale (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE setono_sylius_feed__feed_channels DROP FOREIGN KEY FK_82FAB5E851A5BC03');
        $this->addSql('ALTER TABLE setono_sylius_feed__violation DROP FOREIGN KEY FK_9118780651A5BC03');
        $this->addSql('DROP TABLE setono_sylius_feed__feed');
        $this->addSql('DROP TABLE setono_sylius_feed__feed_channels');
        $this->addSql('DROP TABLE setono_sylius_feed__violation');
    }
}
