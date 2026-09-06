# AGENTS.md

このリポジトリで作業するエージェント向けのインストラクションです。

## プロジェクト概要

`tomk79/filesystem` — PHP 向けのシンプルなファイルシステム操作ユーティリティ (Composer パッケージ / MIT License)。

- 単一クラス `tomk79\filesystem` (`php/filesystem.php`) がすべて。
- 実行時の外部依存はゼロ (`require` は `php: >=7.3.0` のみ)。
- PSR-4 オートロード: `tomk79\` → `php/`
- 動作対象は **PHP 7.3 〜 8.x**、**UNIX 系と Windows の両方**。この2軸の互換性がこのライブラリの存在意義そのものなので、常に意識すること。

```php
$fs = new tomk79\filesystem();
$fs = new tomk79\filesystem(array(
    'file_default_permission' => '775',
    'dir_default_permission'  => '775',
));
```

## ディレクトリ構成

| パス | 内容 |
| --- | --- |
| `php/filesystem.php` | 本体。実質ここだけが実装コード。 |
| `tests/mainTest.php` | PHPUnit のテスト。テストもこの1ファイルのみ。 |
| `tests/data/` | テスト用の固定データ (CSV、タイムスタンプ比較用ファイル)。 |
| `tests/mktest/` | テストの作業用ディレクトリ。`.gitkeep` だけをコミットする。 |
| `docs/` | phpDocumentor が生成した API ドキュメント。**生成物だがコミット対象**。手で編集しない。 |
| `README.md` | 使い方と Change Log。Change Log の一次情報はここ。 |
| `.travis.yml` / `appveyor.yml` | Linux / Windows の CI 設定 (レガシー)。 |
| `phpDocumentor.phar` | ドキュメント生成用。`.gitignore` 済みでコミットされない。 |

`vendor/`、`composer.lock`、`.phpdoc/` はいずれも `.gitignore` 済み。編集・コミットしない。

## コマンド

```bash
composer install                        # 依存 (PHPUnit ~9.5) の導入
composer test                           # = php ./vendor/phpunit/phpunit/phpunit
php ./vendor/phpunit/phpunit/phpunit    # 直接実行も可
composer run-script documentation       # docs/ を再生成 (phpDocumentor.phar が必要)
```

テストは高速 (1秒未満) なので、**コードに触れたら必ず全件実行する**こと。

## 設計上の約束事

新しいメソッドを足すときも、既存のメソッドを直すときも、以下を踏襲する。

### パス表現の3系統

このライブラリの中核。役割を混同しないこと。

- **`normalize_path()`** — スラッシュ区切りに正規化する。Windows のボリュームラベル (`C:\`) は削除。URI スキーム (`https://`) と `//host/` 形式の先頭2スラッシュは温存する。
- **`localize_path()`** — OS の標準表現 (`DIRECTORY_SEPARATOR`) に変換する。
- **`get_realpath()`** — 絶対パスに解決する。PHP の `realpath()` と違い**存在しないパスも解決する**。第2引数 `$cd` でカレントディレクトリを指定できる。
- **`get_relatedpath()`** — `./` から始まる相対パスに変換する。

`_n` / `_l` サフィックスは組み合わせを表す規約:

- `get_realpath_n()` = `get_realpath()` + `normalize_path()`
- `get_realpath_l()` = `get_realpath()` + `localize_path()`
- `get_relatedpath_n()` / `get_relatedpath_l()` も同様

### メソッド実装のパターン

1. **ファイルシステムに触れる public メソッドは、冒頭で引数のパスを `$this->localize_path()` に通す。** その後に PHP のネイティブ関数を呼ぶ。この前処理を省くとWindows で壊れる。
2. **例外を投げない。** 失敗は `false`、判定不能は `null` を返す。既存メソッドの戻り値の型と意味は変えない。
3. `@unlink()` / `@rmdir()` / `@opendir()` のように、失敗が想定される箇所ではエラー抑制演算子を使い、戻り値で判断する。
4. ファイルシステムの状態を変えたあと、判定の前に `clearstatcache()` を呼ぶ。
5. `$options` 配列を受けるメソッドは、冒頭に「Normalize $options」ブロックを置き、`is_array()` チェックと各キーのデフォルト値設定をまとめて行う。

