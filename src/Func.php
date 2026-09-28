<?php

namespace Kingbes\Wasm;

use Kingbes\Wasm\Wasm\Encoder;
use Kingbes\Wasm\Wasm\FunctionState;
use Kingbes\Wasm\Wasm\ModuleState;

class Func extends Base
{
    /**
     * 函数状态
     *
     * @var FunctionState
     */
    public FunctionState $fn;

    /**
     * 构造函数
     *
     * @param ModuleState $mod 模块状态
     * @param array<string, array<ValType|string>|string> $config 函数配置
     * @param boolean $debug 是否开启调试模式
     * @example ```php
     * $config = [
     *     "name" => "add", // 函数名 必填
     *     "params" => [ValType::I32, ValType::I32], // 请求参数类型 必填
     *     "results" => [ValType::I32], // 返回参数类型 必填
     *     "param_names" => ["a", "b"], // debug 模式下必填，参数名数组，与params数组顺序一致
     *     "type_name" => "add_type", // debug 模式下必填，类型名称
     * ];
     * $func = new Func($mod, $config, true);
     * ```
     */
    public function __construct(ModuleState $mod, array $config, bool $debug = false)
    {
        if ($debug) {
            $fun_type = new FunType($config["params"], $config["results"], $config["type_name"]);
            $this->fn = $mod->newDebugFunction($config["name"], $fun_type->data, $config["param_names"]);
        } else {
            $this->fn = $mod->newFunction(
                $config["name"],
                $this->typeBytes($config["params"]),
                $this->typeBytes($config["results"])
            );
        }
    }

    /**
     * 常量
     *
     * @param integer|float $val 常量值
     * @return self
     */
    public function const(int|float $val): self
    {
        if (is_int($val)) {
            Encoder::i32Const($this->fn, $val);
        } else {
            Encoder::f32Const($this->fn, $val);
        }
        return $this;
    }

    /**
     * i64 常量
     *
     * @param integer $val 常量值
     * @example ```php
     * $fn->constI64(100);
     * ```
     * @return self
     */
    public function constI64(int $val): self
    {
        Encoder::i64Const($this->fn, $val);
        return $this;
    }

    /**
     * f64 常量
     *
     * @param float $val 常量值
     * @example ```php
     * $fn->constF64(1.5);
     * ```
     * @return self
     */
    public function constF64(float $val): self
    {
        Encoder::f64Const($this->fn, $val);
        return $this;
    }

    /**
     * 创建局部变量
     *
     * @param ValType $vty 变量值类型
     * @return integer 索引
     */
    public function newLocal(ValType $vty): int
    {
        $idx = count($this->fn->locals);
        $this->fn->locals[] = ['type' => $vty->data(), 'name' => null];
        return $idx;
    }

    /**
     * 获取局部变量
     *
     * @param integer $index 变量索引
     * @return self
     */
    public function getLocal(int $index): self
    {
        Encoder::localGet($this->fn, $index);
        return $this;
    }

    /**
     * 创建带名称的局部变量
     *
     * @param ValType $vty 变量值类型
     * @param string $name 变量名称
     * @return integer 索引
     */
    public function newLocalNamed(ValType $vty, string $name): int
    {
        $idx = count($this->fn->locals);
        $this->fn->locals[] = ['type' => $vty->data(), 'name' => $name];
        return $idx;
    }

    /**
     * 设置局部变量
     *
     * @param integer $index 变量索引
     * @return self
     */
    public function setLocal(int $index): self
    {
        Encoder::localSet($this->fn, $index);
        return $this;
    }

    /**
     * 局部变量 tee 操作（获取并设置）
     *
     * @param integer $index 变量索引
     * @return self
     */
    public function teeLocal(int $index): self
    {
        Encoder::localTee($this->fn, $index);
        return $this;
    }

    // ==================== 全局变量操作 ====================

    /**
     * 获取全局变量
     *
     * @param integer $index 全局变量索引
     * @return self
     */
    public function getGlobal(int $index): self
    {
        Encoder::globalGet($this->fn, $index);
        return $this;
    }

