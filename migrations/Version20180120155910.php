<?php declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180120155910 extends AbstractMigration
{
    public function isTransactional() : bool
    {
        return false;
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user CHANGE first_name `first_name` VARCHAR(255) DEFAULT NULL, CHANGE last_name `last_name` VARCHAR(255) DEFAULT NULL, CHANGE email `email` VARCHAR(255) DEFAULT NULL, CHANGE password `password` CHAR(60) DEFAULT NULL, CHANGE api_token `api_token` CHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `user` CHANGE `first_name` first_name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE `last_name` last_name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE `email` email VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE `password` password CHAR(60) NOT NULL COLLATE utf8_unicode_ci, CHANGE `api_token` api_token CHAR(32) NOT NULL COLLATE utf8_unicode_ci');
    }
}
