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

namespace ProophTest\EventStore\Snapshot\Adapter\Doctrine\Schema;

use Doctrine\DBAL\Schema\Schema;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Snapshot\Adapter\Doctrine\Schema\SnapshotStoreSchema;

/**
 * Class SnapshotStoreSchemaTest
 * @package ProophTest\EventStore\Snapshot\Adapter
 */
final class SnapshotStoreSchemaTest extends TestCase
{
    /**
     * @test
     */
    public function it_drops_snapshot_table()
    {
        $schema = $this->prophesize(Schema::class);
        $schema->dropTable('table_name');

        SnapshotStoreSchema::drop($schema->reveal(), 'table_name');
    }
}
