<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use League\Flysystem\UnableToDeleteFile;
use OSS\Core\OssException;

trait TestDelete
{
    public function test_delete_works(): void
    {
        $this->client->expects('deleteObject')
                     ->andReturn($this->fakeOssPutSetDeleteSuccessfulResponse());

        $this->adapter->delete('object');
    }

    public function test_delete_can_handle_exception(): void
    {
        $this->client->expects('deleteObject')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToDeleteFile::class);
        $this->adapter->delete('object');
    }
}
