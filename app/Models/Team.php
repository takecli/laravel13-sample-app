<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Domains\Enums\Team\PublicStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Team
 *
 * @property string $id
 * @property string $name
 * @property string $description
 * @property PublicStatus $public_status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * @property string|null $created_user_id
 * @property string|null $updated_user_id
 * @property string|null $deleted_user_id
 */
class Team extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'teams';

    public $incrementing = false;

    protected $casts = [
        'public_status' => PublicStatus::class,
    ];

    protected $fillable = [
        'name',
        'description',
        'public_status',
        'created_user_id',
        'updated_user_id',
        'deleted_user_id',
    ];

    public function teamUsers(): HasMany
    {
        return $this->hasMany(TeamUser::class);
    }
}
