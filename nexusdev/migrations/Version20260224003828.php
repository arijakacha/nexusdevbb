<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224003828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add meeting fields to coaching_session table for Jitsi integration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE coaching_session ADD meeting_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE coaching_session ADD meeting_room VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE coaching_session ADD meeting_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE coaching_session DROP meeting_url');
        $this->addSql('ALTER TABLE coaching_session DROP meeting_room');
        $this->addSql('ALTER TABLE coaching_session DROP meeting_expires_at');
    }
}
