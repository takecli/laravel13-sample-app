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
        CREATE TABLE note_likes (
            id BINARY(16) NOT NULL DEFAULT (UUID_TO_BIN(UUID(), 1)),
            note_id BINARY(16) NOT NULL COMMENT "記事ID",
            user_id BINARY(16) NOT NULL COMMENT "ユーザーID",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "作成日",
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "更新日",
            created_user_id BINARY(16) COMMENT "作成ユーザーID",
            updated_user_id BINARY(16) COMMENT "更新ユーザーID",
            PRIMARY KEY (id),
            UNIQUE KEY `note_id_and_user_id` (note_id, user_id)
        );
SQL;
        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_likes');
    }
};
