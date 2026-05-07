<?php

namespace Kingbes\Wasm;

use Kingbes\Wasm\Base;

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
     * 获取数据
     * @example ```php
     * $val_type = ValType::I32;
     * $val_type->data();
     * ```
     * @return integer
     */
    public function data(): int
    {
        $cdate = match ($this) {
            self::I32 => "i32",
            self::I64 => "i64",
            self::F32 => "f32",
            self::F64 => "f64",
            self::V128 => "v128",
            self::FuncRef => "funcref",
            self::ExternRef => "externref",
        };
        return Base::ffi()->help_val_type($cdate);
    }
}
