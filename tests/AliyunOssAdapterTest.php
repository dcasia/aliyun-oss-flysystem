<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use DateTime;
use Faker\Factory;
use Faker\Generator;
use AlphaSnow\Flysystem\AliyunOss\AliyunOssAdapter;
use League\Flysystem\Visibility;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use OSS\Core\OssException;
use OSS\Model\ObjectInfo;
use OSS\Model\ObjectListInfo;
use OSS\Model\PrefixInfo;
use OSS\OssClient;
use RuntimeException;

class AliyunOssAdapterTest extends MockeryTestCase
{
    use TestExistence;
    use TestWrite;
    use TestRead;
    use TestDelete;
    use TestDirectory;
    use TestVisibility;
    use TestMetadata;
    use TestListContents;
    use TestCopyAndMove;

    protected Generator $faker;

    protected MockInterface $client;

    protected AliyunOssAdapter $adapter;

    protected function mockeryTestSetUp(): void
    {
        $this->faker = Factory::create();
        $this->client = Mockery::mock(OssClient::class);
        $this->adapter = new AliyunOssAdapter($this->client, 'bucket');
    }

    public function test_get_client_works(): void
    {
        $this->assertInstanceOf(
            OssClient::class, $this->adapter->getClient()
        );
    }

    public function test_get_temporary_url_works(): void
    {
        $this->client->expects('signUrl')
            ->andReturn('url');

        $this->assertEquals('url', $this->adapter->getTemporaryUrl('object', 60));
    }

    public function test_get_temporary_url_can_handle_exception(): void
    {
        $this->client->expects('signUrl')
            ->andReturn('url');

        $this->assertEquals(
            'url', $this->adapter->getTemporaryUrl('object', new DateTime('+1 day'))
        );
    }

    public function test_get_temporary_url_accept_datetime_as_expiration(): void
    {
        $this->client->expects('signUrl')
            ->andThrow(OssException::class);

        $this->expectException(RuntimeException::class);
        $this->adapter->getTemporaryUrl('object', 60);
    }

    protected function generatorCallback(iterable $iterable, callable $callback = null): void
    {
        foreach ($iterable as $item) {
            ($callback ?? fn ($item) => $item)($item);
        }
    }

    protected function randomVisibility(): string
    {
        return $this->faker->randomElement([Visibility::PUBLIC, Visibility::PRIVATE]);
    }

    protected function fakeOssPutSetDeleteSuccessfulResponse(): array
    {
        return [
            'body' => '',
        ];
    }

    protected function fakeObjectListInfo(
        string $bucketName = '',
        string $prefix = '',
        string $marker = '',
        string $nextMarker = '',
        int    $maxKeys = 100,
        string $delimiter = '',
        string $isTruncated = '',
        array  $objectList = [],
        array  $prefixList = [],
    ): ObjectListInfo {
        return new ObjectListInfo(
            $bucketName, $prefix, $marker, $nextMarker, $maxKeys, $delimiter, $isTruncated, $objectList, $prefixList
        );
    }

    protected function fakeObjectInfo(
        string $key,
        string $lastModified = '',
        string $eTag = '',
        string $type = '',
        string $size = '0',
        string $storageClass = 'Standard',
    ): ObjectInfo {
        return new ObjectInfo(
            $key, $lastModified, $eTag, $type, $size, $storageClass
        );
    }

    protected function fakePrefixInfo(?string $prefix): PrefixInfo
    {
        return new PrefixInfo(
            $prefix ?? $this->faker->word
        );
    }
}
