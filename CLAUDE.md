# CLAUDE.md

このリポジトリのインストラクションは [AGENTS.md](./AGENTS.md) に集約されています。
作業を始める前に `./AGENTS.md` を読み、その内容に従ってください。

Claude Code 固有の補足:

- 変更後は `composer test` を実行し、`git status` がクリーンであることを確認してください (テストが作った一時ファイルの消し忘れを検出できます)。
- `vendor/`、`docs/`、`composer.lock`、`.phpdoc/` は編集対象ではありません。`docs/` は `composer run-script documentation` で再生成します。
