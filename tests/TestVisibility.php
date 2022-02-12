<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use League\Flysystem\InvalidVisibilityProvided;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;
use OSS\Core\OssException;
use OSS\OssClient;
use RuntimeException;

trait TestVisibility
{
    public function test_set_visibility_works(): void
    {
        $this->client->expects('putObjectAcl')
                     ->twice()
                     ->andReturn(
                         $this->fakeOssPutSetDeleteSuccessfulResponse(),
                         $this->fakeOssPutSetDeleteSuccessfulResponse(),
                     );

        $this->adapter->setVisibility('object', Visibility::PUBLIC);
        $this->adapter->setVisibility('object', Visibility::PRIVATE);
    }

    public function test_set_visibility_can_handle_exception(): void
    {
        $this->client->expects('putObjectAcl')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToSetVisibility::class);
        $this->adapter->setVisibility('object', $this->randomVisibility());
    }

    public function test_passing_invalid_visibility_should_throw_exception(): void
    {
        $this->expectException(InvalidVisibilityProvided::class);
        $this->adapter->setVisibility('object', 'invalid-visibility');
    }

    public function test_get_visibility_works(): void
    {
        $this->client->expects('getObjectAcl')
                     ->times(3)
                     ->andReturn(...$this->ossAcl());

        $this->assertIsString($this->adapter->visibility('object')->visibility());
        $this->assertIsString($this->adapter->visibility('object')->visibility());
        $this->assertIsString($this->adapter->visibility('object')->visibility());
    }

    public function test_get_visibility_will_return_bucket_visibility_if_visibility_of_object_is_default_value(): void
    {
        $this->client->expects('getObjectAcl')
                     ->times(3)
                     ->andReturn('default');

        $this->client->expects('getBucketAcl')
                     ->times(3)
                     ->andReturn(...$this->ossAcl());

        $this->assertIsString($this->adapter->visibility('object')->visibility());
        $this->assertIsString($this->adapter->visibility('object')->visibility());
        $this->assertIsString($this->adapter->visibility('object')->visibility());
    }

    public function test_unsupported_acl_returned_from_oss_will_throw_exception(): void
    {
        $this->client->expects('getObjectAcl')
                     ->andReturn('unknown-acl');

        $this->expectException(RuntimeException::class);
        $this->adapter->visibility('object');
    }

    public function test_get_visibility_can_handle_exception(): void
    {
        $this->client->expects('getObjectAcl')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToRetrieveMetadata::class);
        $this->adapter->visibility('object');
    }

    private function ossAcl(): array
    {
        return [
            OssClient::OSS_ACL_TYPE_PRIVATE,
            OssClient::OSS_ACL_TYPE_PUBLIC_READ,
            OssClient::OSS_ACL_TYPE_PUBLIC_READ_WRITE,
        ];
    }
}
