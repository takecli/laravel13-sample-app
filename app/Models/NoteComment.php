<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class NoteComment
 * 
 * @property string $id
 * @property string $note_id
 * @property string $comment
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * @property string|null $created_user_id
 * @property string|null $updated_user_id
 * @property string|null $deleted_user_id
 *
 * @package App\Models
 */
class NoteComment extends Model
{
	use SoftDeletes;
	protected $table = 'note_comments';
	public $incrementing = false;

	protected $casts = [
		'id' => 'binary',
		'note_id' => 'binary',
		'created_user_id' => 'binary',
		'updated_user_id' => 'binary',
		'deleted_user_id' => 'binary'
	];

	protected $fillable = [
		'note_id',
		'comment',
		'created_user_id',
		'updated_user_id',
		'deleted_user_id'
	];
}
