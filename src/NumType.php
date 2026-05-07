<?php

namespace Kingbes\Wasm;

use Kingbes\Wasm\Base;

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
     * 获取数据
     * @example ```php
     * $num_type = NumType::I32;
     * $num_type->data();
     * ```
     * @return integer
     */
    public function data(): int
    {
        $cdata = match ($this) {
            self::I32 => "i32",
            self::I64 => "i64",
            self::F32 => "f32",
            self::F64 => "f64"
        };
        return Base::ffi()->help_num_type($cdata);
    }
}
