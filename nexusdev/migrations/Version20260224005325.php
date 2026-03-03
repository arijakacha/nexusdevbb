<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224005325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add phone number and SMS consent fields to player table for SMS notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD phone_number VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD sms_consent TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP phone_number');
        $this->addSql('ALTER TABLE player DROP sms_consent');
    }
}
