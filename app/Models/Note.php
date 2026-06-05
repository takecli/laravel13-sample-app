<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Note
 * 
 * @property string $id
 * @property string $team_id
 * @property string $title
 * @property string $content
 * @property string $status
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * @property string|null $created_user_id
 * @property string|null $updated_user_id
 * @property string|null $deleted_user_id
 *
 * @package App\Models
 */
class Note extends Model
{
	use SoftDeletes;
	protected $table = 'notes';
	public $incrementing = false;

	protected $casts = [
		'id' => 'binary',
		'team_id' => 'binary',
		'published_at' => 'datetime',
		'created_user_id' => 'binary',
		'updated_user_id' => 'binary',
		'deleted_user_id' => 'binary'
	];

	protected $fillable = [
		'team_id',
		'title',
		'content',
		'status',
		'published_at',
		'created_user_id',
		'updated_user_id',
		'deleted_user_id'
	];
}
