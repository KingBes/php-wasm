<?php

namespace Kingbes\Wasm;

use Kingbes\Wasm\Func;
use Kingbes\Wasm\Wasm\ModuleState;

/**
 * 模块类
 * @example ```php
 * use Kingbes\Wasm\Module;
 * ```
 */
class Module extends Base
{
    /**
     * 模块状态
     *
     * @var ModuleState
     */
    public ModuleState $mod;

    /**
     * 构造函数
     * @example ```php 
     * $mod = new Module();
     * ```
     */
    public function __construct()
    {
        $this->mod = new ModuleState();
    }

    /**
     * 创建函数
     *
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
     * $mod = new Module();
     * $func = $mod->newFn($config, true);
     * ```
     * @return Func 函数对象
     */
    public function newFn(array $config, bool $debug = false): Func
    {
        return new Func($this->mod, $config, $debug);
    }

    /**
     * 导入函数
     *
     * @param string $mod_name 模块名称
     * @param string $fn_name 函数名称
     * @param array<string, array<ValType|string>|string> $config 函数配置
     * @param boolean $debug 是否开启调试模式
     * @example ```php
     * $config = [
     *     "params" => [ValType::I32, ValType::I32], // 请求参数类型 必填
     *     "results" => [ValType::I32], // 返回参数类型 必填
     *     "type_name" => "add_type", // debug 模式下必填，类型名称
     * ];
     * $mod = new Module();
     * $func = $mod->impFn("add_mod","add_fn",$config, true);
     * ```
     * @return void
     */
    public function impFn(
        string $mod_name,
        string $fn_name,
        array $config,
        bool $debug = false
    ): void {
        if ($debug) {
            $fun_type = new FunType($config["params"], $config["results"], $config["type_name"]);
            $this->mod->newFunctionImportDebug($mod_name, $fn_name, $fun_type->data);
        } else {
            $this->mod->newFunctionImport(
                $mod_name,
                $fn_name,
                $this->typeBytes($config["params"]),
                $this->typeBytes($config["results"])
            );
        }
    }

    /**
     * 创建函数类型并返回其类型索引
     *
     * 相同签名的类型会被复用（驻留），返回的索引可用于 `call_indirect`。
     *
     * @param array<ValType> $params 请求参数类型
     * @param array<ValType> $results 返回参数类型
     * @param string|null $type_name 类型名称（仅调试信息使用）
     * @example ```php
     * $mod = new Module();
     * $type_idx = $mod->newFnType([ValType::I32, ValType::I32], [ValType::I32]);
     * ```
     * @return integer 类型索引
     */
    public function newFnType(array $params, array $results, ?string $type_name = null): int
    {
        return $this->mod->newFnType($this->typeBytes($params), $this->typeBytes($results), $type_name);
    }

    /**
     * 创建全局变量
     *
     * @param string $name 变量名称
     * @param boolean $exp 是否导出
     * @param ValType $vty 变量类型
     * @param boolean $mut 是否可变
     * @param ConstExpression $init 初始值
     * @example ```php
     * $mod = new Module();
     * $mod->newGlobal("a", true, ValType::I32, true, new ConstExpression(10));
     * ```
     * @return integer 变量索引
     */
    public function newGlobal(
        string $name,
        bool $exp,
        ValType $vty,
        bool $mut,
        ConstExpression $init
    ): int {
        return $this->mod->newGlobal($name, $exp, $vty->data(), $mut, $init->data);
    }

    /**
     * 导入全局变量
     *
     * @param string $mod_name 模块名称
     * @param string $global_name 变量名称
     * @param ValType $vty 变量类型
     * @param boolean $mut 是否可变
     * @return integer 变量索引
     */
    public function newGlobaImp(
        string $mod_name,
        string $global_name,
        ValType $vty,
        bool $mut
    ): int {
        return $this->mod->newGlobalImport($mod_name, $global_name, $vty->data(), $mut);
    }

    /**
     * 全局变量设置初始化
     *
     * @param integer $index 索引
     * @param ConstExpression $init 初始化表达式
     * @example ```php
     * $mod = new Module();
     * $mod->assignGlobalInit(0, new ConstExpression(10));
     * ```
     * @return void
     */
    public function assignGlobalInit(int $index, ConstExpression $init): void
    {
        $this->mod->assignGlobalInit($index, $init->data);
    }

