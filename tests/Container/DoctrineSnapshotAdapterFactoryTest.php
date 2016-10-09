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

namespace ProophTest\EventStore\Snapshot\Adapter\Doctrine\Container;

use Doctrine\DBAL\Connection;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Snapshot\Adapter\Doctrine\Container\DoctrineSnapshotAdapterFactory;
use Prooph\EventStore\Snapshot\Adapter\Doctrine\DoctrineSnapshotAdapter;

/**
 * Class DoctrineSnapshotAdapterFactoryTest
 * @package ProophTest\EventStore\Snapshot\Adapter
 */
final class DoctrineSnapshotAdapterFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_adapter_with_connection_options()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'prooph' => [
                'snapshot_store' => [
                    'adapter' => [
                        'type' => DoctrineSnapshotAdapter::class,
                        'options' => [
                            'connection' => [
                                'driver' => 'pdo_sqlite',
                                'dbname' => ':memory:'
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        $factory = new DoctrineSnapshotAdapterFactory();
        $adapter = $factory($container->reveal());
        $this->assertInstanceOf(DoctrineSnapshotAdapter::class, $adapter);
    }

    /**
     * @test
     */
    public function it_creates_adapter_with_connection_alias()
    {
        $connection = $this->prophesize(Connection::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'prooph' => [
                'snapshot_store' => [
                    'adapter' => [
                        'type' => DoctrineSnapshotAdapter::class,
                        'options' => [
                            'connection_alias' => 'my_connection'
                        ]
                    ]
                ]
            ]
        ]);
        $container->has('my_connection')->willReturn(true);
        $container->get('my_connection')->willReturn($connection->reveal());
        $factory = new DoctrineSnapshotAdapterFactory();
        $adapter = $factory($container->reveal());
        $this->assertInstanceOf(DoctrineSnapshotAdapter::class, $adapter);
    }

    /**
     * @test
     */
    public function it_creates_adapter_with_snapshot_table_map()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'prooph' => [
                'snapshot_store' => [
                    'adapter' => [
                        'type' => DoctrineSnapshotAdapter::class,
                        'options' => [
                            'connection' => [
                                'driver' => 'pdo_sqlite',
                                'dbname' => ':memory:'
                            ],
                            'snapshot_table_map' => [
                                'foo' => 'bar'
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        $factory = new DoctrineSnapshotAdapterFactory();
        $adapter = $factory($container->reveal());
        $this->assertInstanceOf(DoctrineSnapshotAdapter::class, $adapter);
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Exception\ConfigurationException
     * @expectedExceptionMessage [Configuration Error] Prooph\EventStore\Snapshot\Adapter\Doctrine\Container\DoctrineSnapshotAdapterFactory was not able to locate or create a valid Doctrine\DBAL\Connection
     */
    public function it_throws_exception_when_no_connection_found()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'prooph' => [
                'snapshot_store' => [
                    'adapter' => [
                        'type' => DoctrineSnapshotAdapter::class,
                        'options' => [
                        ]
                    ]
                ]
            ]
        ]);
        $factory = new DoctrineSnapshotAdapterFactory();
        $factory($container->reveal());
    }
}
