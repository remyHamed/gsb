<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261103134311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accounts (id UUID NOT NULL, email VARCHAR(255) NOT NULL, password_hash VARCHAR(255) NOT NULL, roles JSON NOT NULL, is_active BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CAC89EACE7927C74 ON accounts (email)');
        $this->addSql('CREATE TABLE employees (id UUID NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, date_anciennete DATE NOT NULL, weekly_hours INT NOT NULL, available_slots JSON NOT NULL, dates_exclues JSON NOT NULL, skills JSON NOT NULL, is_active BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, account_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BA82C3009B6B5FBA ON employees (account_id)');
        $this->addSql('CREATE TABLE shift (id UUID NOT NULL, date DATE NOT NULL, start_time VARCHAR(5) NOT NULL, end_time VARCHAR(5) NOT NULL, skills_required JSON NOT NULL, week_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_A50B3B45C86F3B2F ON shift (week_id)');
        $this->addSql('CREATE TABLE shift_employee (shift_id UUID NOT NULL, employee_id UUID NOT NULL, PRIMARY KEY (shift_id, employee_id))');
        $this->addSql('CREATE INDEX IDX_742BED05BB70BC0E ON shift_employee (shift_id)');
        $this->addSql('CREATE INDEX IDX_742BED058C03F15C ON shift_employee (employee_id)');
        $this->addSql('CREATE TABLE week (id UUID NOT NULL, year INT NOT NULL, week_number INT NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5B5A69C0BB827337E2EA6607 ON week (year, week_number)');
        $this->addSql('ALTER TABLE employees ADD CONSTRAINT FK_BA82C3009B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE shift ADD CONSTRAINT FK_A50B3B45C86F3B2F FOREIGN KEY (week_id) REFERENCES week (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE shift_employee ADD CONSTRAINT FK_742BED05BB70BC0E FOREIGN KEY (shift_id) REFERENCES shift (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shift_employee ADD CONSTRAINT FK_742BED058C03F15C FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employees DROP CONSTRAINT FK_BA82C3009B6B5FBA');
        $this->addSql('ALTER TABLE shift DROP CONSTRAINT FK_A50B3B45C86F3B2F');
        $this->addSql('ALTER TABLE shift_employee DROP CONSTRAINT FK_742BED05BB70BC0E');
        $this->addSql('ALTER TABLE shift_employee DROP CONSTRAINT FK_742BED058C03F15C');
        $this->addSql('DROP TABLE accounts');
        $this->addSql('DROP TABLE employees');
        $this->addSql('DROP TABLE shift');
        $this->addSql('DROP TABLE shift_employee');
        $this->addSql('DROP TABLE week');
    }
}