### PHP 7.3 〜 8.x 互換

- 文字列関数に値を渡す前に `?? ''` を付ける (`strlen( $x ?? '' )`、`preg_match( ..., $path ?? '' )`)。PHP 8.1 の null 渡し deprecation 対策で全面的にこうなっている。**新しいコードでも必ず踏襲すること。**
- バージョン差異は `PHP_VERSION_ID` で分岐する。例: `read_csv()` の `escape` デフォルト値 (`PHP_VERSION_ID >= 80000 ? "" : "\\"`)。
- 短縮配列構文 `[]` は使わず、`array()` を使う。既存コード全体がこのスタイル。

### 後方互換性

公開済みのメソッドはシグネチャ・戻り値・挙動を壊さない。振る舞いを変える必要がある場合は README の Change Log に明記する。

なお `$filesystem_encoding` プロパティは v1.2.0 で処理が廃止された残骸。参照・復活させないこと。

## コーディングスタイル

`php/filesystem.php` の既存コードに合わせる。整形ツールは導入されていないので、手で合わせる。

- インデントは**タブ**。
- 波括弧は同じ行、スペースなし: `class filesystem{`、`public function is_unix(){`、`}elseif( ... ){`
- 引数リストの内側にスペースを入れる: `public function copy( $from , $to , $perm = null ){`
- クラス名・メソッド名・変数名は snake_case (クラス名も小文字の `filesystem`)。
- **すべての public メソッドに phpDocumentor 形式の docblock を日本語で書く。** `@param` / `@return` は必須。docs/ の生成物がこれをそのまま使う。
- コード内のコメントも日本語。

## テスト

- `tests/mainTest.php` に追記する。テストファイルを増やすなら `phpunit.xml` の `<testsuite>` にも追加が必要。
- `@dataProvider` でパスのバリエーションを与える。プロバイダには **ASCII パス・日本語 (マルチバイト) パス・Windows 形式の区切り文字**の3種を含めるのが慣習 (`directoryProvider()` 参照)。
- `@depends` でテスト間の依存を宣言する。作成系テスト (`testMkDir`) が作ったものを削除系テスト (`testRmDir`) が消す、という順序依存の構成になっている。
- ファイル操作の前後で `clearstatcache()` を呼ぶ。
- **作業ファイルは `tests/mktest/` 以下にだけ作り、テストの中で必ず片付ける。** テスト実行後に `git status` がクリーンであること。
- テストの説明は日本語の docblock で書く。

## Git / リリース手順

### ブランチとコミット

- `master` が安定版、`develop` が開発版。作業用にバージョン系ブランチ (`1.3.x` など) を切ることがある。
- コミットメッセージは**日本語**。変更点は `` `$fs->method_name()` `` の形式でメソッドを明示する。

例:
```
`$fs->chmod_r()` で、対象のディレクトリのパーミッションが変更されない不具合を修正した。
```

### 変更を入れるとき

README.md の Change Log 先頭に、未リリース版の見出し `### tomk79/filesystem vX.Y.Z (リリース日未定)` を作り、その下に変更点を箇条書きで追記する。

### リリースするとき

1. README.md の Change Log の `(リリース日未定)` を実際の日付 `(YYYY-MM-DD)` に置き換える。
2. `composer.json` の `scripts.documentation` にある `--title "tomk79/filesystem vX.Y.Z API Document"` のバージョンを更新する。
3. `composer run-script documentation` で `docs/` を再生成する。
4. コミットメッセージは `tomk79/filesystem vX.Y.Z` を1行目に置き、空行のあと変更点の箇条書きを続ける。README・composer.json・docs/ を1コミットにまとめる。
5. タグは `v` を付けず `1.3.0` の形式。注釈付きタグ (annotated tag) を打つ。

コミット・タグ付け・push はユーザーから明示的に指示されたときだけ行う。
