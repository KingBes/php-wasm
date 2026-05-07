<?php

namespace Kingbes\Wasm;

use \FFI\CData;

class Func extends Base
{
    /**
     * 数据指针
     *
     * @var CData
     */
    public CData $fn;

    /**
     * 构造函数
     *
     * @param CData $mod 模块对象指针
     * @param array<string, array<ValType|string>|string> $config 函数配置
     * @param boolean $debug 是否开启调试模式
     * @example ```php
     * $config = [
     *     "name" => "add", // 函数名 必填
     *     "params" => [ValType::INT32, ValType::INT32], // 请求参数类型 必填
     *     "results" => [ValType::INT32], // 返回参数类型 必填
     *     "param_names" => ["a", "b"], // debug 模式下必填，参数名数组，与params数组顺序一致
     *     "type_name" => "add_type", // debug 模式下必填，类型名称
     * ];
     * $func = new Func($mod, $config, true);
     * ```
     */
    public function __construct(CData $mod, array $config, bool $debug = false)
    {
        $c_param = $this->creatValTypeArr();
        foreach ($config["params"] as $param) {
            $c_param = $this->addValTypeArr($c_param, $param);
        }
        $c_results = $this->creatValTypeArr();
        foreach ($config["results"] as $res) {
            $c_results = $this->addValTypeArr($c_results, $res);
        }
        if ($debug) {
            $c_param_names = $this->creatStrArr();
            foreach ($config["param_names"] as $param_name) {
                $c_param_names = $this->addStrArr($c_param_names, $param_name);
            }
            $fun_type = new FunType($config["params"], $config["results"], $config["type_name"]);
            $this->fn = self::ffi()->new_debug_fn($mod, $config["name"], $fun_type->data, $c_param_names);
        } else {
            $this->fn = self::ffi()->new_fn($mod, $config["name"], $c_param, $c_results);
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
            self::ffi()->fn_i32_const($this->fn, $val);
        } else {
            self::ffi()->fn_f32_const($this->fn, $val);
        }
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
        return self::ffi()->fn_new_local($this->fn, $vty->data());
    }

    /**
     * 获取局部变量
     *
     * @param integer $index 变量索引
     * @return self
     */
    public function getLocal(int $index): self
    {
        self::ffi()->fn_local_get($this->fn, $index);
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
        return self::ffi()->fn_new_local_named($this->fn, $vty->data(), $name);
    }

    /**
     * 设置局部变量
     *
     * @param integer $index 变量索引
     * @return self
     */
    public function setLocal(int $index): self
    {
        self::ffi()->fn_local_set($this->fn, $index);
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
        self::ffi()->fn_local_tee($this->fn, $index);
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
        self::ffi()->fn_global_get($this->fn, $index);
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
        self::ffi()->fn_global_set($this->fn, $index);
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
        self::ffi()->fn_add($this->fn, $typ->data());
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
        self::ffi()->fn_sub($this->fn, $typ->data());
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
        self::ffi()->fn_mul($this->fn, $typ->data());
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
        self::ffi()->fn_div($this->fn, $typ->data(), $signed);
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
        self::ffi()->fn_rem($this->fn, $typ->data(), $signed);
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
        self::ffi()->fn_abs($this->fn, $typ->data());
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
        self::ffi()->fn_neg($this->fn, $typ->data());
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
        self::ffi()->fn_ceil($this->fn, $typ->data());
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
        self::ffi()->fn_floor($this->fn, $typ->data());
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
        self::ffi()->fn_trunc($this->fn, $typ->data());
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
        self::ffi()->fn_nearest($this->fn, $typ->data());
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
        self::ffi()->fn_sqrt($this->fn, $typ->data());
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
        self::ffi()->fn_min($this->fn, $typ->data());
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
        self::ffi()->fn_max($this->fn, $typ->data());
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
        self::ffi()->fn_copysign($this->fn, $typ->data());
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
        self::ffi()->fn_b_and($this->fn, $typ->data());
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
        self::ffi()->fn_b_or($this->fn, $typ->data());
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
        self::ffi()->fn_b_xor($this->fn, $typ->data());
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
        self::ffi()->fn_b_shl($this->fn, $typ->data());
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
        self::ffi()->fn_b_shr($this->fn, $typ->data(), $signed);
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
        self::ffi()->fn_clz($this->fn, $typ->data());
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
        self::ffi()->fn_ctz($this->fn, $typ->data());
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
        self::ffi()->fn_popcnt($this->fn, $typ->data());
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
        self::ffi()->fn_rotl($this->fn, $typ->data());
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
        self::ffi()->fn_rotr($this->fn, $typ->data());
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
        self::ffi()->fn_eqz($this->fn, $typ->data());
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
        self::ffi()->fn_eq($this->fn, $typ->data());
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
        self::ffi()->fn_ne($this->fn, $typ->data());
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
        self::ffi()->fn_lt($this->fn, $typ->data(), $signed);
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
        self::ffi()->fn_gt($this->fn, $typ->data(), $signed);
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
        self::ffi()->fn_le($this->fn, $typ->data(), $signed);
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
        self::ffi()->fn_ge($this->fn, $typ->data(), $signed);
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
        self::ffi()->fn_cast($this->fn, $from->data(), $signed, $to->data());
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
        self::ffi()->fn_cast_trapping($this->fn, $from->data(), $signed, $to->data());
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
        self::ffi()->fn_reinterpret($this->fn, $typ->data());
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
        self::ffi()->fn_sign_extend8($this->fn, $typ->data());
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
        self::ffi()->fn_sign_extend16($this->fn, $typ->data());
        return $this;
    }

