<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Harden webhook events with dedupe and audit payload/signature storage.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE webhook_events ADD raw_payload LONGTEXT DEFAULT NULL, ADD signature VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE webhook_events SET raw_payload = CAST(payload AS CHAR) WHERE raw_payload IS NULL');
        $this->addSql('ALTER TABLE webhook_events MODIFY raw_payload LONGTEXT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_webhook_provider_external_id ON webhook_events (provider, external_id)');
        $this->addSql('CREATE INDEX idx_webhook_external_id ON webhook_events (external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_webhook_provider_external_id ON webhook_events');
        $this->addSql('DROP INDEX idx_webhook_external_id ON webhook_events');
        $this->addSql('ALTER TABLE webhook_events DROP raw_payload, DROP signature');
    }
}
