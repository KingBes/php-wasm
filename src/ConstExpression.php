<?php

namespace Kingbes\Wasm;

use Kingbes\Wasm\Base;
use \FFI\CData;

/**
 * 表达式
 * @example ```php
 * use Kingbes\Wasm\ConstExpression;
 * ```
 */
class ConstExpression extends Base
{
    /**
     * 数据指针
     *
     * @var CData
     */
    public CData $data;

    /**
     * 构造函数
     *
     * @param integer|float|ValType|RefType $val 值
     * @example ```php
     * $expr = new ConstExpression(100);
     * ```
     */
    public function __construct(int|float|ValType|RefType $val)
    {
        if (is_int($val)) {
            $this->data = self::ffi()->constexpr_value_i32($val);
        } elseif (is_float($val)) {
            $this->data = self::ffi()->constexpr_value_f32($val);
        } elseif ($val instanceof ValType) {
            $this->data = self::ffi()->constexpr_value_zero($val->data());
        } elseif ($val instanceof RefType) {
            $this->data = self::ffi()->constexpr_ref_null($val->data());
        }
    }
}
