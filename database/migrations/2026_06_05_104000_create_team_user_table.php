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
        // ユーザーとチームの多対多。role でチーム内の権限を表現する。
        $sql = <<<SQL
        CREATE TABLE team_user (
            id CHAR(36) NOT NULL DEFAULT (UUID_TO_BIN(UUID(), 1)),
            team_id CHAR(36) NOT NULL COMMENT "チームID",
            user_id CHAR(36) NOT NULL COMMENT "ユーザーID",
            role ENUM('admin', 'member') NOT NULL DEFAULT 'member' COMMENT "チーム内ロール",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "作成日",
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "更新日",
            created_user_id CHAR(36) COMMENT "作成ユーザーID",
            updated_user_id CHAR(36) COMMENT "更新ユーザーID",
            PRIMARY KEY (id),
            UNIQUE KEY `team_id_and_user_id` (team_id, user_id),
            KEY `team_user_user_id_index` (user_id)
        );
SQL;
        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};
