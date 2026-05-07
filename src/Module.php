<?php

namespace Kingbes\Wasm;

use Kingbes\Wasm\Func;
use \FFI\CData;

/**
 * 模块类
 * @example ```php
 * use Kingbes\Wasm\Module;
 * ```
 */
class Module extends Base
{
    /**
     * 数据指针
     *
     * @var CData
     */
    public CData $mod;

    /**
     * 构造函数
     * @example ```php 
     * $mod = new Module();
     * ```
     */
    public function __construct()
    {
        $this->mod = self::ffi()->create_mod();
    }

    /**
     * 创建函数
     *
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
     *     "params" => [ValType::INT32, ValType::INT32], // 请求参数类型 必填
     *     "results" => [ValType::INT32], // 返回参数类型 必填
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
        $c_params = $this->creatValTypeArr();
        foreach ($config["params"] as $param) {
            $c_params = $this->addValTypeArr($c_params, $param);
        }
        $c_results = $this->creatValTypeArr();
        foreach ($config["results"] as $res) {
            $c_results = $this->addValTypeArr($c_results, $res);
        }
        if ($debug) {
            $fun_type = new FunType($config["params"], $config["results"], $config["type_name"]);
            self::ffi()->new_fn_import_debug($this->mod, $mod_name, $fn_name, $fun_type);
        } else {
            self::ffi()->new_fn_import($this->mod, $mod_name, $fn_name, $c_params, $c_results);
        }
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
        return self::ffi()->new_global(
            $this->mod,
            $name,
            $exp,
            $vty->data(),
            $mut,
            $init
        );
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
        return self::ffi()->new_global_import($this->mod, $mod_name, $global_name, $vty->data(), $mut);
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
        self::ffi()->assign_global_init($this->mod, $index, $init);
    }

    /**
     * 配置模块的线性内存
     *
     * @param string $name 内存段名称
     * @param boolean $exp 是否导出内存
     * @param integer $min 最小内存页数（每页 64KB）
     * @param integer $max 最大内存页数
     * @example ```php
     * $mod = new Module();
     * $mod->assignMemory("mem", true, 1, 10);
     * ```
     * @return void
     */
    public function assignMemory(string $name, bool $exp, int $min, int $max): void
    {
        self::ffi()->assign_memory($this->mod, $name, $exp, $min, $max);
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
        self::ffi()->assign_start($this->mod, $name);
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
        self::ffi()->mod_commit($this->mod, $fn->fn, $is_export);
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
        return self::ffi()->mod_compile($file, $this->mod);
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
        self::ffi()->mod_enable_debug($this->mod, $name);
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
        $c_data = self::ffi()->help_str_to_u8s($data);
        return self::ffi()->mod_new_data_segment($this->mod, $name, $pos, $c_data);
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
        $c_data = self::ffi()->help_str_to_u8s($data);
        self::ffi()->mod_new_passive_data_segment($this->mod, $name, $c_data);
    }
}
