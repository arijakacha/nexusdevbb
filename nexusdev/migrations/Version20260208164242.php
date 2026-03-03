<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208164242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Like, Report tables and features';
    }

    public function up(Schema $schema): void
    {
        // Create Like table
        $this->addSql('CREATE TABLE `like` (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            type VARCHAR(10) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_LIKE_USER (user_id),
            INDEX IDX_LIKE_POST (post_id),
            UNIQUE INDEX unique_like (user_id, post_id, type),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE `like` 
            ADD CONSTRAINT FK_LIKE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_LIKE_POST FOREIGN KEY (post_id) REFERENCES forum_post (id) ON DELETE CASCADE');

        // Create Report table
        $this->addSql('CREATE TABLE report (
            id INT AUTO_INCREMENT NOT NULL,
            reporter_id INT NOT NULL,
            reported_user_id INT DEFAULT NULL,
            post_id INT DEFAULT NULL,
            comment_id INT DEFAULT NULL,
            type VARCHAR(50) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            resolved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            resolved_by_id INT DEFAULT NULL,
            INDEX IDX_REPORT_REPORTER (reporter_id),
            INDEX IDX_REPORT_REPORTED_USER (reported_user_id),
            INDEX IDX_REPORT_POST (post_id),
            INDEX IDX_REPORT_COMMENT (comment_id),
            INDEX IDX_REPORT_RESOLVED_BY (resolved_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE report 
            ADD CONSTRAINT FK_REPORT_REPORTER FOREIGN KEY (reporter_id) REFERENCES user (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_REPORT_REPORTED_USER FOREIGN KEY (reported_user_id) REFERENCES user (id) ON DELETE SET NULL,
            ADD CONSTRAINT FK_REPORT_POST FOREIGN KEY (post_id) REFERENCES forum_post (id) ON DELETE SET NULL,
            ADD CONSTRAINT FK_REPORT_COMMENT FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE SET NULL,
            ADD CONSTRAINT FK_REPORT_RESOLVED_BY FOREIGN KEY (resolved_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `like` DROP FOREIGN KEY FK_LIKE_USER');
        $this->addSql('ALTER TABLE `like` DROP FOREIGN KEY FK_LIKE_POST');
        $this->addSql('DROP TABLE `like`');
        
        $this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_REPORT_REPORTER');
        $this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_REPORT_REPORTED_USER');
        $this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_REPORT_POST');
        $this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_REPORT_COMMENT');
        $this->addSql('ALTER TABLE report DROP FOREIGN KEY FK_REPORT_RESOLVED_BY');
        $this->addSql('DROP TABLE report');
    }
}
