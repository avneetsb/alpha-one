<?php

namespace TradingPlatform\Infrastructure\Queue;

use Illuminate\Container\Container;
use Illuminate\Queue\Capsule\Manager as QueueCapsule;
use Illuminate\Redis\RedisManager;

/**
 * Class QueueService
 *
 * Queue Service Bootstrapper.
 * Initializes and provides access to the queue capsule backed by Redis.
 * Supports multiple named queues and connection retrieval for producers
 * and consumers.
 *
 * @version 1.0.0
 *
 * @example Boot and push job:
 * QueueService::boot();
 * QueueService::getConnection('high')->push(new ProcessOrderJob($payload));
 */
class QueueService
{
    /**
     * @var QueueCapsule|null Singleton queue capsule instance.
     */
    private static ?QueueCapsule $capsule = null;

    /**
     * Initialize the queue capsule if not already booted.
     */
    public static function boot(): void
    {
        if (self::$capsule !== null) {
            return;
        }

        $container = new Container;
        $config = require __DIR__.'/../../../config/queue.php';
        $dbConfig = require __DIR__.'/../../../config/database.php'; // For Redis config

        // We need to configure Redis for the queue driver
        $container->bind('redis', function () use ($dbConfig) {
            return new RedisManager(
                new Container,
                'predis',
                $dbConfig['redis']
            );
        });

        // Create Queue Capsule
        $capsule = new QueueCapsule($container);

        // Add connections
        $capsule->addConnection($config['connections']['redis'], 'redis');

        // Set as global to allow Queue::push usage if needed,
        // though we prefer dependency injection or the capsule instance.
        $capsule->setAsGlobal();

        self::$capsule = $capsule;
    }

    /**
     * Get the initialized queue capsule instance.
     */
    public static function getCapsule(): QueueCapsule
    {
        if (self::$capsule === null) {
            self::boot();
        }

        return self::$capsule;
    }

    /**
     * Get a queue connection by name or default.
     *
     * @param  string|null  $name  Connection name (optional).
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public static function getConnection(?string $name = null)
    {
        if ($name === null) {
            $config = require __DIR__.'/../../../config/queue.php';
            $name = $config['default'];
        }

        return self::getCapsule()->getConnection($name);
    }
}
