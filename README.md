# tomk79/filesystem

<table>
  <thead>
    <tr>
      <th></th>
      <th>Linux</th>
      <th>Windows</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th>master</th>
      <td align="center">
        <a href="https://travis-ci.org/tomk79/filesystem"><img src="https://secure.travis-ci.org/tomk79/filesystem.svg?branch=master"></a>
      </td>
      <td align="center">
        <a href="https://ci.appveyor.com/project/tomk79/filesystem"><img src="https://ci.appveyor.com/api/projects/status/n8r19nmfvqs5ndr8/branch/master?svg=true"></a>
      </td>
    </tr>
    <tr>
      <th>develop</th>
      <td align="center">
        <a href="https://travis-ci.org/tomk79/filesystem"><img src="https://secure.travis-ci.org/tomk79/filesystem.svg?branch=develop"></a>
      </td>
      <td align="center">
        <a href="https://ci.appveyor.com/project/tomk79/filesystem"><img src="https://ci.appveyor.com/api/projects/status/n8r19nmfvqs5ndr8/branch/develop?svg=true"></a>
      </td>
    </tr>
  </tbody>
</table>



## Install

```bash
$ composer require tomk79/filesystem;
```



### PHP

#### Basic

```php
<?php
require_once('./vendor/autoload.php');
$fs = new tomk79\filesystem();
```

#### Optional

```php
<?php
require_once('./vendor/autoload.php');
$fs = new tomk79\filesystem(array(
  'file_default_permission'=>'775',
  'dir_default_permission'=>'775',
));
```

#### API Document

see: docs/index.html


## Test

```bash
$ cd (project directory)
$ php ./vendor/phpunit/phpunit/phpunit
```

## phpDocumentor

```bash
$ wget https://phpdoc.org/phpDocumentor.phar;
$ composer run-script documentation;
```

## Change Log

### tomk79/filesystem v1.2.4 (2025-01-18)

- `$fs->read_csv()` のオプションに `escape` を追加。

### tomk79/filesystem v1.2.3 (2023-06-25)

- `$fs->chmod_r()` で、対象のディレクトリのパーミッションが変更されない不具合を修正した。

### tomk79/filesystem v1.2.2 (2023-02-11)

- Windowsで、排他ロックされたファイルの削除を試みたときに起きる不具合を修正。

### tomk79/filesystem v1.2.1 (2023-02-05)

- 内部コードの細かい修正。

### tomk79/filesystem v1.2.0 (2022-12-29)

- `filesystem_encoding` の処理を廃止した。(Windowsで起きる問題の回避のため)
- Windows: `$fs->get_realpath()` で、相対パス指定がルートに到達したとき、先頭の `DIRECTORY_SEPARATOR` が2重に付与される場合がある問題を修正した。

### tomk79/filesystem v1.1.2 (2022-12-28)

- detect order の全体を指示できるようになった。

### tomk79/filesystem v1.1.1 (2022-01-08)

- PHP 8.1 で起きる不具合を修正。

### tomk79/filesystem v1.1.0 (2022-01-04)

- サポートするPHPのバージョンを `>=7.3.0` に変更。

### tomk79/filesystem v1.0.12 (2021-04-23)

- 内部コードの細かい修正。

### tomk79/filesystem v1.0.11 (2020-10-17)

- 細かい不具合を修正。

### tomk79/filesystem v1.0.10 (2020-08-20)

- `rm()` が、シンボリックリンクを削除できない不具合を修正。

### tomk79/filesystem v1.0.9 (2020-06-07)

- `chmod_r()` を追加。

### tomk79/filesystem v1.0.8 (2018-08-16)

- `is_link()` を追加。
- その他、内部処理の調整。

### tomk79/filesystem v1.0.7 (2018-08-08)

- オプションに連想配列を受け取れない不具合を修正。

### tomk79/filesystem v1.0.6 (2016-09-05)

- normalize_path() が、 `C:\\` から始まるパスを `//` から始まるパスに変換するようになった。

### tomk79/filesystem v1.0.5 (2015-09-03)

- `normalize_path()` が、URIスキームを含むパス、ドメイン名を含む2つのスラッシュから始まるパスを処理できるようになった。


## License

MIT License


## Author

- (C)Tomoya Koyanagi <tomk79@gmail.com>
- website: <https://www.pxt.jp/>
- Twitter: @tomk79 <https://twitter.com/tomk79/>
