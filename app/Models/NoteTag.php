<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class NoteTag
 * 
 * @property string $id
 * @property string $note_id
 * @property string $tag_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $created_user_id
 * @property string|null $updated_user_id
 *
 * @package App\Models
 */
class NoteTag extends Model
{
	protected $table = 'note_tag';
	public $incrementing = false;

	protected $casts = [
		'id' => 'binary',
		'note_id' => 'binary',
		'tag_id' => 'binary',
		'created_user_id' => 'binary',
		'updated_user_id' => 'binary'
	];

	protected $fillable = [
		'note_id',
		'tag_id',
		'created_user_id',
		'updated_user_id'
	];
}