    /**
     * 设置全局变量
     *
     * @param integer $index 全局变量索引
     * @return self
     */
    public function setGlobal(int $index): self
    {
        Encoder::globalSet($this->fn, $index);
        return $this;
    }

    /**
     * 获取导入的全局变量
     *
     * @param integer $index 导入全局变量索引
     * @example ```php
     * $fn->getGlobalImport(0);
     * ```
     * @return self
     */
    public function getGlobalImport(int $index): self
    {
        Encoder::globalGetImport($this->fn, $index);
        return $this;
    }

    /**
     * 设置导入的全局变量
     *
     * @param integer $index 导入全局变量索引
     * @example ```php
     * $fn->setGlobalImport(0);
     * ```
     * @return self
     */
    public function setGlobalImport(int $index): self
    {
        Encoder::globalSetImport($this->fn, $index);
        return $this;
    }

    // ==================== 算术运算 ====================

    /**
     * 加法
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function add(NumType $typ): self
    {
        Encoder::byNum($this->fn, 'add', $typ->data());
        return $this;
    }

    /**
     * 减法
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function sub(NumType $typ): self
    {
        Encoder::byNum($this->fn, 'sub', $typ->data());
        return $this;
    }

    /**
     * 乘法
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function mul(NumType $typ): self
    {
        Encoder::byNum($this->fn, 'mul', $typ->data());
        return $this;
    }

    /**
     * 除法
     *
     * @param NumType $typ 数值类型
     * @param boolean $signed 是否有符号
     * @return self
     */
    public function div(NumType $typ, bool $signed = false): self
    {
        Encoder::bySign($this->fn, 'div', $typ->data(), $signed);
        return $this;
    }

    /**
     * 取余
     *
     * @param NumType $typ 数值类型
     * @param boolean $signed 是否有符号
     * @return self
     */
    public function rem(NumType $typ, bool $signed = false): self
    {
        Encoder::intOnlySign($this->fn, 'rem', $typ->data(), $signed);
        return $this;
    }

    /**
     * 取绝对值
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function abs(NumType $typ): self
    {
        Encoder::floatOnly($this->fn, 'abs', $typ->data());
        return $this;
    }

    /**
     * 取反
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function neg(NumType $typ): self
    {
        Encoder::floatOnly($this->fn, 'neg', $typ->data());
        return $this;
    }

    /**
     * 向上取整
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function ceil(NumType $typ): self
    {
        Encoder::floatOnly($this->fn, 'ceil', $typ->data());
        return $this;
    }

    /**
     * 向下取整
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function floor(NumType $typ): self
    {
        Encoder::floatOnly($this->fn, 'floor', $typ->data());
        return $this;
    }

    /**
     * 截断取整
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function trunc(NumType $typ): self
    {
        Encoder::floatOnly($this->fn, 'trunc', $typ->data());
        return $this;
    }

    /**
     * 就近取整
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function nearest(NumType $typ): self
    {
        Encoder::floatOnly($this->fn, 'nearest', $typ->data());
        return $this;
    }

    /**
     * 平方根
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function sqrt(NumType $typ): self
    {
        Encoder::floatOnly($this->fn, 'sqrt', $typ->data());
        return $this;
    }

    /**
     * 最小值
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function min(NumType $typ): self
    {
        Encoder::floatOnly($this->fn, 'min', $typ->data());
        return $this;
    }

    /**
     * 最大值
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function max(NumType $typ): self
    {
        Encoder::floatOnly($this->fn, 'max', $typ->data());
        return $this;
    }

    /**
     * 复制符号位
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function copysign(NumType $typ): self
    {
        Encoder::floatOnly($this->fn, 'copysign', $typ->data());
        return $this;
    }

    // ==================== 位运算 ====================

    /**
     * 按位与
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function band(NumType $typ): self
    {
        Encoder::intOnly($this->fn, 'band', $typ->data());
        return $this;
    }

    /**
     * 按位或
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function bor(NumType $typ): self
    {
        Encoder::intOnly($this->fn, 'bor', $typ->data());
        return $this;
    }

    /**
     * 按位异或
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function bxor(NumType $typ): self
    {
        Encoder::intOnly($this->fn, 'bxor', $typ->data());
        return $this;
    }

    /**
     * 左移
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function shl(NumType $typ): self
    {
        Encoder::intOnly($this->fn, 'shl', $typ->data());
        return $this;
    }

    /**
     * 右移
     *
     * @param NumType $typ 数值类型
     * @param boolean $signed 是否有符号
     * @return self
     */
    public function shr(NumType $typ, bool $signed = false): self
    {
        Encoder::intOnlySign($this->fn, 'shr', $typ->data(), $signed);
        return $this;
    }

