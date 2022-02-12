<?php

namespace AlphaSnow\Flysystem\AliyunOss\Tests;

use League\Flysystem\StorageAttributes;
use OSS\Core\OssException;
use RuntimeException;

trait TestListContents
{
    public function test_list_contents_works(): void
    {
        $this->client->expects('listObjects')
                     ->andReturn($this->fakeObjectListInfo(
                         objectList: [
                             $this->fakeObjectInfo(key: 'object/'),
                             $this->fakeObjectInfo(key: 'object/file.ext', size: '1'),
                         ],
                     ));

        $this->generatorCallback($this->adapter->listContents('object', false), function ($content) {
            $this->assertInstanceOf(StorageAttributes::class, $content);
        });
    }

    public function test_list_contents_recursively_works(): void
    {
        $this->client->expects('listObjects')
                     ->twice()
                     ->andReturn(
                         $this->fakeObjectListInfo(
                             objectList: [
                                 $this->fakeObjectInfo(key: 'object/'),
                                 $this->fakeObjectInfo(key: 'object/file.txt', size: '1'),
                             ],
                             prefixList: [
                                 $this->fakePrefixInfo(prefix: 'object/foo'),
                             ],
                         ),
                         $this->fakeObjectListInfo(
                             objectList: [
                                 $this->fakeObjectInfo(key: 'object/foo'),
                                 $this->fakeObjectInfo(key: 'object/foo/bar.txt', size: '1'),
                             ]
                         )
                     );

        $paths = [];

        foreach ($this->adapter->listContents('object', true) as $content) {
            $paths[] = $content->path();
        }

        $this->assertEquals($paths, [
            'object/file.txt',
            'object/foo',
            'object/foo/bar.txt',
        ]);
    }

    public function test_list_contents_can_handle_exception(): void
    {
        $this->client->expects('listObjects')
                     ->andThrow(OssException::class);

        $this->expectException(RuntimeException::class);
        $this->generatorCallback($this->adapter->listContents('object', $this->faker->boolean));
    }
}
