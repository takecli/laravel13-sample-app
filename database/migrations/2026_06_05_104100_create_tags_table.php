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
        $sql = <<<SQL
        CREATE TABLE tags (
            id CHAR(36) NOT NULL DEFAULT (UUID_TO_BIN(UUID(), 1)),
            `name` VARCHAR(255) NOT NULL COMMENT "タグ名",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "作成日",
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "更新日",
            deleted_at TIMESTAMP NULL COMMENT "削除日",
            created_user_id CHAR(36) COMMENT "作成ユーザーID",
            updated_user_id CHAR(36) COMMENT "更新ユーザーID",
            deleted_user_id CHAR(36) COMMENT "削除ユーザーID",
            PRIMARY KEY (id),
            UNIQUE KEY `tags_name_unique` (`name`)
        );
SQL;
        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
