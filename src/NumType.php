<?php

namespace Kingbes\Wasm;

/**
 * 数字类型枚举
 * @example ```php
 * use Kingbes\Wasm\NumType;
 * ```
 */
enum NumType
{
    case I32;
    case I64;
    case F32;
    case F64;

    /**
     * 获取该类型在 Wasm 二进制中的规范字节
     * @example ```php
     * $num_type = NumType::I32;
     * $num_type->data();
     * ```
     * @return integer
     */
    public function data(): int
    {
        return match ($this) {
            self::I32 => 0x7F,
            self::I64 => 0x7E,
            self::F32 => 0x7D,
            self::F64 => 0x7C,
        };
    }
}