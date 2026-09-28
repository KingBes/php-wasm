<?php

namespace Kingbes\Wasm;

/**
 * 值类型枚举
 * @example ```php
 * use Kingbes\Wasm\ValType;
 * ```
 */
enum ValType
{
    case I32;
    case I64;
    case F32;
    case F64;
    case V128;
    case FuncRef;
    case ExternRef;

    /**
     * 获取该类型在 Wasm 二进制中的规范字节
     * @example ```php
     * $val_type = ValType::I32;
     * $val_type->data();
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
            self::V128 => 0x7B,
            self::FuncRef => 0x70,
            self::ExternRef => 0x6F,
        };
    }
}