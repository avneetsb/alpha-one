<?php

namespace TradingPlatform\Infrastructure\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Class: Database Connection
 *
 * Singleton wrapper for Illuminate Database Capsule.
 * Initializes the Eloquent ORM and manages the global database connection.
 */
class DatabaseConnection
{
    private static ?DatabaseConnection $instance = null;

    private Capsule $capsule;

    private function __construct()
    {
        $this->capsule = new Capsule;
        $config = require __DIR__.'/../../../config/database.php';

        // Add default connection
        $this->capsule->addConnection($config['connections'][$config['default']]);

        // Make this Capsule instance available globally via static methods... (optional)
        $this->capsule->setAsGlobal();

        // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
        $this->capsule->bootEloquent();
    }

    /**
     * Get the singleton database connection instance.
     *
     * @return DatabaseConnection The shared instance.
     *
     * @example
     * ```php
     * $db = DatabaseConnection::getInstance();
     * $users = $db->getCapsule()->table('users')->get();
     * ```
     */
    public static function getInstance(): DatabaseConnection
    {
        if (self::$instance === null) {
            self::$instance = new DatabaseConnection;
        }

        return self::$instance;
    }

    public function getCapsule(): Capsule
    {
        return $this->capsule;
    }
}
