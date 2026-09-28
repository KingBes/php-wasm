<?php

namespace Kingbes\Wasm;

use Kingbes\Wasm\Base;
use Kingbes\Wasm\Wasm\ConstExprState;

/**
 * 表达式
 * @example ```php
 * use Kingbes\Wasm\ConstExpression;
 * ```
 */
class ConstExpression extends Base
{
    /**
     * 常量表达式状态
     *
     * @var ConstExprState
     */
    public ConstExprState $data;

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
            $this->data = ConstExprState::valueI32($val);
        } elseif (is_float($val)) {
            $this->data = ConstExprState::valueF32($val);
        } elseif ($val instanceof ValType) {
            $this->data = ConstExprState::zero($val->data());
        } else {
            $this->data = ConstExprState::refNullExpr($val->data());
        }
    }

    /**
     * 由内部状态构造表达式对象
     */
    private static function fromState(ConstExprState $state): self
    {
        $expr = new self(0);
        $expr->data = $state;
        return $expr;
    }

    /**
     * i64 常量表达式
     *
     * @param integer $val 值
     * @example ```php
     * $expr = ConstExpression::i64(100);
     * ```
     * @return self
     */
    public static function i64(int $val): self
    {
        return self::fromState(ConstExprState::valueI64($val));
    }

    /**
     * f64 常量表达式
     *
     * @param float $val 值
     * @example ```php
     * $expr = ConstExpression::f64(1.5);
     * ```
     * @return self
     */
    public static function f64(float $val): self
    {
        return self::fromState(ConstExprState::valueF64($val));
    }

    /**
     * 引用导入的全局变量
     *
     * @param integer $index 导入全局变量索引
     * @example ```php
     * $expr = ConstExpression::globalGet(0);
     * ```
     * @return self
     */
    public static function globalGet(int $index): self
    {
        $state = new ConstExprState();
        $state->globalGet($index);
        return self::fromState($state);
    }

    /**
     * 引用函数
     *
     * @param string $name 函数名称
     * @example ```php
     * $expr = ConstExpression::refFunc("add");
     * ```
     * @return self
     */
    public static function refFunc(string $name): self
    {
        $state = new ConstExprState();
        $state->refFunc($name);
        return self::fromState($state);
    }

    /**
     * 引用导入函数
     *
     * @param string $mod_name 模块名称
     * @param string $fn_name 函数名称
     * @example ```php
     * $expr = ConstExpression::refFuncImport("env", "log");
     * ```
     * @return self
     */
    public static function refFuncImport(string $mod_name, string $fn_name): self
    {
        $state = new ConstExprState();
        $state->refFuncImport($mod_name, $fn_name);
        return self::fromState($state);
    }

    /**
     * 追加 i32 常量
     *
     * @param integer $val 值
     * @example ```php
     * $expr = (new ConstExpression(2))->i32Const(3)->add(NumType::I32); // 2 + 3
     * ```
     * @return self
     */
    public function i32Const(int $val): self
    {
        $this->data->i32Const($val);
        return $this;
    }

    /**
     * 追加 i64 常量
     *
     * @param integer $val 值
     * @return self
     */
    public function i64Const(int $val): self
    {
        $this->data->i64Const($val);
        return $this;
    }

    /**
     * 追加 f32 常量
     *
     * @param float $val 值
     * @return self
     */
    public function f32Const(float $val): self
    {
        $this->data->f32Const($val);
        return $this;
    }

    /**
     * 追加 f64 常量
     *
     * @param float $val 值
     * @return self
     */
    public function f64Const(float $val): self
    {
        $this->data->f64Const($val);
        return $this;
    }

    /**
     * 加法
     *
     * @param NumType $typ 数值类型，仅支持 i32/i64
     * @example ```php
     * $expr = ConstExpression::i64(1)->add(NumType::I64);
     * ```
     * @return self
     */
    public function add(NumType $typ): self
    {
        $this->data->add($typ->data());
        return $this;
    }

    /**
     * 减法
     *
     * @param NumType $typ 数值类型，仅支持 i32/i64
     * @return self
     */
    public function sub(NumType $typ): self
    {
        $this->data->sub($typ->data());
        return $this;
    }

    /**
     * 乘法
     *
     * @param NumType $typ 数值类型，仅支持 i32/i64
     * @return self
     */
    public function mul(NumType $typ): self
    {
        $this->data->mul($typ->data());
        return $this;
    }
}