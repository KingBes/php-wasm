<?php

namespace Kingbes\Wasm;

use Kingbes\Wasm\Base;

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
     * 获取数据
     * @example ```php
     * $ref_type = RefType::FuncRef;
     * $ref_type->data();
     * ```
     * @return integer
     */
    public function data(): int
    {
        $cdate = match ($this) {
            self::FuncRef => "funcref",
            self::ExternRef => "externref",
        };
        return Base::ffi()->help_ref_type($cdate);
    }
}
