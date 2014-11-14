tomk79/filesystem
=================

## Usage

Define `tomk79/filesystem` in your `composer.json`.

```
{
    "require": {
        "php": ">=5.3.0",
        "tomk79/filesystem": "1.*"
    }
}
```

Execute `composer install` command.

```
$ composer install
```

Or update command.

```
$ composer update
```


### PHP

#### Basic

```
<?php
require_once('./vendor/autoload.php');
$fs = new tomk79\filesystem();
```

#### Optional

```
<?php
require_once('./vendor/autoload.php');
$fs = new tomk79\filesystem(array(
  'file_default_permission'=>'775',
  'dir_default_permission'=>'775',
  'filesystem_encoding'=>'UTF-8'
));
```


## Test

```
$ cd (project directory)
$ ./vendor/phpunit/phpunit/phpunit php/tests/filesystemTest
```

