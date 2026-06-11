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
        // 認証は Keycloak SSO。password は保持せず、keycloak_id で名寄せする。
        $sql = <<<SQL
        CREATE TABLE users (
            id CHAR(36) NOT NULL,
            keycloak_id VARCHAR(255) NOT NULL COMMENT "Keycloakのsub",
            `name` VARCHAR(255) NOT NULL COMMENT "氏名",
            email VARCHAR(255) NOT NULL COMMENT "メールアドレス",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "作成日",
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "更新日",
            PRIMARY KEY (id),
            UNIQUE KEY `users_keycloak_id_unique` (keycloak_id),
            UNIQUE KEY `users_email_unique` (email)
        );
SQL;
        DB::statement($sql);

        // Laravel のセッション（database ドライバ）用。user_id は UUID に合わせる。
        $sessions = <<<SQL
        CREATE TABLE sessions (
            id VARCHAR(255) NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT "ユーザーID",
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            payload LONGTEXT NOT NULL,
            last_activity INT NOT NULL,
            PRIMARY KEY (id),
            KEY `sessions_user_id_index` (user_id),
            KEY `sessions_last_activity_index` (last_activity)
        );
SQL;
        DB::statement($sessions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
