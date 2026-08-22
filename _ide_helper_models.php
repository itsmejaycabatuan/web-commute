<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * App\Models\DevMarker
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $lat
 * @property string $lng
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Driver $driver
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker query()
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevMarker whereUserId($value)
 * @mixin \Eloquent
 */
	class DevMarker extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Driver
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $driver_code
 * @property string|null $name
 * @property string|null $expiration_date
 * @property string|null $contact_info
 * @property string|null $license_number
 * @property string|null $license_code
 * @property string|null $license_image_path
 * @property string|null $license_status
 * @property int $is_approved
 * @property int $is_rejected
 * @property string|null $status
 * @property string|null $license_image_data
 * @property string|null $license_image_mime
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeKeeping> $timeKeeping
 * @property-read int|null $time_keeping_count
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vehicle> $vehicle
 * @property-read int|null $vehicle_count
 * @method static \Illuminate\Database\Eloquent\Builder|Driver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Driver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Driver query()
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereContactInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereDriverCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereExpirationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereIsApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereIsRejected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLicenseCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLicenseImageData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLicenseImageMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLicenseImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLicenseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLicenseStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereUserId($value)
 * @mixin \Eloquent
 */
	class Driver extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Fare
 *
 * @property int $id
 * @property string $location
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FareRate> $rate
 * @property-read int|null $rate_count
 * @method static \Illuminate\Database\Eloquent\Builder|Fare newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Fare newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Fare query()
 * @method static \Illuminate\Database\Eloquent\Builder|Fare whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fare whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fare whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Fare whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class Fare extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\FareRate
 *
 * @property int $id
 * @property int $fare_id
 * @property int $km
 * @property float $regular
 * @property float $discount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fare $fare
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate query()
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereFareId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereRegular($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FareRate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class FareRate extends \Eloquent {}
}

