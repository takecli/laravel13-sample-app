<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TeamUser
 *
 * @property string $id
 * @property string $team_id
 * @property string $user_id
 * @property string $role
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $created_user_id
 * @property string|null $updated_user_id
 */
class TeamUser extends Model
{
    use HasUuids;

    protected $table = 'team_user';

    public $incrementing = false;

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'created_user_id',
        'updated_user_id',
    ];
}
