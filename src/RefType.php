<?php

namespace Kingbes\Wasm;

/**
 * 引用类型枚举
 * @example ```php
 * use Kingbes\Wasm\RefType;
 * ```
 */
enum RefType
{
    case FuncRef;
    case ExternRef;

    /**
     * 获取该类型在 Wasm 二进制中的规范字节
     * @example ```php
     * $ref_type = RefType::FuncRef;
     * $ref_type->data();
     * ```
     * @return integer
     */
    public function data(): int
    {
        return match ($this) {
            self::FuncRef => 0x70,
            self::ExternRef => 0x6F,
        };
    }
}