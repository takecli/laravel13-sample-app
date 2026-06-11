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
 * Class NoteAttachment
 *
 * @property string $id
 * @property string $note_id
 * @property string $file_path
 * @property string $file_name
 * @property string $mime_type
 * @property int $file_size
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * @property string|null $created_user_id
 * @property string|null $updated_user_id
 * @property string|null $deleted_user_id
 */
class NoteAttachment extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'note_attachments';

    public $incrementing = false;

    protected $casts = [
        'file_size' => 'int',
    ];

    protected $fillable = [
        'note_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'created_user_id',
        'updated_user_id',
        'deleted_user_id',
    ];
}