    /**
     * 前导零计数
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function clz(NumType $typ): self
    {
        Encoder::intOnly($this->fn, 'clz', $typ->data());
        return $this;
    }

    /**
     * 后导零计数
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function ctz(NumType $typ): self
    {
        Encoder::intOnly($this->fn, 'ctz', $typ->data());
        return $this;
    }

    /**
     * 置位计数
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function popcnt(NumType $typ): self
    {
        Encoder::intOnly($this->fn, 'popcnt', $typ->data());
        return $this;
    }

    /**
     * 循环左移
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function rotl(NumType $typ): self
    {
        Encoder::intOnly($this->fn, 'rotl', $typ->data());
        return $this;
    }

    /**
     * 循环右移
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function rotr(NumType $typ): self
    {
        Encoder::intOnly($this->fn, 'rotr', $typ->data());
        return $this;
    }

    // ==================== 比较运算 ====================

    /**
     * 等于零
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function eqz(NumType $typ): self
    {
        Encoder::intOnly($this->fn, 'eqz', $typ->data());
        return $this;
    }

    /**
     * 等于
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function eq(NumType $typ): self
    {
        Encoder::byNum($this->fn, 'eq', $typ->data());
        return $this;
    }

    /**
     * 不等于
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function ne(NumType $typ): self
    {
        Encoder::byNum($this->fn, 'ne', $typ->data());
        return $this;
    }

    /**
     * 小于
     *
     * @param NumType $typ 数值类型
     * @param boolean $signed 是否有符号
     * @return self
     */
    public function lt(NumType $typ, bool $signed = false): self
    {
        Encoder::bySign($this->fn, 'lt', $typ->data(), $signed);
        return $this;
    }

    /**
     * 大于
     *
     * @param NumType $typ 数值类型
     * @param boolean $signed 是否有符号
     * @return self
     */
    public function gt(NumType $typ, bool $signed = false): self
    {
        Encoder::bySign($this->fn, 'gt', $typ->data(), $signed);
        return $this;
    }

    /**
     * 小于等于
     *
     * @param NumType $typ 数值类型
     * @param boolean $signed 是否有符号
     * @return self
     */
    public function le(NumType $typ, bool $signed = false): self
    {
        Encoder::bySign($this->fn, 'le', $typ->data(), $signed);
        return $this;
    }

    /**
     * 大于等于
     *
     * @param NumType $typ 数值类型
     * @param boolean $signed 是否有符号
     * @return self
     */
    public function ge(NumType $typ, bool $signed = false): self
    {
        Encoder::bySign($this->fn, 'ge', $typ->data(), $signed);
        return $this;
    }

    // ==================== 类型转换 ====================

    /**
     * 类型转换
     *
     * @param NumType $from 源类型
     * @param boolean $signed 是否有符号
     * @param NumType $to 目标类型
     * @return self
     */
    public function cast(NumType $from, bool $signed, NumType $to): self
    {
        Encoder::cast($this->fn, $from->data(), $signed, $to->data());
        return $this;
    }

    /**
     * 陷阱类型转换
     *
     * @param NumType $from 源类型
     * @param boolean $signed 是否有符号
     * @param NumType $to 目标类型
     * @return self
     */
    public function castTrapping(NumType $from, bool $signed, NumType $to): self
    {
        Encoder::castTrapping($this->fn, $from->data(), $signed, $to->data());
        return $this;
    }

