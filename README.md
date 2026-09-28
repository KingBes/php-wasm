# php-wasm

> 纯 PHP 环境下以编程方式构建 WebAssembly (Wasm) 二进制模块的库，编码引擎完全由 PHP 实现，无需任何扩展、原生库或外部工具链。

## ✨ 核心特性

- **纯 PHP 生成 Wasm** — 无需外部工具链与原生扩展，直接在 PHP 中编写并编译 `.wasm` 二进制模块
- **丰富的 Wasm 指令集** — 覆盖常量、局部/全局变量、算术、位运算、比较、类型转换、控制流、内存操作、表操作、引用操作
- **表与间接调用** — 支持表声明、元素段（主动/声明式）、`call_indirect` 与 `table.*` 系列指令
- **链式调用 API** — `$fn->getLocal(0)->getLocal(1)->add(NumType::I32)` 风格流畅编程
- **Debug 模式** — 生成带 name 段（模块名、函数名、参数名、类型名）的 Wasm 模块
- **导入/导出与内存管理** — 函数与全局变量导入导出、可变全局变量、线性内存、数据段（主动/被动）
- **跨平台** — 纯 PHP 实现，任意可运行 PHP 的环境均可使用

## 📋 环境要求

| 依赖 | 说明 |
|---|---|
| PHP | >= 8.2 |
| 操作系统 | 任意可运行 PHP 的环境 |

## 🚀 安装

```bash
composer require kingbes/wasm
```

## ⚡ 快速开始

```php
<?php

require "vendor/autoload.php";

use Kingbes\Wasm\Module;
use Kingbes\Wasm\ValType;
use Kingbes\Wasm\NumType;

$mod = new Module();

// 创建函数 add(i32, i32) -> i32
$fn = $mod->newFn([
    "name"    => "add",
    "params"  => [ValType::I32, ValType::I32],
    "results" => [ValType::I32],
]);

// 编写函数体（链式调用）
$fn->getLocal(0)
   ->getLocal(1)
   ->add(NumType::I32);

// 提交并编译输出
$mod->commit($fn);
$mod->compile("./add.wasm");
```

## 📚 文档

| 文档 | 说明 |
|---|---|
| [Module 类](doc/module.md) | 模块创建、函数管理、全局变量、内存、表、数据段、编译 |
| [Func 类](doc/func.md) | 函数体指令：常量、局部/全局变量、算术、位运算、比较、类型转换、控制流、函数调用、表、内存、引用 |
| [类型枚举](doc/types.md) | `ValType`、`NumType`、`RefType` 枚举说明 |
| [ConstExpression](doc/const-expression.md) | 常量表达式与全局变量初始化 |
| [使用示例](doc/examples.md) | 覆盖常见场景的完整示例，含表与间接调用 |

## 📄 License

[MIT](LICENSE)