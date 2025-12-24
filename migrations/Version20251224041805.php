<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224041805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE asset ADD category VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE asset_transaction ADD tech_approved_by_id INT DEFAULT NULL, ADD accounting_approved_by_id INT DEFAULT NULL, ADD received_by_id INT DEFAULT NULL, ADD tech_approved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD accounting_approved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD received_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE asset_transaction ADD CONSTRAINT FK_F94F5CD843BC34C FOREIGN KEY (tech_approved_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE asset_transaction ADD CONSTRAINT FK_F94F5CD8E55E9209 FOREIGN KEY (accounting_approved_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE asset_transaction ADD CONSTRAINT FK_F94F5CD86F8DDD17 FOREIGN KEY (received_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_F94F5CD843BC34C ON asset_transaction (tech_approved_by_id)');
        $this->addSql('CREATE INDEX IDX_F94F5CD8E55E9209 ON asset_transaction (accounting_approved_by_id)');
        $this->addSql('CREATE INDEX IDX_F94F5CD86F8DDD17 ON asset_transaction (received_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE asset DROP category');
        $this->addSql('ALTER TABLE asset_transaction DROP FOREIGN KEY FK_F94F5CD843BC34C');
        $this->addSql('ALTER TABLE asset_transaction DROP FOREIGN KEY FK_F94F5CD8E55E9209');
        $this->addSql('ALTER TABLE asset_transaction DROP FOREIGN KEY FK_F94F5CD86F8DDD17');
        $this->addSql('DROP INDEX IDX_F94F5CD843BC34C ON asset_transaction');
        $this->addSql('DROP INDEX IDX_F94F5CD8E55E9209 ON asset_transaction');
        $this->addSql('DROP INDEX IDX_F94F5CD86F8DDD17 ON asset_transaction');
        $this->addSql('ALTER TABLE asset_transaction DROP tech_approved_by_id, DROP accounting_approved_by_id, DROP received_by_id, DROP tech_approved_at, DROP accounting_approved_at, DROP received_at');
    }
}