    /**
     * 符号扩展32位（i64专用）
     *
     * @return self
     */
    public function signExtend32(): self
    {
        self::ffi()->fn_sign_extend32($this->fn);
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
        $c_params = $this->creatValTypeArr();
        foreach ($params as $p) {
            $c_params = $this->addValTypeArr($c_params, $p);
        }
        $c_results = $this->creatValTypeArr();
        foreach ($results as $r) {
            $c_results = $this->addValTypeArr($c_results, $r);
        }
        return self::ffi()->fn_c_block($this->fn, $c_params, $c_results);
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
        $c_params = $this->creatValTypeArr();
        foreach ($params as $p) {
            $c_params = $this->addValTypeArr($c_params, $p);
        }
        $c_results = $this->creatValTypeArr();
        foreach ($results as $r) {
            $c_results = $this->addValTypeArr($c_results, $r);
        }
        return self::ffi()->fn_c_loop($this->fn, $c_params, $c_results);
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
        $c_params = $this->creatValTypeArr();
        foreach ($params as $p) {
            $c_params = $this->addValTypeArr($c_params, $p);
        }
        $c_results = $this->creatValTypeArr();
        foreach ($results as $r) {
            $c_results = $this->addValTypeArr($c_results, $r);
        }
        return self::ffi()->fn_c_if($this->fn, $c_params, $c_results);
    }

    /**
     * else 分支
     *
     * @param integer $label 标签索引
     * @return self
     */
    public function else_(int $label): self
    {
        self::ffi()->fn_c_else($this->fn, $label);
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
        self::ffi()->fn_c_end($this->fn, $label);
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
        self::ffi()->fn_c_br($this->fn, $label);
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
        self::ffi()->fn_c_br_if($this->fn, $label);
        return $this;
    }

    /**
     * 返回
     *
     * @return self
     */
    public function return_(): self
    {
        self::ffi()->fn_c_return($this->fn);
        return $this;
    }

    /**
     * select 指令
     *
     * @return self
     */
    public function select(): self
    {
        self::ffi()->fn_c_select($this->fn);
        return $this;
    }

    /**
     * 丢弃栈顶值
     *
     * @return self
     */
    public function drop(): self
    {
        self::ffi()->fn_drop($this->fn);
        return $this;
    }

    /**
     * 不可达指令
     *
     * @return self
     */
    public function unreachable(): self
    {
        self::ffi()->fn_unreachable($this->fn);
        return $this;
    }

    /**
     * 空操作指令
     *
     * @return self
     */
    public function nop(): self
    {
        self::ffi()->fn_nop($this->fn);
        return $this;
    }

    /**
     * 获取当前补丁位置
     *
     * @return integer 位置
     */
    public function patchPos(): int
    {
        return self::ffi()->fn_patch_pos($this->fn);
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
        self::ffi()->fn_patch($this->fn, $loc, $begin);
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
        self::ffi()->fn_export_name($this->fn, $name);
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
        self::ffi()->fn_call($this->fn, $name);
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
        self::ffi()->fn_call_import($this->fn, $mod_name, $fn_name);
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
        self::ffi()->fn_load($this->fn, $typ->data(), $align, $offset);
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
        self::ffi()->fn_load8($this->fn, $typ->data(), $signed, $align, $offset);
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
        self::ffi()->fn_load16($this->fn, $typ->data(), $signed, $align, $offset);
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
        self::ffi()->fn_load32_i64($this->fn, $signed, $align, $offset);
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
        self::ffi()->fn_store($this->fn, $typ->data(), $align, $offset);
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
        self::ffi()->fn_store8($this->fn, $typ->data(), $align, $offset);
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
        self::ffi()->fn_store16($this->fn, $typ->data(), $align, $offset);
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
        self::ffi()->fn_store32_i64($this->fn, $align, $offset);
        return $this;
    }

    /**
     * 获取内存大小
     *
     * @return self
     */
    public function memorySize(): self
    {
        self::ffi()->fn_memory_size($this->fn);
        return $this;
    }

    /**
     * 增长内存
     *
     * @return self
     */
    public function memoryGrow(): self
    {
        self::ffi()->fn_memory_grow($this->fn);
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
        self::ffi()->fn_memory_init($this->fn, $idx);
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
        self::ffi()->fn_data_drop($this->fn, $idx);
        return $this;
    }

    /**
     * 内存复制
     *
     * @return self
     */
    public function memoryCopy(): self
    {
        self::ffi()->fn_memory_copy($this->fn);
        return $this;
    }

    /**
     * 内存填充
     *
     * @return self
     */
    public function memoryFill(): self
    {
        self::ffi()->fn_memory_fill($this->fn);
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
        self::ffi()->fn_ref_null($this->fn, $rt->data());
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
        self::ffi()->fn_ref_func($this->fn, $name);
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
        self::ffi()->fn_ref_func_import($this->fn, $mod_name, $fn_name);
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
        self::ffi()->fn_ref_is_null($this->fn, $rt->data());
        return $this;
    }
}
