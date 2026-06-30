<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628220009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename "user" table to "users" (user is reserved in PostgreSQL)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" RENAME TO users');
        $this->addSql('ALTER INDEX idx_8d93d649e7927c74 RENAME TO IDX_1483A5E9E7927C74');
        $this->addSql('ALTER INDEX uniq_8d93d649e7927c74 RENAME TO UNIQ_1483A5E9E7927C74');
        $this->addSql('ALTER INDEX idx_8d93d6494af38fd1 RENAME TO IDX_1483A5E94AF38FD1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users RENAME TO "user"');
        $this->addSql('ALTER INDEX IDX_1483A5E9E7927C74 RENAME TO idx_8d93d649e7927c74');
        $this->addSql('ALTER INDEX UNIQ_1483A5E9E7927C74 RENAME TO uniq_8d93d649e7927c74');
        $this->addSql('ALTER INDEX IDX_1483A5E94AF38FD1 RENAME TO idx_8d93d6494af38fd1');
    }
}
