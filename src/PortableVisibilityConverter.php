<?php

namespace AlphaSnow\Flysystem\AliyunOss;

use League\Flysystem\InvalidVisibilityProvided;
use League\Flysystem\Visibility;
use OSS\OssClient;
use RuntimeException;

class PortableVisibilityConverter implements VisibilityConverter
{
    public function visibilityToAcl(string $visibility): string
    {
        return match ($visibility) {
            Visibility::PUBLIC => OssClient::OSS_ACL_TYPE_PUBLIC_READ,
            Visibility::PRIVATE => OssClient::OSS_ACL_TYPE_PRIVATE,
            default => throw InvalidVisibilityProvided::withVisibility(
                $visibility, sprintf('either %1$s::PUBLIC or %1$s::PRIVATE', Visibility::class),
            )
        };
    }

    public function aclToVisibility(string $acl): string
    {
        if ($acl === OssClient::OSS_ACL_TYPE_PUBLIC_READ_WRITE ||
            $acl === OssClient::OSS_ACL_TYPE_PUBLIC_READ) {
            return Visibility::PUBLIC;
        }

        if ($acl === OssClient::OSS_ACL_TYPE_PRIVATE) {
            return Visibility::PRIVATE;
        }

        throw new RuntimeException("Unsupported oss acl '$acl'");
    }
}
