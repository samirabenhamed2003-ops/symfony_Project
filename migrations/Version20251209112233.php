<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209112233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reserver_rendez_vous ADD patient_id INT DEFAULT NULL, CHANGE message message LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE statut statut VARCHAR(20) DEFAULT \'en_attente\' NOT NULL');
        $this->addSql('ALTER TABLE reserver_rendez_vous ADD CONSTRAINT FK_154C3D236B899279 FOREIGN KEY (patient_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_154C3D236B899279 ON reserver_rendez_vous (patient_id)');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE reserver_rendez_vous DROP FOREIGN KEY FK_154C3D236B899279');
        $this->addSql('DROP INDEX IDX_154C3D236B899279 ON reserver_rendez_vous');
        $this->addSql('ALTER TABLE reserver_rendez_vous DROP patient_id, CHANGE message message TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE statut statut VARCHAR(20) DEFAULT \'\'\'en_attente\'\'\' NOT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
    }
}
