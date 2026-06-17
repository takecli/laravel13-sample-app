<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = <<<'SQL'
        CREATE TABLE notes (
            id CHAR(36) NOT NULL,
            team_id CHAR(36) NOT NULL COMMENT "チームID",
            title VARCHAR(255) NOT NULL COMMENT "タイトル",
            content TEXT NOT NULL COMMENT "本文",
            status ENUM('draft', 'published') NOT NULL DEFAULT 'draft' COMMENT "公開ステータス(下書き/公開)",
            published_at TIMESTAMP NULL COMMENT "公開日時",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "作成日",
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "更新日",
            deleted_at TIMESTAMP NULL COMMENT "削除日",
            created_user_id CHAR(36) COMMENT "作成ユーザーID",
            updated_user_id CHAR(36) COMMENT "更新ユーザーID",
            deleted_user_id CHAR(36) COMMENT "削除ユーザーID",
            PRIMARY KEY (id),
            KEY `notes_team_id_index` (team_id)
        );
SQL;
        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
