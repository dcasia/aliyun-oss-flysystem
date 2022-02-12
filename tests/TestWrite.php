<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use League\Flysystem\Config;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use OSS\Core\OssException;

trait TestWrite
{
    public function test_write_works(): void
    {
        $this->client->expects('putObject')
                     ->andReturn($this->fakeOssPutSetDeleteSuccessfulResponse());

        $this->adapter->write('object', 'content', new Config());
    }

    public function test_write_with_acl_option_works(): void
    {
        $this->client->expects('putObject')
                     ->andReturn($this->fakeOssPutSetDeleteSuccessfulResponse());

        $this->adapter->write('object', 'content', new Config([
            Config::OPTION_VISIBILITY => Visibility::PUBLIC,
        ]));
    }

    public function test_write_can_handle_exception(): void
    {
        $this->client->expects('putObject')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToWriteFile::class);
        $this->adapter->write('object', 'content', new Config());
    }

    public function test_write_stream_works(): void
    {
        $this->client->expects('uploadStream')
                     ->andReturn($this->fakeOssPutSetDeleteSuccessfulResponse());

        $this->adapter->writeStream('object', tmpfile(), new Config());
    }

    public function test_write_stream_with_acl_option_works(): void
    {
        $this->client->expects('uploadStream')
                     ->andReturn($this->fakeOssPutSetDeleteSuccessfulResponse());

        $this->adapter->writeStream('object', tmpfile(), new Config([
            Config::OPTION_VISIBILITY => Visibility::PUBLIC,
        ]));
    }

    public function test_write_stream_can_handle_exception(): void
    {
        $this->client->expects('uploadStream')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToWriteFile::class);
        $this->adapter->writeStream('object', tmpfile(), new Config());
    }
}
