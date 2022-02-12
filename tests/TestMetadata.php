<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use League\Flysystem\UnableToRetrieveMetadata;
use OSS\Core\OssException;

trait TestMetadata
{
    public function test_mime_type_works(): void
    {
        $this->client->expects('getObjectMeta')
                     ->andReturn($this->fakeObjectMetaSuccessfulResponse());

        $this->assertIsString($this->adapter->mimeType('object')->mimeType());
    }

    public function test_last_modified_works(): void
    {
        $this->client->expects('getObjectMeta')
                     ->andReturn($this->fakeObjectMetaSuccessfulResponse());

        $this->assertIsInt($this->adapter->lastModified('object')->lastModified());
    }

    public function test_file_size_works(): void
    {
        $this->client->expects('getObjectMeta')
                     ->andReturn($this->fakeObjectMetaSuccessfulResponse());

        $this->assertIsInt($this->adapter->fileSize('object')->fileSize());
    }

    public function test_mime_type_can_handle_exception(): void
    {
        $this->client->expects('getObjectMeta')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToRetrieveMetadata::class);
        $this->adapter->mimeType('object');
    }

    public function test_last_modified_can_handle_exception(): void
    {
        $this->client->expects('getObjectMeta')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToRetrieveMetadata::class);
        $this->adapter->lastModified('object');
    }

    public function test_file_size_can_handle_exception(): void
    {
        $this->client->expects('getObjectMeta')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToRetrieveMetadata::class);
        $this->adapter->fileSize('object');
    }

    private function fakeObjectMetaSuccessfulResponse(): array
    {
        return [
            'content-type' => 'image/png',
            'content-length' => 1024,
            'last-modified' => 'Mon, 14 Feb 2022 12:00:00 GMT',
        ];
    }
}
