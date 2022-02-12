<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use League\Flysystem\Config;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToMoveFile;
use OSS\Core\OssException;

trait TestCopyAndMove
{
    public function test_copy_works(): void
    {
        $this->client->expects('copyObject')
                     ->andReturn($this->fakeCopySuccessfulResponse());

        $this->adapter->copy('source', 'destination', new Config());
    }

    public function test_copy_can_handle_exception(): void
    {
        $this->client->expects('copyObject')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToCopyFile::class);
        $this->adapter->copy('source', 'destination', new Config());
    }

    public function test_move_works(): void
    {
        $this->client->expects('copyObject')
                     ->andReturn($this->fakeCopySuccessfulResponse());

        $this->client->expects('deleteObject')
                     ->andReturn($this->fakeOssPutSetDeleteSuccessfulResponse());

        $this->adapter->move('source', 'destination', new Config());
    }

    public function test_move_can_handle_exception_when_copying(): void
    {
        $this->client->expects('copyObject')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToMoveFile::class);
        $this->adapter->move('source', 'destination', new Config());
    }

    public function test_move_can_handle_exception_when_deleting_source_object(): void
    {
        $this->client->expects('copyObject')
                     ->andReturn($this->fakeCopySuccessfulResponse());

        $this->client->expects('deleteObject')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToMoveFile::class);
        $this->adapter->move('source', 'destination', new Config());
    }

    private function fakeCopySuccessfulResponse(): array
    {
        return [
            'body' => 'etag-and-last-modified-in-xml',
        ];
    }
}