    /**
     * 重新解释类型（位模式不变）
     *
     * @param NumType $typ 数值类型
     * @return self
     */
    public function reinterpret(NumType $typ): self
    {
        Encoder::reinterpret($this->fn, $typ->data());
        return $this;
    }

    /**
     * 符号扩展8位
     *
     * @param ValType $typ 值类型
     * @return self
     */
    public function signExtend8(ValType $typ): self
    {
        Encoder::intOnly($this->fn, 'signExtend8', $typ->data());
        return $this;
    }

    /**
     * 符号扩展16位
     *
     * @param ValType $typ 值类型
     * @return self
     */
    public function signExtend16(ValType $typ): self
    {
        Encoder::intOnly($this->fn, 'signExtend16', $typ->data());
        return $this;
    }

    /**
     * 符号扩展32位（i64专用）
     *
     * @return self
     */
    public function signExtend32(): self
    {
        Encoder::simple($this->fn, 'signExtend32');
        return $this;
    }

    // ==================== 控制流 ====================

    /**
     * 创建 block 块
     *
     * @param array<ValType> $params 参数类型数组
     * @param array<ValType> $results 返回类型数组
     * @return integer 标签索引
     */
    public function block(array $params, array $results): int
    {
        return Encoder::block($this->fn, $this->typeBytes($params), $this->typeBytes($results));
    }

    /**
     * 创建 loop 块
     *
     * @param array<ValType> $params 参数类型数组
     * @param array<ValType> $results 返回类型数组
     * @return integer 标签索引
     */
    public function loop(array $params, array $results): int
    {
        return Encoder::loop($this->fn, $this->typeBytes($params), $this->typeBytes($results));
    }

    /**
     * 创建 if 块
     *
     * @param array<ValType> $params 参数类型数组
     * @param array<ValType> $results 返回类型数组
     * @return integer 标签索引
     */
    public function if_(array $params, array $results): int
    {
        return Encoder::cIf($this->fn, $this->typeBytes($params), $this->typeBytes($results));
    }

    /**
     * else 分支
     *
     * @param integer $label 标签索引
     * @return self
     */
    public function else_(int $label): self
    {
        Encoder::cElse($this->fn, $label);
        return $this;
    }

    /**
     * 结束块
     *
     * @param integer $label 标签索引
     * @return self
     */
    public function end(int $label): self
    {
        Encoder::cEnd($this->fn, $label);
        return $this;
    }

    /**
     * 无条件跳转
     *
     * @param integer $label 标签索引
     * @return self
     */
    public function br(int $label): self
    {
        Encoder::cBr($this->fn, $label);
        return $this;
    }

    /**
     * 条件跳转
     *
     * @param integer $label 标签索引
     * @return self
     */
    public function brIf(int $label): self
    {
        Encoder::cBrIf($this->fn, $label);
        return $this;
    }

    /**
     * 返回
     *
     * @return self
     */
    public function return_(): self
    {
        Encoder::simple($this->fn, 'return');
        return $this;
    }

    /**
     * select 指令
     *
     * @return self
     */
    public function select(): self
    {
        Encoder::simple($this->fn, 'select');
        return $this;
    }

    /**
     * 丢弃栈顶值
     *
     * @return self
     */
    public function drop(): self
    {
        Encoder::simple($this->fn, 'drop');
        return $this;
    }

    /**
     * 不可达指令
     *
     * @return self
     */
    public function unreachable(): self
    {
        Encoder::simple($this->fn, 'unreachable');
        return $this;
    }

    /**
     * 空操作指令
     *
     * @return self
     */
    public function nop(): self
    {
        Encoder::simple($this->fn, 'nop');
        return $this;
    }

    /**
     * 获取当前补丁位置
     *
     * @return integer 位置
     */
    public function patchPos(): int
    {
        return $this->fn->patchPos();
    }

    /**
     * 补丁
     *
     * @param integer $loc 位置
     * @param integer $begin 起始
     * @return self
     */
    public function patch(int $loc, int $begin): self
    {
        $this->fn->patch($loc, $begin);
        return $this;
    }