    /**
     * 配置模块的线性内存
     *
     * @param string $name 内存段名称
     * @param boolean $exp 是否导出内存
     * @param integer $min 最小内存页数（每页 64KB）
     * @param integer $max 最大内存页数，0 表示无上限
     * @example ```php
     * $mod = new Module();
     * $mod->assignMemory("mem", true, 1, 10);
     * ```
     * @return void
     */
    public function assignMemory(string $name, bool $exp, int $min, int $max): void
    {
        $this->mod->assignMemory($name, $exp, $min, $max === 0 ? null : $max);
    }

    /**
     * 配置模块的开始函数,类似c 的main函数
     *
     * @param string $name 函数名称
     * @example ```php
     * $mod = new Module();
     * $mod->assignStart("main");
     * ```
     * @return void
     */
    public function assignStart(string $name): void
    {
        $this->mod->assignStart($name);
    }

    /**
     * 声明一个表并返回其索引
     *
     * @param string $name 表名称
     * @param boolean $exp 是否导出
     * @param RefType $reftype 元素类型
     * @param integer $min 最小元素数量
     * @param integer $max 最大元素数量，0 表示无上限
     * @example ```php
     * $mod = new Module();
     * $table_idx = $mod->assignTable("tbl", true, RefType::FuncRef, 1, 0);
     * ```
     * @return integer 表索引
     */
    public function assignTable(string $name, bool $exp, RefType $reftype, int $min, int $max): int
    {
        return $this->mod->assignTable($name, $exp, $reftype->data(), $min, $max === 0 ? null : $max);
    }

    /**
     * 创建主动元素段，用于初始化表
     *
     * @param integer $tableidx 表索引
     * @param integer $offset 起始位置
     * @param array<string> $funcs 函数名称数组
     * @example ```php
     * $mod = new Module();
     * $mod->newActiveElement(0, 0, ["add"]);
     * ```
     * @return integer 元素段索引
     */
    public function newActiveElement(int $tableidx, int $offset, array $funcs): int
    {
        return $this->mod->newActiveElement($tableidx, $offset, $funcs);
    }

    /**
     * 创建声明式元素段，用于声明函数引用（不占用表空间）
     *
     * @param array<string> $funcs 函数名称数组
     * @example ```php
     * $mod = new Module();
     * $mod->newDeclarativeElement(["add"]);
     * ```
     * @return integer 元素段索引
     */
    public function newDeclarativeElement(array $funcs): int
    {
        return $this->mod->newDeclarativeElement($funcs);
    }

    /**
     * 函数提交
     *
     * @param Func $fn 函数对象
     * @param boolean $is_export 是否导出该函数
     * @example ```php
     * $mod = new Module();
     * $mod->commit($func, true);
     * ```
     * @return void
     */
    public function commit(Func $fn, bool $is_export = true)
    {
        $this->mod->commit($fn->fn, $is_export);
    }

    /**
     * 编译模块
     *
     * @param string $file 编译的webm文件路径
     * @example ```php
     * $mod = new Module();
     * $mod->compile("./test.wasm");
     * ```
     * @return boolean 结果: true 成功 false 失败
     */
    public function compile(string $file): bool
    {
        return file_put_contents($file, $this->mod->compile()) !== false;
    }

    /**
     * 编译模块并返回二进制字节
     *
     * @example ```php
     * $mod = new Module();
     * $bytes = $mod->toBytes();
     * ```
     * @return string
     */
    public function toBytes(): string
    {
        return $this->mod->compile();
    }

    /**
     * 调试模式
     *
     * @param string $name 模块名
     * @example ```php 
     * $mod = new Module();
     * $mod->enableDebug("wasm_mod");
     * ```
     * @return void
     */
    public function enableDebug(string $name): void
    {
        $this->mod->enableDebug($name);
    }

    /**
     * 创建数据段
     *
     * @param string $name 数据段名称
     * @param integer $pos 内存起始位置
     * @param string $data 数据内容
     * @example ```php
     * $mod = new Module();
     * $mod->newDataSegment("data_name" ,1 ,"一个数据段");
     * ```
     * @return integer
     */
    public function newDataSegment(string $name, int $pos, string $data): int
    {
        return $this->mod->newDataSegment($name, $pos, $data);
    }

    /**
     * 创建被动数据段
     *
     * @param string $name 数据段名称
     * @param string $data 数据内容
     * @example  ```php
     * $mod = new Module();
     * $mod->newPassiveDataSegment("data_name" ,"一个被动数据段");
     * ```
     * @return void
     */
    public function newPassiveDataSegment(string $name, string $data): void
    {
        $this->mod->newPassiveDataSegment($name, $data);
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