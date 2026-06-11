<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 記事に紐づく添付ファイル（スライド・PDF・画像など）。
        $sql = <<<SQL
        CREATE TABLE note_attachments (
            id CHAR(36) NOT NULL DEFAULT (UUID_TO_BIN(UUID(), 1)),
            note_id CHAR(36) NOT NULL COMMENT "記事ID",
            file_path VARCHAR(2048) NOT NULL COMMENT "保存先パス",
            file_name VARCHAR(255) NOT NULL COMMENT "元のファイル名",
            mime_type VARCHAR(255) NOT NULL COMMENT "MIMEタイプ",
            file_size BIGINT UNSIGNED NOT NULL COMMENT "ファイルサイズ(バイト)",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "作成日",
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "更新日",
            deleted_at TIMESTAMP NULL COMMENT "削除日",
            created_user_id CHAR(36) COMMENT "作成ユーザーID",
            updated_user_id CHAR(36) COMMENT "更新ユーザーID",
            deleted_user_id CHAR(36) COMMENT "削除ユーザーID",
            PRIMARY KEY (id),
            KEY `note_attachments_note_id_index` (note_id)
        );
SQL;
        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_attachments');
    }
};