    /**
     * 设置导出名称
     *
     * @param string $name 导出名称
     * @return self
     */
    public function exportName(string $name): self
    {
        $this->fn->exportName = $name;
        return $this;
    }

    // ==================== 函数调用 ====================

    /**
     * 调用函数
     *
     * @param string $name 函数名称
     * @return self
     */
    public function call(string $name): self
    {
        Encoder::call($this->fn, $name);
        return $this;
    }

    /**
     * 调用导入函数
     *
     * @param string $mod_name 模块名称
     * @param string $fn_name 函数名称
     * @return self
     */
    public function callImport(string $mod_name, string $fn_name): self
    {
        Encoder::callImport($this->fn, $mod_name, $fn_name);
        return $this;
    }

    /**
     * 间接调用表中的函数
     *
     * 函数索引从栈上弹出（i32）。类型索引可通过 `Module::newFnType` 获取。
     *
     * @param integer $typeidx 类型索引
     * @param integer $tableidx 表索引
     * @example ```php
     * $fn->callIndirect($type_idx, 0);
     * ```
     * @return self
     */
    public function callIndirect(int $typeidx, int $tableidx): self
    {
        Encoder::callIndirect($this->fn, $typeidx, $tableidx);
        return $this;
    }

    // ==================== 内存操作 ====================

    /**
     * 内存加载
     *
     * @param NumType $typ 数值类型
     * @param integer $align 对齐
     * @param integer $offset 偏移量
     * @return self
     */
    public function load(NumType $typ, int $align, int $offset): self
    {
        Encoder::load($this->fn, $typ->data(), $align, $offset);
        return $this;
    }

    /**
     * 8位内存加载
     *
     * @param NumType $typ 数值类型
     * @param boolean $signed 是否有符号
     * @param integer $align 对齐
     * @param integer $offset 偏移量
     * @return self
     */
    public function load8(NumType $typ, bool $signed, int $align, int $offset): self
    {
        Encoder::load8($this->fn, $typ->data(), $signed, $align, $offset);
        return $this;
    }

    /**
     * 16位内存加载
     *
     * @param NumType $typ 数值类型
     * @param boolean $signed 是否有符号
     * @param integer $align 对齐
     * @param integer $offset 偏移量
     * @return self
     */
    public function load16(NumType $typ, bool $signed, int $align, int $offset): self
    {
        Encoder::load16($this->fn, $typ->data(), $signed, $align, $offset);
        return $this;
    }

    /**
     * 32位内存加载（i64专用）
     *
     * @param boolean $signed 是否有符号
     * @param integer $align 对齐
     * @param integer $offset 偏移量
     * @return self
     */
    public function load32I64(bool $signed, int $align, int $offset): self
    {
        Encoder::load32I64($this->fn, $signed, $align, $offset);
        return $this;
    }

    /**
     * 内存存储
     *
     * @param NumType $typ 数值类型
     * @param integer $align 对齐
     * @param integer $offset 偏移量
     * @return self
     */
    public function store(NumType $typ, int $align, int $offset): self
    {
        Encoder::store($this->fn, $typ->data(), $align, $offset);
        return $this;
    }

    /**
     * 8位内存存储
     *
     * @param NumType $typ 数值类型
     * @param integer $align 对齐
     * @param integer $offset 偏移量
     * @return self
     */
    public function store8(NumType $typ, int $align, int $offset): self
    {
        Encoder::store8($this->fn, $typ->data(), $align, $offset);
        return $this;
    }

    /**
     * 16位内存存储
     *
     * @param NumType $typ 数值类型
     * @param integer $align 对齐
     * @param integer $offset 偏移量
     * @return self
     */
    public function store16(NumType $typ, int $align, int $offset): self
    {
        Encoder::store16($this->fn, $typ->data(), $align, $offset);
        return $this;
    }

    /**
     * 32位内存存储（i64专用）
     *
     * @param integer $align 对齐
     * @param integer $offset 偏移量
     * @return self
     */
    public function store32I64(int $align, int $offset): self
    {
        Encoder::store32I64($this->fn, $align, $offset);
        return $this;
    }

