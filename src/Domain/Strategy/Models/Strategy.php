<?php

namespace TradingPlatform\Domain\Strategy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Strategy extends Model
{
    protected $fillable = [
        'name',
        'class_name',
        'description',
        'category',
        'version',
        'hyperparameters_schema',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'hyperparameters_schema' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all configurations for this strategy
     */
    public function configurations(): HasMany
    {
        return $this->hasMany(StrategyConfiguration::class);
    }

    /**
     * Get all optimization runs for this strategy
     */
    public function optimizationRuns(): HasMany
    {
        return $this->hasMany(OptimizationRun::class);
    }

    /**
     * Get active configurations
     */
    public function activeConfigurations(): HasMany
    {
        return $this->configurations()->whereNull('deleted_at');
    }

    /**
     * Get favorite configurations
     */
    public function favoriteConfigurations(): HasMany
    {
        return $this->configurations()->where('is_favorite', true);
    }

    /**
     * Scope: Only active strategies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the instantiated strategy class
     */
    public function getInstance(array $hyperparameters = [])
    {
        $className = $this->class_name;
        if (!class_exists($className)) {
            throw new \Exception("Strategy class {$className} not found");
        }

        // Pass name and config (hyperparameters) to constructor
        $instance = new $className($this->name, $hyperparameters);

        return $instance;
    }
}
