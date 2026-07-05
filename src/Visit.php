<?php

declare(strict_types=1);

namespace Skywalker\Footprints;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    /**
     * The name of the database table.
     *
     * @var string
     */
    protected $table = 'visits';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Override constructor to set the table name @ time of instantiation.
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $tableName = config('footprints.table_name');
        $this->setTable(is_string($tableName) ? $tableName : 'visits');

        $connectionName = config('footprints.connection_name');
        if (is_string($connectionName) && $connectionName !== '') {
            $this->setConnection($connectionName);
        }
    }

    /**
     * Get the account that owns the visit.
     *
     * @return BelongsTo<Model, $this>
     */
    public function account(): BelongsTo
    {
        /** @var class-string<Model> $model */
        $model = config('footprints.model') ?: 'App\Models\User';

        $columnName = config('footprints.column_name');
        $columnName = is_string($columnName) ? $columnName : 'user_id';

        return $this->belongsTo($model, $columnName);
    }

    /**
     * Scope a query to only include previous visits.
     * 
     * @param Builder<$this> $query
     * @param string $footprint
     * @return Builder<$this>
     */
    public function scopePreviousVisits(Builder $query, string $footprint): Builder
    {
        return $query->where('footprint', $footprint);
    }

    /**
     * Scope a query to only include previous visits that have been unassigned.
     * 
     * @param Builder<$this> $query
     * @param string $footprint
     * @return Builder<$this>
     */
    public function scopeUnassignedPreviousVisits(Builder $query, string $footprint): Builder
    {
        $columnName = config('footprints.column_name');
        $columnName = is_string($columnName) ? $columnName : 'user_id';
        
        return $query->whereNull($columnName)->where('footprint', $footprint);
    }

    /**
     * Scope a query to only include unassigned visits older than $days days.
     * 
     * @param Builder<$this> $query
     * @param int $days
     * @return Builder<$this>
     */
    public function scopePrunable(Builder $query, int $days): Builder
    {
        $columnName = config('footprints.column_name');
        $columnName = is_string($columnName) ? $columnName : 'user_id';
        
        return $query->whereNull($columnName)->where('created_at', '<=', today()->subDays($days));
    }
}
