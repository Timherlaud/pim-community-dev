<?php declare(strict_types=1);

namespace Pim\Upgrade\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * This migration
 * - Removes the table `pim_catalog_completeness_missing_attribute`
 * - Drops the column ratio from table `pim_catalog_completeness`
 */
final class Version_3_2_20190813091149_remove_missing_attributes_and_ratio_from_completeness extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('DROP TABLE pim_catalog_completeness_missing_attribute');
        $this->addSql('ALTER TABLE pim_catalog_completeness DROP COLUMN ratio');
    }

    public function down(Schema $schema) : void
    {
        throw new IrreversibleMigrationException();
    }
}
