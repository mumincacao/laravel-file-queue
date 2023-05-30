# Laravel 用ファイルキュー

## インストール方法

1. パッケージのインストール

```sh
composer require mumincacao/laravel-file-queue
```

2. 設定

|key|型|必須|デフォルト値|備考|
|----|----|----|----|----|
|queue.connections.file.driver|string|o| file |ドライバ名|
|queue.connections.file.path|string|o| - |キューとして使うディレクトリ。 基本的には storage_path() からの相対パスで指定。|
|queue.connections.file.is_absolute|bool| - |false|キューとして使うディレクトリを絶対パスで指定するときは true にする。|
|queue.connections.file.queue|string| - |default|キュー名を指定しなかったときに使うキューの名称。|
|queue.connections.file.permission|int| - |0777|キュー用ディレクトリを自動作成するときのパーミッション。|

```php
<?php
/// config/queue.php
return [
/// snip
    'connections' => [
/// snip
        'file' => [
            'driver' => 'file',
            'path' => 'queue',
            'is_absolute' => false,
            'queue' => 'default',
            'permission' => 0777,
        ],
    ],
/// snip
];
```

## LICENSE
MIT
