<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial payments and webhook events tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE payments (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', amount INT NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(32) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE webhook_events (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', provider VARCHAR(32) NOT NULL, external_id VARCHAR(128) DEFAULT NULL, payload JSON NOT NULL, signature_valid TINYINT(1) NOT NULL, received_at DATETIME NOT NULL, processed_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE webhook_events');
        $this->addSql('DROP TABLE payments');
    }
}
