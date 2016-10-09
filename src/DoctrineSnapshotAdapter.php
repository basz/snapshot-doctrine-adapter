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

namespace Prooph\EventStore\Snapshot\Adapter\Doctrine;

use Doctrine\DBAL\Connection;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Snapshot\Adapter\Adapter;
use Prooph\EventStore\Snapshot\Snapshot;

/**
 * Class DoctrineSnapshotAdapter
 * @package Prooph\EventStore\Snapshot\Adapter\Doctrine
 */
final class DoctrineSnapshotAdapter implements Adapter
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Custom sourceType to snapshot mapping
     *
     * @var array
     */
    private $snapshotTableMap = [];

    public function __construct(Connection $connection, array $snapshotTableMap = [])
    {
        $this->connection = $connection;
        $this->snapshotTableMap = $snapshotTableMap;
    }

    public function get(AggregateType $aggregateType, string $aggregateId): ?Snapshot
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $table = $this->getTable($aggregateType);
        $queryBuilder
            ->select('*')
            ->from($table, $table)
            ->where('aggregate_type = :aggregate_type')
            ->andWhere('aggregate_id = :aggregate_id')
            ->orderBy('last_version', 'DESC')
            ->setParameter('aggregate_type', $aggregateType->toString())
            ->setParameter('aggregate_id', $aggregateId)
            ->setMaxResults(1);

        $stmt = $queryBuilder->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        return new Snapshot(
            $aggregateType,
            $aggregateId,
            $this->unserializeAggregateRoot($result['aggregate_root']),
            (int) $result['last_version'],
            \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', $result['created_at'], new \DateTimeZone('UTC'))
        );
    }

    public function save(Snapshot $snapshot): void
    {
        $table = $this->getTable($snapshot->aggregateType());

        $this->connection->insert(
            $table,
            [
                'aggregate_type' => $snapshot->aggregateType()->toString(),
                'aggregate_id' => $snapshot->aggregateId(),
                'last_version' => $snapshot->lastVersion(),
                'created_at' => $snapshot->createdAt()->format('Y-m-d\TH:i:s.u'),
                'aggregate_root' => serialize($snapshot->aggregateRoot()),
            ],
            [
                'string',
                'string',
                'integer',
                'string',
                'blob',
            ]
        );

        $queryBuilder = $this->connection->createQueryBuilder();
        $table = $this->getTable($snapshot->aggregateType());
        $queryBuilder
            ->delete($table)
            ->where('aggregate_type = :aggregate_type')
            ->andWhere('aggregate_id = :aggregate_id')
            ->andWhere('last_version < :last_version')
            ->setParameter('aggregate_type', $snapshot->aggregateType()->toString())
            ->setParameter('aggregate_id', $snapshot->aggregateId())
            ->setParameter('last_version', $snapshot->lastVersion());

        $queryBuilder->execute();
    }

    private function getTable(AggregateType $aggregateType): string
    {
        if (isset($this->snapshotTableMap[$aggregateType->toString()])) {
            $tableName = $this->snapshotTableMap[$aggregateType->toString()];
        } else {
            $tableName = strtolower($this->getShortAggregateTypeName($aggregateType));
            if (strpos($tableName, "_snapshot") === false) {
                $tableName.= "_snapshot";
            }
        }
        return $tableName;
    }

    private function getShortAggregateTypeName(AggregateType $aggregateType): string
    {
        $streamName = str_replace('-', '_', $aggregateType->toString());
        return implode('', array_slice(explode('\\', $streamName), -1));
    }

    /**
     * @param string|resource $serialized
     * @return object
     */
    private function unserializeAggregateRoot($serialized)
    {
        if (is_resource($serialized)) {
            $serialized = stream_get_contents($serialized);
        }

        return unserialize($serialized);
    }
}
