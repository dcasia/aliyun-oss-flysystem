# Aliyun OSS Flysystem

💾 Flysystem Adapter for [Aliyun Object Storage Service](http://oss.aliyun.com).

[![Build Status](https://github.com/alphasnow/aliyun-oss-flysystem/workflows/CI/badge.svg)](https://github.com/alphasnow/aliyun-oss-flysystem/actions)
[![Latest Stable Version](https://poser.pugx.org/alphasnow/aliyun-oss-flysystem/v/stable)](https://packagist.org/packages/alphasnow/aliyun-oss-flysystem)
[![Code Coverage](https://scrutinizer-ci.com/g/alphasnow/aliyun-oss-flysystem/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/alphasnow/aliyun-oss-flysystem/?branch=master)
[![License](https://poser.pugx.org/alphasnow/aliyun-oss-flysystem/license)](https://packagist.org/packages/alphasnow/aliyun-oss-flysystem)
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Falphasnow%2Faliyun-oss-flysystem.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2Falphasnow%2Faliyun-oss-flysystem?ref=badge_shield)

## Compatibility

| **php**  | **flysystem**  |  **aliyun-oss-flysystem** |
|---|---|---|
|>=5.5.9,\<7.0| ~1.0.0  | ^0.3  |
|>=7.0| ^1.0 | ^1.0  |

## Installation

```bash
composer require "alphasnow/aliyun-oss-flysystem" -vvv
```

## Usage

### Initialize
```php
use OSS\OssClient;
use League\Flysystem\Filesystem;
use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapter;
use AlphaSnow\Flysystem\AliyunOss\Plugins\AppendContent;
use AlphaSnow\Flysystem\AliyunOss\Plugins\GetTemporaryUrl;

$config = [
    "access_id" => "LTAI4**************qgcsA",        // Required, AccessKey
    "access_key" => "PkT4F********************Bl9or", // Required, AccessKey Key Secret
    "endpoint" => "oss-cn-shanghai.aliyuncs.com",     // Required, Endpoint
    "bucket" => "my-storage",                         // Required, Bucket
    "prefix" => "",
    "options" => [
        "checkmd5" => false
    ]
];

$client = new OssClient($config['access_id'], $config['access_key'], $config['endpoint']);
$adapter = new AliyunOssAdapter($client, $config['bucket'], $config['prefix'], $config['options']);
$flysystem = new Filesystem($adapter, ["disable_asserts" => true,"case_sensitive" => true]);
$flysystem->addPlugin(new AppendContent());
$flysystem->addPlugin(new GetTemporaryUrl());
```

### Methods
```php
$flysystem->write('file.md', 'contents');
$flysystem->writeStream('file.md', fopen('file.md', 'r'));
$flysystem->update('file.md', 'new contents');
$flysystem->updateStream('file.md', fopen('file.md', 'r'));
$flysystem->put('file.md', 'contents');
$flysystem->putStream('file.md', fopen('file.md', 'r'));

$flysystem->rename('baz.md', 'bar.md');
$flysystem->copy('foo.md', 'baz.md');
$flysystem->delete('bar.md');
$flysystem->has('bar.md');

$flysystem->read('file.md');
$flysystem->readStream('file.md');
$flysystem->readAndDelete('file.md');

$flysystem->listContents('/');
$flysystem->listContents('/',true);

$flysystem->createDir('foo/');
$flysystem->deleteDir('foo/');

$flysystem->setVisibility('foo/bar','public');
$flysystem->getVisibility('foo/bar');

$flysystem->getMetadata('file.md');
$flysystem->getSize('file.md');
$flysystem->getMimetype('file.md');
$flysystem->getTimestamp('file.md');
```

### Plugins
```php
$flysystem->appendContent('foo.md', 'contents', 0);
$flysystem->getTemporaryUrl('foo.md', 3600);
```

### Options
```php
$flysystem->write('file.md', 'contents', [
    "options" => ["length" => 8]
]);
$flysystem->write('file.md', 'contents', [
    "headers" => ["Content-Disposition" => "attachment; filename=file.md"]
]);
$flysystem->write('file.md', 'contents', [
    "visibility" => "private"
]);
```

### Exception
```php
$flysystem->getMetadata('none.md');
$exception = $flysystem->getAdapter()->getException();
```

## Reference
[https://github.com/thephpleague/flysystem](https://github.com/thephpleague/flysystem)  
[https://github.com/aliyun/aliyun-oss-php-sdk-flysystem](https://github.com/aliyun/aliyun-oss-php-sdk-flysystem)  

## License
The MIT License (MIT). Please see [License File](LICENSE) for more information.

[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Falphasnow%2Faliyun-oss-flysystem.svg?type=large)](https://app.fossa.com/projects/git%2Bgithub.com%2Falphasnow%2Faliyun-oss-flysystem?ref=badge_large)
