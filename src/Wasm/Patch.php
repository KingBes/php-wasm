<?php

declare(strict_types=1);

namespace Kingbes\Wasm\Wasm;

/**
 * 待解析的索引补丁项。
 */
final class Patch
{
    public const FUNCTION = 'function';
    public const IMPORT = 'import';
    public const GLOBAL = 'global';

    public function __construct(
        public string $kind,
        public int $pos,
        public string $name = '',
        public string $mod = '',
        public int $idx = 0,
    ) {
    }

    public static function function(string $name, int $pos): self
    {
        return new self(self::FUNCTION, $pos, $name);
    }

    public static function import(string $mod, string $name, int $pos): self
    {
        return new self(self::IMPORT, $pos, $name, $mod);
    }

    public static function global(int $idx, int $pos): self
    {
        return new self(self::GLOBAL, $pos, '', '', $idx);
    }
}