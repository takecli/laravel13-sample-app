<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Tag
 *
 * @property string $id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * @property string|null $created_user_id
 * @property string|null $updated_user_id
 * @property string|null $deleted_user_id
 */
class Tag extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'tags';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'created_user_id',
        'updated_user_id',
        'deleted_user_id',
    ];
}
