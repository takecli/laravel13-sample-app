<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
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
 */
class NoteTag extends Model
{
    use HasUuids;

    protected $table = 'note_tag';

    public $incrementing = false;

    protected $fillable = [
        'note_id',
        'tag_id',
        'created_user_id',
        'updated_user_id',
    ];
}
