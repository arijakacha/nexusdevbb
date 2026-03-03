<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204142912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE content (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, type VARCHAR(20) NOT NULL, body LONGTEXT NOT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, author_id INT NOT NULL, INDEX IDX_FEC530A9F675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT NOT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(30) NOT NULL, provider VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, order_id INT NOT NULL, UNIQUE INDEX UNIQ_6D28840D8D9F6D38 (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_order (id INT AUTO_INCREMENT NOT NULL, total DOUBLE PRECISION NOT NULL, status VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_17EB68C0A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE virtual_currency (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, amount INT NOT NULL, deleted_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_62BB20CDA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE content ADD CONSTRAINT FK_FEC530A9F675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D8D9F6D38 FOREIGN KEY (order_id) REFERENCES user_order (id)');
        $this->addSql('ALTER TABLE user_order ADD CONSTRAINT FK_17EB68C0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE virtual_currency ADD CONSTRAINT FK_62BB20CDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE coach_game DROP FOREIGN KEY `fk_coach_game_coach`');
        $this->addSql('ALTER TABLE coach_game DROP FOREIGN KEY `fk_coach_game_game`');
        $this->addSql('ALTER TABLE follow DROP FOREIGN KEY `fk_follow_player`');
        $this->addSql('ALTER TABLE follow DROP FOREIGN KEY `fk_follow_team`');
        $this->addSql('ALTER TABLE follow DROP FOREIGN KEY `fk_follow_user`');
        $this->addSql('ALTER TABLE guide DROP FOREIGN KEY `fk_guide_coach`');
        $this->addSql('ALTER TABLE guide DROP FOREIGN KEY `fk_guide_game`');
        $this->addSql('ALTER TABLE news DROP FOREIGN KEY `fk_news_author`');
        $this->addSql('ALTER TABLE news DROP FOREIGN KEY `fk_news_game`');
        $this->addSql('ALTER TABLE news_comment DROP FOREIGN KEY `fk_news_comment_news`');
        $this->addSql('ALTER TABLE news_comment DROP FOREIGN KEY `fk_news_comment_user`');
        $this->addSql('DROP TABLE coach_game');
        $this->addSql('DROP TABLE follow');
        // NOTE: forum_category is kept as a legacy table to avoid FK constraint issues during migration.
        // $this->addSql('DROP TABLE forum_category');
        $this->addSql('DROP TABLE guide');
        $this->addSql('DROP TABLE news');
        $this->addSql('DROP TABLE news_comment');
        $this->addSql('ALTER TABLE coach DROP FOREIGN KEY `fk_coach_user`');
        $this->addSql('ALTER TABLE coach CHANGE experience_level experience_level VARCHAR(100) DEFAULT NULL, CHANGE bio bio LONGTEXT DEFAULT NULL, CHANGE rating rating NUMERIC(3, 2) DEFAULT NULL, CHANGE price_per_session price_per_session NUMERIC(10, 2) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE coach ADD CONSTRAINT FK_3F596DCCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE coach RENAME INDEX uniq_coach_user TO UNIQ_3F596DCCA76ED395');
        $this->addSql('ALTER TABLE coaching_session DROP FOREIGN KEY `FK_coaching_session_coach`');
        $this->addSql('ALTER TABLE coaching_session DROP FOREIGN KEY `FK_coaching_session_player`');
        $this->addSql('ALTER TABLE coaching_session CHANGE status status VARCHAR(20) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE coaching_session ADD CONSTRAINT FK_7BAAADB499E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE coaching_session ADD CONSTRAINT FK_7BAAADB43C105691 FOREIGN KEY (coach_id) REFERENCES coach (id)');
        $this->addSql('ALTER TABLE coaching_session RENAME INDEX idx_coaching_session_player TO IDX_7BAAADB499E6F5DF');
        $this->addSql('ALTER TABLE coaching_session RENAME INDEX idx_coaching_session_coach TO IDX_7BAAADB43C105691');
        $this->addSql('ALTER TABLE forum_comment DROP FOREIGN KEY `fk_forum_comment_post`');
        $this->addSql('ALTER TABLE forum_comment DROP FOREIGN KEY `fk_forum_comment_user`');
        $this->addSql('ALTER TABLE forum_comment CHANGE content content LONGTEXT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE forum_comment ADD CONSTRAINT FK_65B81F1DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE forum_comment ADD CONSTRAINT FK_65B81F1D4B89032C FOREIGN KEY (post_id) REFERENCES forum_post (id)');
        $this->addSql('ALTER TABLE forum_comment RENAME INDEX fk_forum_comment_user TO IDX_65B81F1DA76ED395');
        $this->addSql('ALTER TABLE forum_comment RENAME INDEX fk_forum_comment_post TO IDX_65B81F1D4B89032C');
        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY `fk_forum_post_category`');
        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY `fk_forum_post_user`');
        $this->addSql('DROP INDEX fk_forum_post_category ON forum_post');
        $this->addSql('ALTER TABLE forum_post DROP category_id, DROP status, CHANGE title title VARCHAR(150) NOT NULL, CHANGE content content LONGTEXT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT FK_996BCC5AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE forum_post RENAME INDEX fk_forum_post_user TO IDX_996BCC5AA76ED395');
        $this->addSql('DROP INDEX uniq_game_name ON game');
        $this->addSql('ALTER TABLE game CHANGE logo logo VARCHAR(255) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE release_year release_year INT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY `FK_organization_owner`');
        $this->addSql('ALTER TABLE organization CHANGE logo logo VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE organization RENAME INDEX idx_organization_owner TO IDX_C1EE637C7E3C61F9');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY `fk_player_game`');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY `fk_player_team`');
        $this->addSql('DROP INDEX uniq_player_nickname_game ON player');
        $this->addSql('ALTER TABLE player CHANGE real_name real_name VARCHAR(150) DEFAULT NULL, CHANGE role role VARCHAR(80) DEFAULT NULL, CHANGE nationality nationality VARCHAR(80) DEFAULT NULL, CHANGE profile_picture profile_picture VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE score score INT NOT NULL, CHANGE is_pro is_pro TINYINT NOT NULL');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE player RENAME INDEX fk_player_team TO IDX_98197A65296CD8AE');
        $this->addSql('ALTER TABLE player RENAME INDEX fk_player_game TO IDX_98197A65E48FD905');
        $this->addSql('ALTER TABLE product DROP created_at, CHANGE quantity quantity INT NOT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_purchase DROP FOREIGN KEY `fk_product_purchase_product`');
        $this->addSql('ALTER TABLE product_purchase DROP FOREIGN KEY `fk_product_purchase_user`');
        $this->addSql('ALTER TABLE product_purchase CHANGE quantity quantity INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product_purchase ADD CONSTRAINT FK_AAA7BBACA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product_purchase ADD CONSTRAINT FK_AAA7BBAC4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_purchase RENAME INDEX fk_product_purchase_user TO IDX_AAA7BBACA76ED395');
        $this->addSql('ALTER TABLE product_purchase RENAME INDEX fk_product_purchase_product TO IDX_AAA7BBAC4584665A');
        $this->addSql('ALTER TABLE statistic DROP INDEX uniq_stat_player, ADD INDEX IDX_649B469C99E6F5DF (player_id)');
        $this->addSql('ALTER TABLE statistic DROP FOREIGN KEY `fk_stat_game`');
        $this->addSql('ALTER TABLE statistic DROP FOREIGN KEY `fk_stat_player`');
        $this->addSql('ALTER TABLE statistic DROP FOREIGN KEY `fk_stat_team`');
        $this->addSql('ALTER TABLE statistic CHANGE matches_played matches_played INT NOT NULL, CHANGE wins wins INT NOT NULL, CHANGE losses losses INT NOT NULL, CHANGE kills kills INT NOT NULL, CHANGE deaths deaths INT NOT NULL, CHANGE assists assists INT NOT NULL, CHANGE win_rate win_rate NUMERIC(5, 2) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE statistic ADD CONSTRAINT FK_649B469CE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE statistic ADD CONSTRAINT FK_649B469C296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE statistic ADD CONSTRAINT FK_649B469C99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE statistic RENAME INDEX fk_stat_game TO IDX_649B469CE48FD905');
        $this->addSql('ALTER TABLE statistic RENAME INDEX fk_stat_team TO IDX_649B469C296CD8AE');
        $this->addSql('ALTER TABLE stream DROP FOREIGN KEY `FK_stream_player`');
        $this->addSql('ALTER TABLE stream CHANGE is_live is_live TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE stream ADD CONSTRAINT FK_F0E9BE1C99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE stream RENAME INDEX idx_stream_player TO IDX_F0E9BE1C99E6F5DF');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY `fk_team_game`');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY `FK_team_organization`');
        $this->addSql('DROP INDEX uniq_team_name_game ON team');
        $this->addSql('ALTER TABLE team CHANGE logo logo VARCHAR(255) DEFAULT NULL, CHANGE country country VARCHAR(80) DEFAULT NULL, CHANGE foundation_year foundation_year INT DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61FE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE team RENAME INDEX fk_team_game TO IDX_C4E0A61FE48FD905');
        $this->addSql('ALTER TABLE team RENAME INDEX idx_team_organization TO IDX_C4E0A61F32C8A3DE');
        $this->addSql('ALTER TABLE user CHANGE profile_picture profile_picture VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE status status VARCHAR(30) NOT NULL, CHANGE user_type user_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX username TO UNIQ_8D93D649F85E0677');
        $this->addSql('ALTER TABLE user RENAME INDEX email TO UNIQ_8D93D649E7927C74');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE coach_game (coach_id INT NOT NULL, game_id INT NOT NULL, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX fk_coach_game_game (game_id), INDEX IDX_A01454903C105691 (coach_id), PRIMARY KEY (coach_id, game_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE follow (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, follow_type ENUM(\'TEAM\', \'PLAYER\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, team_id INT DEFAULT NULL, player_id INT DEFAULT NULL, followed_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX idx_follow_user (user_id), INDEX idx_follow_target_team (team_id), INDEX idx_follow_target_player (player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE forum_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE guide (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, coach_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, difficulty_level ENUM(\'BEGINNER\', \'INTERMEDIATE\', \'ADVANCED\') CHARACTER SET utf8mb4 DEFAULT \'\'\'BEGINNER\'\'\' NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, media_type ENUM(\'VIDEO\', \'TEXT\', \'MIXED\') CHARACTER SET utf8mb4 DEFAULT \'\'\'TEXT\'\'\' NOT NULL COLLATE `utf8mb4_unicode_ci`, media_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, INDEX fk_guide_game (game_id), INDEX fk_guide_coach (coach_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE news (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, author_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, cover_image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, published_at DATETIME DEFAULT \'NULL\', status ENUM(\'DRAFT\', \'PUBLISHED\') CHARACTER SET utf8mb4 DEFAULT \'\'\'DRAFT\'\'\' NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX fk_news_game (game_id), INDEX fk_news_author (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE news_comment (id INT AUTO_INCREMENT NOT NULL, news_id INT NOT NULL, user_id INT NOT NULL, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX fk_news_comment_news (news_id), INDEX fk_news_comment_user (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE coach_game ADD CONSTRAINT `fk_coach_game_coach` FOREIGN KEY (coach_id) REFERENCES coach (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coach_game ADD CONSTRAINT `fk_coach_game_game` FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE follow ADD CONSTRAINT `fk_follow_player` FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE follow ADD CONSTRAINT `fk_follow_team` FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE follow ADD CONSTRAINT `fk_follow_user` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guide ADD CONSTRAINT `fk_guide_coach` FOREIGN KEY (coach_id) REFERENCES coach (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guide ADD CONSTRAINT `fk_guide_game` FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE news ADD CONSTRAINT `fk_news_author` FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE news ADD CONSTRAINT `fk_news_game` FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE news_comment ADD CONSTRAINT `fk_news_comment_news` FOREIGN KEY (news_id) REFERENCES news (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE news_comment ADD CONSTRAINT `fk_news_comment_user` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content DROP FOREIGN KEY FK_FEC530A9F675F31B');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D8D9F6D38');
        $this->addSql('ALTER TABLE user_order DROP FOREIGN KEY FK_17EB68C0A76ED395');
        $this->addSql('ALTER TABLE virtual_currency DROP FOREIGN KEY FK_62BB20CDA76ED395');
        $this->addSql('DROP TABLE content');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE user_order');
        $this->addSql('DROP TABLE virtual_currency');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE coach DROP FOREIGN KEY FK_3F596DCCA76ED395');
        $this->addSql('ALTER TABLE coach CHANGE experience_level experience_level VARCHAR(100) DEFAULT \'NULL\', CHANGE bio bio TEXT DEFAULT NULL, CHANGE rating rating NUMERIC(3, 2) DEFAULT \'NULL\', CHANGE price_per_session price_per_session NUMERIC(10, 2) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE coach ADD CONSTRAINT `fk_coach_user` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coach RENAME INDEX uniq_3f596dcca76ed395 TO uniq_coach_user');
        $this->addSql('ALTER TABLE coaching_session DROP FOREIGN KEY FK_7BAAADB499E6F5DF');
        $this->addSql('ALTER TABLE coaching_session DROP FOREIGN KEY FK_7BAAADB43C105691');
        $this->addSql('ALTER TABLE coaching_session CHANGE status status VARCHAR(20) DEFAULT \'\'\'PENDING\'\'\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE coaching_session ADD CONSTRAINT `FK_coaching_session_coach` FOREIGN KEY (coach_id) REFERENCES coach (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coaching_session ADD CONSTRAINT `FK_coaching_session_player` FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coaching_session RENAME INDEX idx_7baaadb499e6f5df TO IDX_coaching_session_player');
        $this->addSql('ALTER TABLE coaching_session RENAME INDEX idx_7baaadb43c105691 TO IDX_coaching_session_coach');
        $this->addSql('ALTER TABLE forum_comment DROP FOREIGN KEY FK_65B81F1DA76ED395');
        $this->addSql('ALTER TABLE forum_comment DROP FOREIGN KEY FK_65B81F1D4B89032C');
        $this->addSql('ALTER TABLE forum_comment CHANGE content content TEXT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE forum_comment ADD CONSTRAINT `fk_forum_comment_post` FOREIGN KEY (post_id) REFERENCES forum_post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_comment ADD CONSTRAINT `fk_forum_comment_user` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_comment RENAME INDEX idx_65b81f1da76ed395 TO fk_forum_comment_user');
        $this->addSql('ALTER TABLE forum_comment RENAME INDEX idx_65b81f1d4b89032c TO fk_forum_comment_post');
        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY FK_996BCC5AA76ED395');
        $this->addSql('ALTER TABLE forum_post ADD category_id INT NOT NULL, ADD status ENUM(\'OPEN\', \'CLOSED\') DEFAULT \'\'\'OPEN\'\'\' NOT NULL, CHANGE title title VARCHAR(255) NOT NULL, CHANGE content content TEXT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT `fk_forum_post_category` FOREIGN KEY (category_id) REFERENCES forum_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT `fk_forum_post_user` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_forum_post_category ON forum_post (category_id)');
        $this->addSql('ALTER TABLE forum_post RENAME INDEX idx_996bcc5aa76ed395 TO fk_forum_post_user');
        $this->addSql('ALTER TABLE game CHANGE logo logo VARCHAR(255) DEFAULT \'NULL\', CHANGE description description TEXT DEFAULT NULL, CHANGE release_year release_year SMALLINT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_game_name ON game (name)');
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY FK_C1EE637C7E3C61F9');
        $this->addSql('ALTER TABLE organization CHANGE logo logo VARCHAR(255) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT `FK_organization_owner` FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE organization RENAME INDEX idx_c1ee637c7e3c61f9 TO IDX_organization_owner');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65296CD8AE');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65E48FD905');
        $this->addSql('ALTER TABLE player CHANGE real_name real_name VARCHAR(150) DEFAULT \'NULL\', CHANGE role role VARCHAR(80) DEFAULT \'NULL\', CHANGE nationality nationality VARCHAR(80) DEFAULT \'NULL\', CHANGE profile_picture profile_picture VARCHAR(255) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE score score INT DEFAULT 0 NOT NULL, CHANGE is_pro is_pro TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT `fk_player_game` FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT `fk_player_team` FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX uniq_player_nickname_game ON player (nickname, game_id)');
        $this->addSql('ALTER TABLE player RENAME INDEX idx_98197a65296cd8ae TO fk_player_team');
        $this->addSql('ALTER TABLE player RENAME INDEX idx_98197a65e48fd905 TO fk_player_game');
        $this->addSql('ALTER TABLE product ADD created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE quantity quantity INT DEFAULT 0 NOT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT \'NULL\', CHANGE image_path image_path VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE product_purchase DROP FOREIGN KEY FK_AAA7BBACA76ED395');
        $this->addSql('ALTER TABLE product_purchase DROP FOREIGN KEY FK_AAA7BBAC4584665A');
        $this->addSql('ALTER TABLE product_purchase CHANGE quantity quantity INT DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE product_purchase ADD CONSTRAINT `fk_product_purchase_product` FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_purchase ADD CONSTRAINT `fk_product_purchase_user` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_purchase RENAME INDEX idx_aaa7bbac4584665a TO fk_product_purchase_product');
        $this->addSql('ALTER TABLE product_purchase RENAME INDEX idx_aaa7bbaca76ed395 TO fk_product_purchase_user');
        $this->addSql('ALTER TABLE statistic DROP INDEX IDX_649B469C99E6F5DF, ADD UNIQUE INDEX uniq_stat_player (player_id)');
        $this->addSql('ALTER TABLE statistic DROP FOREIGN KEY FK_649B469CE48FD905');
        $this->addSql('ALTER TABLE statistic DROP FOREIGN KEY FK_649B469C296CD8AE');
        $this->addSql('ALTER TABLE statistic DROP FOREIGN KEY FK_649B469C99E6F5DF');
        $this->addSql('ALTER TABLE statistic CHANGE matches_played matches_played INT DEFAULT 0 NOT NULL, CHANGE wins wins INT DEFAULT 0 NOT NULL, CHANGE losses losses INT DEFAULT 0 NOT NULL, CHANGE kills kills INT DEFAULT 0 NOT NULL, CHANGE deaths deaths INT DEFAULT 0 NOT NULL, CHANGE assists assists INT DEFAULT 0 NOT NULL, CHANGE win_rate win_rate NUMERIC(5, 2) DEFAULT \'0.00\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE statistic ADD CONSTRAINT `fk_stat_game` FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE statistic ADD CONSTRAINT `fk_stat_player` FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE statistic ADD CONSTRAINT `fk_stat_team` FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE statistic RENAME INDEX idx_649b469c296cd8ae TO fk_stat_team');
        $this->addSql('ALTER TABLE statistic RENAME INDEX idx_649b469ce48fd905 TO fk_stat_game');
        $this->addSql('ALTER TABLE stream DROP FOREIGN KEY FK_F0E9BE1C99E6F5DF');
        $this->addSql('ALTER TABLE stream CHANGE is_live is_live TINYINT DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE stream ADD CONSTRAINT `FK_stream_player` FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stream RENAME INDEX idx_f0e9be1c99e6f5df TO IDX_stream_player');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61FE48FD905');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F32C8A3DE');
        $this->addSql('ALTER TABLE team CHANGE logo logo VARCHAR(255) DEFAULT \'NULL\', CHANGE country country VARCHAR(80) DEFAULT \'NULL\', CHANGE foundation_year foundation_year SMALLINT DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT `fk_team_game` FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT `FK_team_organization` FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_team_name_game ON team (name, game_id)');
        $this->addSql('ALTER TABLE team RENAME INDEX idx_c4e0a61fe48fd905 TO fk_team_game');
        $this->addSql('ALTER TABLE team RENAME INDEX idx_c4e0a61f32c8a3de TO IDX_team_organization');
        $this->addSql('ALTER TABLE user CHANGE profile_picture profile_picture VARCHAR(255) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE status status ENUM(\'ACTIVE\', \'BANNED\') DEFAULT \'\'\'ACTIVE\'\'\' NOT NULL, CHANGE user_type user_type ENUM(\'VISITOR\', \'REGISTERED\', \'COACH\', \'ADMIN\') DEFAULT \'\'\'REGISTERED\'\'\' NOT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d649f85e0677 TO username');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d649e7927c74 TO email');
    }
}
