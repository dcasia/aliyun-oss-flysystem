<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use OSS\Core\OssException;

trait TestExistence
{
    public function test_file_exists_works(): void
    {
        $this->client->expects('doesObjectExist')
                     ->twice()
                     ->andReturn(true, false);

        $this->assertTrue($this->adapter->fileExists('exists'));
        $this->assertFalse($this->adapter->fileExists('not-found'));
    }

    public function test_file_exists_can_handle_exception(): void
    {
        $this->client->expects('doesObjectExist')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToCheckFileExistence::class);
        $this->adapter->fileExists('object');
    }

    public function test_directory_exists_works(): void
    {
        $this->client->expects('doesObjectExist')
                     ->twice()
                     ->andReturn(true, false);

        $this->assertTrue($this->adapter->directoryExists('exists'));
        $this->assertFalse($this->adapter->directoryExists('not-found'));
    }

    public function test_directory_exists_can_handle_exception(): void
    {
        $this->client->expects('doesObjectExist')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToCheckDirectoryExistence::class);
        $this->adapter->directoryExists('object');
    }
}
