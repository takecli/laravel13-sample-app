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
        // 記事とタグの多対多。
        $sql = <<<'SQL'
        CREATE TABLE note_tag (
            id CHAR(36) NOT NULL,
            note_id CHAR(36) NOT NULL COMMENT "記事ID",
            tag_id CHAR(36) NOT NULL COMMENT "タグID",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "作成日",
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "更新日",
            created_user_id CHAR(36) COMMENT "作成ユーザーID",
            updated_user_id CHAR(36) COMMENT "更新ユーザーID",
            PRIMARY KEY (id),
            UNIQUE KEY `note_id_and_tag_id` (note_id, tag_id),
            KEY `note_tag_tag_id_index` (tag_id)
        );
SQL;
        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_tag');
    }
};
