#!/usr/bin/env bash
# PostToolUse フック: Claude が編集した *.php を Pint で自動整形する。
#
# - Claude Code が Edit/Write/MultiEdit を実行した直後に呼ばれる。
# - 対象ファイルが *.php のときだけ Docker 経由で Pint をかける。
# - 整形フローを止めないよう「非ブロッキング」（失敗しても常に exit 0）。
#   Docker 未起動などで pint が動かない場合も黙って素通りする。
set -u

# hook 入力 JSON（stdin）から編集対象ファイルパスを取り出す。
input="$(cat)"
file="$(printf '%s' "$input" | python3 -c 'import json,sys; print(json.load(sys.stdin).get("tool_input",{}).get("file_path",""))' 2>/dev/null)"

[ -z "$file" ] && exit 0
case "$file" in
  *.php) ;;
  *) exit 0 ;;
esac

# 絶対パス → リポジトリルートからの相対パス（app コンテナの作業ディレクトリ /work 基準）。
proj="${CLAUDE_PROJECT_DIR:-$(pwd)}"
rel="${file#"$proj"/}"

docker compose exec -T app ./vendor/bin/pint "$rel" >/dev/null 2>&1 || true
exit 0
