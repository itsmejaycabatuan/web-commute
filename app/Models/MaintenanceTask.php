<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\MaintenanceTask
 *
 * @property int $id
 * @property string $tasks_performed
 * @property int|null $miles_between_service
 * @property int|null $months_between_service
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceTask query()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceTask whereMilesBetweenService($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceTask whereMonthsBetweenService($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceTask whereTasksPerformed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceTask whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MaintenanceTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'tasks_performed',
        'miles_between_service',
        'months_between_service',
    ];
}
