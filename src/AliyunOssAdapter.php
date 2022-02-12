<?php

namespace AlphaSnow\Flysystem\AliyunOss;

use DateTimeInterface;
use Generator;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use OSS\Core\OssException;
use OSS\Model\ObjectInfo;
use OSS\OssClient;
use RuntimeException;

class AliyunOssAdapter implements FilesystemAdapter
{
    private OssClient $client;

    private string $bucket;

    private PathPrefixer $prefixer;

    private VisibilityConverter $visibility;

    public function __construct(
        OssClient $client,
        string $bucket,
        string $prefix = '',
        VisibilityConverter $visibility = null
    ) {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->prefixer = new PathPrefixer($prefix);
        $this->visibility = $visibility ?: new PortableVisibilityConverter();
    }

    public function getClient(): OssClient
    {
        return $this->client;
    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $this->prefixer->prefixPath($path));
        } catch (OssException $exception) {
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $this->prefixer->prefixDirectoryPath($path));
        } catch (OssException $exception) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $exception);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->client->putObject(
                $this->bucket, $this->prefixer->prefixPath($path), $contents, $this->configToOptions($config)
            );
        } catch (OssException $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $this->client->uploadStream(
                $this->bucket, $this->prefixer->prefixPath($path), $contents, $this->configToOptions($config)
            );
        } catch (OssException $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function read(string $path): string
    {
        try {
            return $this->client->getObject($this->bucket, $this->prefixer->prefixPath($path));
        } catch (OssException $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function readStream(string $path)
    {
        if (($stream = tmpfile()) === false) {
            throw UnableToReadFile::fromLocation($path); // @codeCoverageIgnore
        }

        try {
            $this->client->getObject($this->bucket, $this->prefixer->prefixPath($path), [
                OssClient::OSS_FILE_DOWNLOAD => $stream,
            ]);
        } catch (OssException $exception) {
            fclose($stream);

            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }

        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $this->client->deleteObject($this->bucket, $this->prefixer->prefixPath($path));
        } catch (OssException $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $directories = [];

            /** @var StorageAttributes $content */
            foreach ($this->listContents($path, deep: true) as $content) {
                if ($content->isFile()) {
                    $this->delete($content->path());
                    continue;
                }

                $directories[] = $content->path();
            }

            rsort($directories);

            foreach ($directories as $directory) {
                $this->delete($directory);
            }

            /**
             * Don't delete `prefix` directory if `path` is empty string.
             */
            if ($path) {
                $this->delete($path . '/');
            }
        } catch (OssException|UnableToDeleteFile|RuntimeException $exception) {
            throw UnableToDeleteDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->client->createObjectDir(
                $this->bucket, $this->prefixer->prefixPath($path), $this->configToOptions($config)
            );
        } catch (OssException $exception) {
            throw UnableToCreateDirectory::atLocation($path, $exception->getMessage());
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        try {
            $this->client->putObjectAcl(
                $this->bucket, $this->prefixer->prefixPath($path), $this->visibility->visibilityToAcl($visibility)
            );
        } catch (OssException $exception) {
            throw UnableToSetVisibility::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        try {
            $acl = $this->client->getObjectAcl(
                $this->bucket, $this->prefixer->prefixPath($path)
            );

            if ($this->shouldUseBucketAcl($acl)) {
                $acl = $this->client->getBucketAcl($this->bucket);
            }

            $visibility = $this->visibility->aclToVisibility($acl);
        } catch (OssException $exception) {
            throw UnableToRetrieveMetadata::visibility($path, $exception->getMessage(), $exception);
        }

        return new FileAttributes(
            path: $path,
            visibility: $visibility
        );
    }

    public function mimeType(string $path): FileAttributes
    {
        return new FileAttributes(
            path: $path,
            mimeType: $this->meta($path, 'content-type')
        );
    }

    public function lastModified(string $path): FileAttributes
    {
        return new FileAttributes(
            path: $path,
            lastModified: $this->meta($path, 'last-modified', 'strtotime')
        );
    }

    public function fileSize(string $path): FileAttributes
    {
        return new FileAttributes(
            path: $path,
            fileSize: $this->meta($path, 'content-length')
        );
    }

    public function listContents(string $path, bool $deep): iterable
    {
        foreach ($this->iterateDirectory($path, $deep) as $object) {
            $storage = $this->normalizeObjectInfo($object);

            if ($storage->isDir() && $path === trim($storage->path(), '/')) {
                continue;
            }

            yield $storage;
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (OssException|UnableToCopyFile|UnableToDeleteFile $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->client->copyObject(
                $this->bucket, $this->prefixer->prefixPath($source),
                $this->bucket, $this->prefixer->prefixPath($destination),
                $this->configToOptions($config)
            );
        } catch (OssException $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function getTemporaryUrl(string $path, DateTimeInterface|int $expiration, array $options = []): string
    {
        try {
            $method = $options[ OssClient::OSS_METHOD ] ?? OssClient::OSS_HTTP_GET;

            if ($expiration instanceof DateTimeInterface) {
                $expiration = $expiration->getTimestamp() - time();
            }

            return $this->client->signUrl(
                $this->bucket, $this->prefixer->prefixPath($path), $expiration, $method, $options
            );
        } catch (OssException $exception) {
            throw new RuntimeException(
                "Unable to generate temporary url for $path. {$exception->getMessage()}"
            );
        }
    }

    private function configToOptions(Config $config): array
    {
        $options = [];

        if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
            $options[ OssClient::OSS_HEADERS ][ OssClient::OSS_OBJECT_ACL ] = $this->visibility->visibilityToAcl($visibility);
        }

        return $options;
    }

    private function meta(string $path, string $key, callable $formatter = null): mixed
    {
        try {
            $meta = $this->client->getObjectMeta($this->bucket, $this->prefixer->prefixPath($path));
        } catch (OssException $exception) {
            throw UnableToRetrieveMetadata::mimeType($path, $exception->getMessage(), $exception);
        }

        $value = $meta[ $key ] ?? null;

        if ($value && $formatter) {
            return $formatter($value);
        }

        return $value;
    }

    /**
     * @param string $path
     * @param bool $deep
     *
     * @return Generator<ObjectInfo>
     */
    private function iterateDirectory(string $path, bool $deep = false): Generator
    {
        $marker = '';
        $subDirectories = [];

        do {
            try {
                $list = $this->client->listObjects($this->bucket, [
                    OssClient::OSS_PREFIX => $this->prefixer->prefixDirectoryPath($path),
                    OssClient::OSS_MARKER => $marker,
                ]);
            } catch (OssException $exception) {
                throw new RuntimeException("Unable to list contents for $path");
            }

            foreach ($list->getObjectList() as $objectInfo) {
                yield $objectInfo;
            }

            foreach ($list->getPrefixList() as $prefixInfo) {
                $subDirectories[] = $this->prefixer->stripPrefix($prefixInfo->getPrefix());
            }

            $marker = $list->getNextMarker();
        } while ($marker);

        if ($deep) {
            foreach ($subDirectories as $directory) {
                yield from $this->iterateDirectory($directory, $deep);
            }
        }
    }

    private function normalizeObjectInfo(ObjectInfo $objectInfo): StorageAttributes
    {
        $path = $this->prefixer->stripPrefix($objectInfo->getKey());
        $size = $objectInfo->getSize();
        $lastModified = strtotime($objectInfo->getLastModified()) ?: null;

        return match ($size) {
            0 => new DirectoryAttributes(path: $path, lastModified: $lastModified),
            default => new FileAttributes(path: $path, fileSize: $size, lastModified: $lastModified)
        };
    }

    private function shouldUseBucketAcl(string $acl): bool
    {
        return $acl === 'default';
    }
}