    /**
     * 获取内存大小
     *
     * @return self
     */
    public function memorySize(): self
    {
        Encoder::simple($this->fn, 'memorySize');
        return $this;
    }

    /**
     * 增长内存
     *
     * @return self
     */
    public function memoryGrow(): self
    {
        Encoder::simple($this->fn, 'memoryGrow');
        return $this;
    }

    /**
     * 内存初始化
     *
     * @param integer $idx 数据段索引
     * @return self
     */
    public function memoryInit(int $idx): self
    {
        Encoder::memoryInit($this->fn, $idx);
        return $this;
    }

    /**
     * 数据段丢弃
     *
     * @param integer $idx 数据段索引
     * @return self
     */
    public function dataDrop(int $idx): self
    {
        Encoder::dataDrop($this->fn, $idx);
        return $this;
    }

    /**
     * 内存复制
     *
     * @return self
     */
    public function memoryCopy(): self
    {
        Encoder::simple($this->fn, 'memoryCopy');
        return $this;
    }

    /**
     * 内存填充
     *
     * @return self
     */
    public function memoryFill(): self
    {
        Encoder::simple($this->fn, 'memoryFill');
        return $this;
    }

    // ==================== 引用操作 ====================

    /**
     * 创建空引用
     *
     * @param RefType $rt 引用类型
     * @return self
     */
    public function refNull(RefType $rt): self
    {
        Encoder::refNull($this->fn, $rt->data());
        return $this;
    }

    /**
     * 创建函数引用
     *
     * @param string $name 函数名称
     * @return self
     */
    public function refFunc(string $name): self
    {
        Encoder::refFunc($this->fn, $name);
        return $this;
    }

    /**
     * 创建导入函数引用
     *
     * @param string $mod_name 模块名称
     * @param string $fn_name 函数名称
     * @return self
     */
    public function refFuncImport(string $mod_name, string $fn_name): self
    {
        Encoder::refFuncImport($this->fn, $mod_name, $fn_name);
        return $this;
    }

    /**
     * 判断引用是否为空
     *
     * @param RefType $rt 引用类型
     * @return self
     */
    public function refIsNull(RefType $rt): self
    {
        Encoder::refIsNull($this->fn);
        return $this;
    }

    // ==================== 表操作 ====================

    /**
     * 获取表中的引用
     *
     * @param integer $tableidx 表索引
     * @example ```php
     * $fn->tableGet(0);
     * ```
     * @return self
     */
    public function tableGet(int $tableidx): self
    {
        Encoder::tableGet($this->fn, $tableidx);
        return $this;
    }

    /**
     * 设置表中的引用
     *
     * @param integer $tableidx 表索引
     * @example ```php
     * $fn->tableSet(0);
     * ```
     * @return self
     */
    public function tableSet(int $tableidx): self
    {
        Encoder::tableSet($this->fn, $tableidx);
        return $this;
    }

    /**
     * 获取表大小
     *
     * @param integer $tableidx 表索引
     * @example ```php
     * $fn->tableSize(0);
     * ```
     * @return self
     */
    public function tableSize(int $tableidx): self
    {
        Encoder::tableSize($this->fn, $tableidx);
        return $this;
    }

    /**
     * 增长表
     *
     * @param integer $tableidx 表索引
     * @example ```php
     * $fn->tableGrow(0);
     * ```
     * @return self
     */
    public function tableGrow(int $tableidx): self
    {
        Encoder::tableGrow($this->fn, $tableidx);
        return $this;
    }

    /**
     * 填充表
     *
     * @param integer $tableidx 表索引
     * @example ```php
     * $fn->tableFill(0);
     * ```
     * @return self
     */
    public function tableFill(int $tableidx): self
    {
        Encoder::tableFill($this->fn, $tableidx);
        return $this;
    }

    /**
     * 把值类型数组转换为规范字节数组
     *
     * @param array<ValType> $types
     * @return array<int,int>
     */
    private function typeBytes(array $types): array
    {
        $out = [];
        foreach ($types as $type) {
            $out[] = $type->data();
        }
        return $out;
    }
}