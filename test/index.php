<?php

require dirname(__DIR__) . "/vendor/autoload.php";

use Kingbes\Wasm\Module;
use Kingbes\Wasm\ValType;
use Kingbes\Wasm\NumType;

// 1. 创建模块
$mod = new Module();

// 2. 创建函数
$fn = $mod->newFn([
    "name"    => "add",
    "params"  => [ValType::I32, ValType::I32],
    "results" => [ValType::I32],
]);

// 3. 编写函数体（链式调用）
$fn->getLocal(0)   // 获取第1个参数
    ->getLocal(1)   // 获取第2个参数
    ->add(NumType::I32); // i32 加法

// 4. 提交并导出函数
$mod->commit($fn);

// 5. 编译为 wasm 文件
$res = $mod->compile("./add.wasm");

var_dump($res);
