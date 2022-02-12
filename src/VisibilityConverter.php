<?php

namespace AlphaSnow\Flysystem\AliyunOss;

interface VisibilityConverter
{
    public const OSS_ACL_TYPE_DEFAULT = 'default';

    public function visibilityToAcl(string $visibility): string;

    public function aclToVisibility(string $acl): string;
}
