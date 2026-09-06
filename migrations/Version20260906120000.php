<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\AbstractMultiPlatformMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260906120000 extends AbstractMultiPlatformMigration
{
    public function getDescription(): string
    {
        return 'Add a unique optional user-defined barcode to storage locations';
    }

    public function mySQLUp(Schema $schema): void
    {
        $this->addSql('ALTER TABLE storelocations ADD user_barcode VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX storelocations_unique_user_barcode ON storelocations (user_barcode)');
    }

    public function mySQLDown(Schema $schema): void
    {
        $this->addSql('DROP INDEX storelocations_unique_user_barcode ON storelocations');
        $this->addSql('ALTER TABLE storelocations DROP user_barcode');
    }

    public function sqLiteUp(Schema $schema): void
    {
        $this->addSql('ALTER TABLE storelocations ADD COLUMN user_barcode VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX storelocations_unique_user_barcode ON storelocations (user_barcode)');
    }

    public function sqLiteDown(Schema $schema): void
    {
        $this->addSql('DROP INDEX storelocations_unique_user_barcode');
        $this->addSql('ALTER TABLE storelocations DROP COLUMN user_barcode');
    }

    public function postgreSQLUp(Schema $schema): void
    {
        $this->addSql('ALTER TABLE storelocations ADD user_barcode VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX storelocations_unique_user_barcode ON storelocations (user_barcode)');
    }

    public function postgreSQLDown(Schema $schema): void
    {
        $this->addSql('DROP INDEX storelocations_unique_user_barcode');
        $this->addSql('ALTER TABLE storelocations DROP user_barcode');
    }
}
