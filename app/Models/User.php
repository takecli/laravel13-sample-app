<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticated;

/**
 * Class User
 *
 * @property string $id
 * @property string $keycloak_id
 * @property string $name
 * @property string $email
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class User extends Authenticated
{
    use HasFactory, HasUuids;

    protected $table = 'users';

    public $incrementing = false;

    protected $fillable = [
        'keycloak_id',
        'name',
        'email',
    ];
}