namespace App\Models{
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
	class MaintenanceTask extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Payment
 *
 * @property int $id
 * @property int $paid_by
 * @property string $transaction_id
 * @property string $starting_point
 * @property string $destination
 * @property string $total_distance
 * @property int $is_discounted
 * @property string $payment_method
 * @property string $price
 * @property string $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereIsDiscounted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaidBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereStartingPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereTotalDistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class Payment extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\PreventiveMaintenance
 *
 * @property int $id
 * @property int $fleet_id
 * @property int $task_id
 * @property int|null $last_service_odo
 * @property Carbon|null $last_service_date
 * @property string|null $last_service_cost
 * @property string|null $comments
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FleetInventory $fleet
 * @property-read MaintenanceTask $maintenanceTask
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance query()
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereFleetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereLastServiceCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereLastServiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereLastServiceOdo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $vehicle_id
 * @property-read \App\Models\Vehicle $vehicle
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VehicleMaintenanceLog> $vehicleMaintenanceLog
 * @property-read int|null $vehicle_maintenance_log_count
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereVehicleId($value)
 */
	class PreventiveMaintenance extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Route
 *
 * @property int $id
 * @property string $starting_point
 * @property string $destination
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Route newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Route newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Route query()
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereStartingPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereUpdatedAt($value)
 * @property string $code
 * @property string $name
 * @property string $description
 * @property string $total_distance
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Route whereTotalDistance($value)
 * @mixin \Eloquent
 */
	class Route extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\TimeKeeping
 *
 * @property int $id
 * @property int $driver_id
 * @property string $date
 * @property string|null $time_in
 * @property string|null $time_out
 * @property string|null $hours_worked
 * @property string|null $overtime_hours
 * @property int|null $sick
 * @property int|null $vacation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Driver $driver
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping query()
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereDriverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereHoursWorked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereOvertimeHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereSick($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereTimeIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereTimeOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereVacation($value)
 * @mixin \Eloquent
 */
	class TimeKeeping extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\TopupHistory
 *
 * @property int $id
 * @property int $user_id
 * @property int $wallet_id
 * @property string $amount_added
 * @property string $payment_method
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Wallet $wallet
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereAmountAdded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereWalletId($value)
 * @mixin \Eloquent
 */
	class TopupHistory extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\User
 *
 * @property int $id
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $license_number
 * @property string|null $license_code
 * @property string|null $license_image_path
 * @property string|null $license_image_data
 * @property string|null $license_image_mime
 * @property string|null $driver_approval_status
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Payment> $payment
 * @property-read int|null $payment_count
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDriverApprovalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLicenseCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLicenseImageData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLicenseImageMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLicenseImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLicenseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutRole($roles, $guard = null)
 * @property string|null $expiration_date
 * @property string|null $contact_info
 * @method static \Illuminate\Database\Eloquent\Builder|User whereContactInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereExpirationDate($value)
 * @property string|null $driver_code
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDriverCode($value)
 * @property-read \App\Models\Driver|null $driver
 * @mixin \Eloquent
 */
	class User extends \Eloquent implements \Illuminate\Contracts\Auth\MustVerifyEmail {}
}

namespace App\Models{
/**
 * App\Models\Vehicle
 *
 * @property int $id
 * @property int|null $driver_id
 * @property int|null $year
 * @property string|null $brand
 * @property string|null $model
 * @property string|null $plate_number
 * @property string|null $status
 * @property string|null $fuel_type
 * @property string|null $tank_capacity
 * @property string|null $vin
 * @property string|null $location
 * @property \Illuminate\Support\Carbon|null $acquistion_date
 * @property \Illuminate\Support\Carbon|null $exp_disposal_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Driver|null $driver
 * @property-read mixed $driver_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VehicleMaintenanceLog> $maintenanceLogs
 * @property-read int|null $maintenance_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PreventiveMaintenance> $preventiveMaintenances
 * @property-read int|null $preventive_maintenances_count
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle query()
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereAcquistionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereDriverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereExpDisposalDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereFuelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle wherePlateNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereTankCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereVin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereYear($value)
 */
	class Vehicle extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\VehicleLocation
 *
 * @property int $id
 * @property string $vehicle_id
 * @property int|null $user_id
 * @property string $latitude
 * @property string $longitude
 * @property string|null $accuracy
 * @property string|null $speed
 * @property string $last_update
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation query()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereAccuracy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereLastUpdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocation whereVehicleId($value)
 * @mixin \Eloquent
 */
	class VehicleLocation extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\VehicleLocationHistory
 *
 * @property int $id
 * @property int $vehicle_location_id
 * @property int|null $user_id
 * @property string $distance_from_last_pos
 * @property string $latitude
 * @property string $longitude
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\VehicleLocation|null $location
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereDistanceFromLastPos($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleLocationHistory whereVehicleLocationId($value)
 * @mixin \Eloquent
 */
	class VehicleLocationHistory extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\VehicleMaintenanceLog
 *
 * @property int $id
 * @property int $fleet_id
 * @property int $maintenance_task_id
 * @property Carbon|null $service_date
 * @property int|null $mileage_at_service
 * @property string|null $performed_by
 * @property string|null $cost
 * @property string|null $invoice_number
 * @property string|null $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FleetInventory $fleet
 * @property-read MaintenanceTask $maintenanceTask
 * @property-read Vehicle|null $vehicle
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereFleetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereMaintenanceTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereMileageAtService($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog wherePerformedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereServiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $maintenance_id
 * @property-read mixed $comments
 * @property-read mixed $last_service_cost
 * @property-read mixed $last_service_date
 * @property-read mixed $last_service_odo
 * @property-read mixed $maintenance_task
 * @property-read \App\Models\PreventiveMaintenance $preventiveMaintenance
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereMaintenanceId($value)
 */
	class VehicleMaintenanceLog extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ViolationCode
 *
 * @property int $id
 * @property string $code
 * @property string $violation_name
 * @property string $first_offense
 * @property string $second_offense
 * @property string $third_offense
 * @property string|null $fourth_offense
 * @property int $is_revoked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode query()
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereFirstOffense($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereFourthOffense($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereIsRevoked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereSecondOffense($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereThirdOffense($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereViolationName($value)
 * @mixin \Eloquent
 */
	class ViolationCode extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ViolationLog
 *
 * @property int $id
 * @property int $user_id
 * @property int $vc_id
 * @property string $violation_instance
 * @property string $violation_fine
 * @property string|null $additional_penalties
 * @property string $date_of_violation
 * @property string $time_of_violation
 * @property string $place_of_violation
 * @property string $remarks
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\ViolationCode $violationCode
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereAdditionalPenalties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereDateOfViolation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog wherePlaceOfViolation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereTimeOfViolation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereVcId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereViolationFine($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereViolationInstance($value)
 * @mixin \Eloquent
 */
	class ViolationLog extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Wallet
 *
 * @property int $id
 * @property int $user_id
 * @property string $balance
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet query()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereUserId($value)
 * @mixin \Eloquent
 */
	class Wallet extends \Eloquent {}
}

