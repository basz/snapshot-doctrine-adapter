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

namespace Prooph\EventStore\Snapshot\Adapter\Doctrine\Container;

use Doctrine\DBAL\DriverManager;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfig;
use Interop\Config\RequiresConfigId;
use Interop\Container\ContainerInterface;
use Prooph\EventStore\Adapter\Exception\ConfigurationException;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Snapshot\Adapter\Doctrine\DoctrineSnapshotAdapter;

/**
 * Class DoctrineSnapshotAdapterFactory
 * @package Prooph\EventStore\Snapshot\Adapter\Doctrine\Container
 */
final class DoctrineSnapshotAdapterFactory implements ProvidesDefaultOptions, RequiresConfig, RequiresConfigId
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $configId;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'prooph.snaptshot_store.service_name' => [MemcachedSnapshotAdapterFactory::class, 'service_name'],
     * ];
     * </code>
     *
     * @throws InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments): DoctrineSnapshotAdapter
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }
        return (new static($name))->__invoke($arguments[0]);
    }

    public function __construct(string $configId = 'default')
    {
        $this->configId = $configId;
    }

    public function __invoke(ContainerInterface $container): DoctrineSnapshotAdapter
    {
        $config = $container->get('config');
        $config = $this->options($config, $this->configId)['adapter']['options'];

        if (isset($config['connection_alias']) && $container->has($config['connection_alias'])) {
            $connection = $container->get($config['connection_alias']);
        } elseif (isset($config['connection']) && is_array($config['connection'])) {
            $connection = DriverManager::getConnection($config['connection']);
        }

        if (! isset($connection)) {
            throw new ConfigurationException(sprintf(
                '%s was not able to locate or create a valid Doctrine\DBAL\Connection',
                __CLASS__
            ));
        }

        return new DoctrineSnapshotAdapter($connection, $config['snapshot_table_map']);
    }

    public function dimensions(): array
    {
        return ['prooph', 'snapshot_store'];
    }

    public function defaultOptions(): array
    {
        return [
            'adapter' => [
                'options' => [
                    'snapshot_table_map' => []
                ],
            ],
        ];
    }
}
