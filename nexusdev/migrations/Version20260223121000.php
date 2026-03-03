<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add messaging tables: conversation, message, conversation_participants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, conversation_id INT NOT NULL, sender_id INT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, INDEX idx_message_conversation (conversation_id), INDEX idx_message_sender (sender_id), INDEX idx_message_created (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE conversation_participants (conversation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_97A3A2F99E6F5DF (user_id), INDEX IDX_97A3A2F9714586C (conversation_id), PRIMARY KEY(conversation_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_5377C48E714586C FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_5377C48E67220AC6 FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversation_participants ADD CONSTRAINT FK_97A3A2F9714586C FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversation_participants ADD CONSTRAINT FK_97A3A2F99E6F5DF FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conversation_participants DROP FOREIGN KEY FK_97A3A2F99E6F5DF');
        $this->addSql('ALTER TABLE conversation_participants DROP FOREIGN KEY FK_97A3A2F9714586C');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_5377C48E67220AC6');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_5377C48E714586C');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE conversation_participants');
    }
}
