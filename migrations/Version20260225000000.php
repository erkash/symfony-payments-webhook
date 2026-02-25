<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment idempotency table for create payment operation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE payment_idempotency_keys (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', operation VARCHAR(64) NOT NULL, idempotency_key VARCHAR(128) NOT NULL, payment_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME NOT NULL, INDEX idx_payment_idempotency_payment_id (payment_id), UNIQUE INDEX uniq_payment_idempotency_operation_key (operation, idempotency_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE payment_idempotency_keys ADD CONSTRAINT FK_PAYMENT_IDEMPOTENCY_PAYMENT FOREIGN KEY (payment_id) REFERENCES payments (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment_idempotency_keys DROP FOREIGN KEY FK_PAYMENT_IDEMPOTENCY_PAYMENT');
        $this->addSql('DROP TABLE payment_idempotency_keys');
    }
}
