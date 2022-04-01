<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220401135749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_rma ADD reception_at DATETIME DEFAULT NULL, ADD return_at DATETIME DEFAULT NULL, ADD public_comment LONGTEXT DEFAULT NULL, ADD private_comment LONGTEXT DEFAULT NULL, CHANGE state state VARCHAR(35) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nan_chk_rma DROP reception_at, DROP return_at, DROP public_comment, DROP private_comment, CHANGE state state VARCHAR(20) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
    }
}
