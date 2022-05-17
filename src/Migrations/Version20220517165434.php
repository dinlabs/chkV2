<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220517165434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sylius_rbac_administration_role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, permissions JSON NOT NULL, UNIQUE INDEX UNIQ_3333A12E5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8MB4 COLLATE `UTF8MB4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sylius_admin_user ADD administration_role_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_admin_user ADD CONSTRAINT FK_88D5CC4D913437BF FOREIGN KEY (administration_role_id) REFERENCES sylius_rbac_administration_role (id)');
        $this->addSql('CREATE INDEX IDX_88D5CC4D913437BF ON sylius_admin_user (administration_role_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sylius_admin_user DROP FOREIGN KEY FK_88D5CC4D913437BF');
        $this->addSql('DROP TABLE sylius_rbac_administration_role');
        $this->addSql('DROP INDEX IDX_88D5CC4D913437BF ON sylius_admin_user');
        $this->addSql('ALTER TABLE sylius_admin_user DROP administration_role_id');
    }
}
