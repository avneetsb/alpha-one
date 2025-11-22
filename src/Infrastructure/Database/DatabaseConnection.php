<?php

namespace TradingPlatform\Infrastructure\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseConnection
{
    private static ?DatabaseConnection $instance = null;
    private Capsule $capsule;

    private function __construct()
    {
        $this->capsule = new Capsule;
        $config = require __DIR__ . '/../../../config/database.php';
        
        // Add default connection
        $this->capsule->addConnection($config['connections'][$config['default']]);

        // Make this Capsule instance available globally via static methods... (optional)
        $this->capsule->setAsGlobal();

        // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
        $this->capsule->bootEloquent();
    }

    public static function getInstance(): DatabaseConnection
    {
        if (self::$instance === null) {
            self::$instance = new DatabaseConnection();
        }

        return self::$instance;
    }

    public function getCapsule(): Capsule
    {
        return $this->capsule;
    }
}
