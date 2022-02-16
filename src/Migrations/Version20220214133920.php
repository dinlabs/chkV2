<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220214133920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nan_chk_brand_translation (id INT AUTO_INCREMENT NOT NULL, introduction LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE loevgaard_brand_image');
        $this->addSql('DROP INDEX UNIQ_680CAA0877153098 ON nan_chk_brand');
        $this->addSql('ALTER TABLE nan_chk_brand ADD logo VARCHAR(255) DEFAULT NULL, ADD top TINYINT(1) DEFAULT NULL, ADD size_guide VARCHAR(255) DEFAULT NULL, ADD background VARCHAR(255) DEFAULT NULL, ADD soc_facebook VARCHAR(255) DEFAULT NULL, ADD soc_twitter VARCHAR(255) DEFAULT NULL, ADD soc_instagram VARCHAR(255) DEFAULT NULL, ADD soc_youtube VARCHAR(255) DEFAULT NULL, ADD soc_pinterest VARCHAR(255) DEFAULT NULL, ADD tag_instagram VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE loevgaard_brand_image (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, type VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, path VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, INDEX IDX_95D3C8B97E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE nan_chk_brand_translation');
        $this->addSql('ALTER TABLE nan_chk_brand DROP logo, DROP top, DROP size_guide, DROP background, DROP soc_facebook, DROP soc_twitter, DROP soc_instagram, DROP soc_youtube, DROP soc_pinterest, DROP tag_instagram');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_680CAA0877153098 ON nan_chk_brand (code)');
    }
}
