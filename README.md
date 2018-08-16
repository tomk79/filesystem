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

## Usage

Define `tomk79/filesystem` in your `composer.json`.

```json
{
    "require": {
        "php": ">=5.3.0",
        "tomk79/filesystem": "1.*"
    }
}
```

Execute `composer install` command.

```bash
$ composer install
```

Or update command.

```bash
$ composer update
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
  'filesystem_encoding'=>'UTF-8'
));
```

#### API Document

see: docs/index.html


## Test

```
$ cd (project directory)
$ php ./vendor/phpunit/phpunit/phpunit
```

## phpDocumentor

```
$ composer run-script documentation
```


## Change Log

### tomk79/filesystem v1.0.8 (2018-08-??)

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
- website: <http://www.pxt.jp/>
- Twitter: @tomk79 <http://twitter.com/tomk79/>


