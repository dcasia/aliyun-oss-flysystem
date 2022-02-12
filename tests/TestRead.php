<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use League\Flysystem\UnableToReadFile;
use OSS\Core\OssException;

trait TestRead
{
    public function test_read_works(): void
    {
        $this->client->expects('getObject')
                     ->andReturn($this->fakeReadSuccessfulResponse());

        $this->assertIsString($this->adapter->read('object'));
    }

    public function test_read_can_handle_exception(): void
    {
        $this->client->expects('getObject')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToReadFile::class);
        $this->adapter->read('object');
    }

    public function test_read_stream_works(): void
    {
        $this->client->expects('getObject')
                     ->andReturn($this->fakeReadSuccessfulResponse());

        $this->assertIsResource($this->adapter->readStream('object'));
    }

    public function test_read_stream_can_handle_exception(): void
    {
        $this->client->expects('getObject')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToReadFile::class);
        $this->adapter->readStream('object');
    }

    private function fakeReadSuccessfulResponse(): string
    {
        return 'contents';
    }
}
