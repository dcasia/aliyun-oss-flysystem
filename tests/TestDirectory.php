<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use League\Flysystem\Config;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use OSS\Core\OssException;

trait TestDirectory
{
    public function test_create_directory_works(): void
    {
        $this->client->expects('createObjectDir')
                     ->andReturn($this->fakeOssPutSetDeleteSuccessfulResponse());

        $this->adapter->createDirectory('object', new Config());
    }

    public function test_create_directory_with_acl_works(): void
    {
        $this->client->expects('createObjectDir')
                     ->andReturn($this->fakeOssPutSetDeleteSuccessfulResponse());

        $this->adapter->createDirectory('object', new Config([
            Config::OPTION_VISIBILITY => $this->randomVisibility(),
        ]));
    }

    public function test_create_directory_can_handle_exception(): void
    {
        $this->client->expects('createObjectDir')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToCreateDirectory::class);
        $this->adapter->createDirectory('object', new Config());
    }

    public function test_delete_directory_works(): void
    {
        $this->client->expects('listObjects')
                     ->twice()
                     ->andReturn(
                         $this->fakeObjectListInfo(
                             objectList: [
                                 $this->fakeObjectInfo(key: 'object/'),
                                 $this->fakeObjectInfo(key: 'object/file.ext', size: '1'),
                             ],
                             prefixList: [
                                 $this->fakePrefixInfo(prefix: 'object/foo'),
                             ],
                         ),
                         $this->fakeObjectListInfo(
                             objectList: [
                                 $this->fakeObjectInfo(key: 'object/foo/'),
                                 $this->fakeObjectInfo(key: 'object/foo/bar.txt', size: '1'),
                             ]
                         )
                     );

        $this->client->expects('deleteObject')
                     ->times(4)
                     ->andReturn($this->fakeOssPutSetDeleteSuccessfulResponse());

        $this->adapter->deleteDirectory('object');
    }

    public function test_delete_directory_can_handle_exception_when_deleting_object(): void
    {
        $this->client->expects('listObjects')
                     ->andReturn($this->fakeObjectListInfo(
                         objectList: [
                             $this->fakeObjectInfo(key: 'object/'),
                             $this->fakeObjectInfo(key: 'object/file.ext', size: '1'),
                         ],
                     ));

        $this->client->expects('deleteObject')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToDeleteDirectory::class);
        $this->adapter->deleteDirectory('object');
    }

    public function test_delete_directory_can_handle_exception_when_listing_contents(): void
    {
        $this->client->expects('listObjects')
                     ->andThrow(OssException::class);

        $this->expectException(UnableToDeleteDirectory::class);
        $this->adapter->deleteDirectory('object');
    }
}
