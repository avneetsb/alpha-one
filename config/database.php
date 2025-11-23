<?php

/**
 * Database configuration for the trading platform application.
 *
 * This configuration file defines all database connections used by the trading
 * platform, including primary database connections and Redis cache configuration.
 * The file supports environment-based configuration for different deployment
 * environments (development, staging, production).
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @structure
 * - default: Specifies the default database connection driver
 * - connections: Array of available database connections with their configurations
 * - redis: Redis connection settings for caching and session storage
 * - migrations: Table name for migration tracking
 *
 * @environment_variables
 * - DB_CONNECTION: Default database connection type (mysql, sqlite, etc.)
 * - DB_HOST: Database server hostname or IP address
 * - DB_PORT: Database server port number
 * - DB_DATABASE: Database name
 * - DB_USERNAME: Database username for authentication
 * - DB_PASSWORD: Database password for authentication
 * - DB_SOCKET: Unix socket path (MySQL specific)
 * - DB_FOREIGN_KEYS: Enable/disable foreign key constraints (SQLite)
 * - REDIS_HOST: Redis server hostname or IP address
 * - REDIS_PASSWORD: Redis authentication password
 * - REDIS_PORT: Redis server port number
 * - REDIS_DB: Redis database number
 *
 * @example
 * // Environment configuration examples:
 *
 * // Development environment (.env):
 * DB_CONNECTION=sqlite
 * DB_DATABASE=/path/to/trading_platform_dev.sqlite
 *
 * // Production environment (.env):
 * DB_CONNECTION=mysql
 * DB_HOST=db.trading-platform.com
 * DB_PORT=3306
 * DB_DATABASE=trading_platform_prod
 * DB_USERNAME=trading_user
 * DB_PASSWORD=secure_password_here
 *
 * // Redis configuration:
 * REDIS_HOST=redis.trading-platform.com
 * REDIS_PASSWORD=redis_password
 * REDIS_PORT=6379
 * REDIS_DB=0
 *
 * @usage
 * // Accessing configuration values:
 * $defaultConnection = config('database.default');
 * $mysqlConfig = config('database.connections.mysql');
 * $redisConfig = config('database.redis.default');
 *
 * @important This configuration supports both MySQL and SQLite databases,
 *           making it suitable for development (SQLite) and production (MySQL)
 *           environments without code changes.
 *
 * @note The utf8mb4 charset is used for full Unicode support including
 *       emojis and special characters in strategy names and descriptions.
 */

return [
    /**
     * Default database connection driver.
     *
     * Specifies which database connection should be used by default when
     * no specific connection is requested. This value is typically set via
     * the DB_CONNECTION environment variable.
     *
     * @var string
     *
     * @example 'mysql', 'sqlite', 'pgsql'
     */
    'default' => env('DB_CONNECTION', 'mysql'),

    /**
     * Database connection configurations.
     *
     * Defines all available database connections with their specific
     * configuration parameters. Each connection can be used independently
     * or switched between based on application requirements.
     */
    'connections' => [
        /**
         * MySQL database connection configuration.
         *
         * Production-ready MySQL configuration with UTF-8 support,
         * strict mode for data integrity, and optimized settings
         * for trading platform workloads.
         *
         * @structure
         * - driver: Database driver identifier
         * - host: Database server hostname/IP
         * - port: Database server port (default 3306)
         * MySQL Configuration
         *
         * The primary database for production environments. Optimized for
         * reliability and concurrent access.
         *
         * @driver mysql
         *
         * @charset utf8mb4 (Supports full Unicode, including emojis)
         *
         * @collation utf8mb4_unicode_ci
         *
         * @strict true (Enforces SQL standards for data integrity)
         */
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'trading_platform'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],

        /**
         * SQLite Configuration
         *
         * A lightweight, file-based database ideal for local development and
         * testing. Requires no server setup.
         *
         * @driver sqlite
         *
         * @database Absolute path to the .sqlite file
         *
         * @foreign_key_constraints Enabled by default to ensure referential integrity
         */
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', __DIR__.'/../database/database.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
    ],

    /**
     *           - Rate limiting
     *           - Temporary data storage
     *
     * @environment_variables
     * - REDIS_HOST: Redis server hostname/IP
     * - REDIS_PASSWORD: Redis authentication password
     * - REDIS_PORT: Redis server port (default 6379)
     * - REDIS_DB: Redis database number (0-15)
     *
     * @example Production Redis setup:
     * // High-availability Redis cluster:
     * REDIS_HOST=redis-cluster.trading-platform.internal
     * REDIS_PASSWORD=redis_cluster_password
     * REDIS_PORT=6379
     * REDIS_DB=0
     *
     * @note Redis database numbers allow logical separation of data:
     *       - DB 0: Cache and sessions
     *       - DB 1: Queue jobs
     *       - DB 2: Rate limiting
     *       - DB 3: Temporary trading data
     *
     * @performance Redis provides sub-millisecond latency for cache operations,
     *              making it ideal for real-time trading applications.
     */
    'redis' => [
        'client' => 'predis',
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
        ],
    ],

    /**
     * Migration table name configuration.
     *
     * Specifies the name of the table used to track database migrations.
     * This table stores the history of applied migrations for version control.
     *
     * @var string
     *
     * @default 'migrations'
     *
     * @note The migrations table is automatically created by the migration
     *       system and should not be manually modified.
     *
     * @warning Changing this value after migrations have been run will
     *          cause the migration system to lose track of applied migrations.
     */
    'migrations' => 'migrations',
];
