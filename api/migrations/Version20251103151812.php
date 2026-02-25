<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251103151812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pending_verification ADD type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE user DROP is_verified, DROP verification_code, DROP verification_code_expires_at, DROP verified_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pending_verification DROP type');
        $this->addSql('ALTER TABLE `user` ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL, ADD verification_code VARCHAR(6) DEFAULT NULL, ADD verification_code_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD verified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
