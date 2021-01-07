<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210107141014 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE currency (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(3) NOT NULL, rate DOUBLE PRECISION NOT NULL)');
        $this->addSql('CREATE TABLE customer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, vat VARCHAR(20) NOT NULL)');
        $this->addSql('CREATE TABLE document (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, customer_id INTEGER NOT NULL, currency_id INTEGER NOT NULL, parent_id INTEGER DEFAULT NULL, number BIGINT NOT NULL, type SMALLINT NOT NULL, total DOUBLE PRECISION NOT NULL)');
        $this->addSql('CREATE INDEX IDX_D8698A769395C3F3 ON document (customer_id)');
        $this->addSql('CREATE INDEX IDX_D8698A7638248176 ON document (currency_id)');
        $this->addSql('CREATE INDEX IDX_D8698A76727ACA70 ON document (parent_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE currency');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE document');
    }
}
