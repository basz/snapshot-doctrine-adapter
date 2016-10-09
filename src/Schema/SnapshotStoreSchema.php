<?php
/**
 * This file is part of the prooph/snapshot-doctrine-adapter.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Snapshot\Adapter\Doctrine\Schema;

use Doctrine\DBAL\Schema\Schema;

/**
 * Class SnapshotStoreSchema
 *
 * Use this helper in a doctrine migrations script to set up the snapshot store schema
 *
 * @package Prooph\EventStore\Snapshot\Adapter\Doctrine\Schema
 */
final class SnapshotStoreSchema
{
    /**
     * Use this method when you work with a single stream strategy
     */
    public static function create(Schema $schema, string $snapshotName = 'snapshot'): void
    {
        $snapshot = $schema->createTable($snapshotName);

        $snapshot->addColumn('aggregate_type', 'string');
        $snapshot->addColumn('aggregate_id', 'string');
        $snapshot->addColumn('last_version', 'integer');
        $snapshot->addColumn('created_at', 'string');
        $snapshot->addColumn('aggregate_root', 'blob');

        $snapshot->addIndex(['aggregate_type', 'aggregate_id']);
    }

    /**
     * Drop a stream schema
     */
    public static function drop(Schema $schema, string $snapshotName = 'snapshot'): void
    {
        $schema->dropTable($snapshotName);
    }
}
