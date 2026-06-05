<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class User
 * 
 * @property string $id
 * @property string $keycloak_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @package App\Models
 */
class User extends Model
{
	protected $table = 'users';
	public $incrementing = false;

	protected $casts = [
		'id' => 'binary',
		'email_verified_at' => 'datetime'
	];

	protected $fillable = [
		'keycloak_id',
		'name',
		'email',
		'email_verified_at'
	];
}
