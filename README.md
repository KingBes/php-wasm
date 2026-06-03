# php-wasm

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.2-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)
[![Composer](https://img.shields.io/badge/composer-kingbes%2Fwasm-orange)](https://packagist.org/packages/kingbes/wasm)

纯 PHP 环境下以编程方式构建 WebAssembly (Wasm) 二进制模块的库，底层通过 FFI 调用跨平台 C 编译库实现高性能指令编码。

## ✨ 核心特性

- **纯 PHP 生成 Wasm** — 无需外部工具链，直接在 PHP 中编写并编译 `.wasm` 二进制模块
- **完整 Wasm 指令集** — 覆盖常量、局部/全局变量、算术、位运算、比较、类型转换、控制流（block/loop/if-else/br）、内存操作、引用操作
- **链式调用 API** — `$fn->getLocal(0)->getLocal(1)->add(NumType::I32)` 风格流畅编程
- **Debug 模式** — 生成带 DWARF 调试信息（参数名、类型名）的 Wasm 模块
- **函数导入/导出** — 支持 `import` 外部函数/全局变量，`export` 内部函数/内存
- **全局变量 & 内存管理** — 可变的全局变量、线性内存配置、数据段（主动/被动）
- **跨平台支持** — Windows/Linux/macOS，x86_64/arm64 架构

## 📋 环境要求

| 依赖 | 说明 |
|---|---|
| PHP | >= 8.2 |
| `ext-ffi` | FFI 扩展，启用 |
| 操作系统 | Windows x86_64 / Linux x86_64\|arm64 / macOS arm64 |

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

// 1. 创建模块
$mod = new Module();

// 2. 创建函数
$fn = $mod->newFn([
    "name"    => "add",                          // 函数名
    "params"  => [ValType::I32, ValType::I32],   // 参数类型
    "results" => [ValType::I32],                 // 返回值类型
]);

// 3. 编写函数体（链式调用）
$fn->getLocal(0)            // local.get 0 — 压入第一个参数
   ->getLocal(1)            // local.get 1 — 压入第二个参数
   ->add(NumType::I32);     // i32.add

// 4. 提交并导出
$mod->commit($fn);

// 5. 编译输出
$mod->compile("./add.wasm");
```

等价 WAT 文本：

```wat
(module
  (func (export "add") (param i32 i32) (result i32)
    local.get 0
    local.get 1
    i32.add
  )
)
```

## 📐 架构概览

```
src/
├── Base.php              # FFI 基础抽象类，跨平台库加载 & C 数组辅助方法
├── Module.php            # Wasm 模块管理器：函数/全局变量/内存/数据段/编译
├── Func.php              # 函数体指令构造器（链式 API）
├── ConstExpression.php   # 常量表达式，用于全局变量初始化
├── FunType.php            # 函数类型对象，Debug 模式下使用
├── ValType.php           # 值类型枚举（i32/i64/f32/f64/v128/funcref/externref）
├── NumType.php           # 数值类型枚举（i32/i64/f32/f64）
├── RefType.php           # 引用类型枚举（funcref/externref）
└── Wasm.h                # C 函数声明头文件
```

## 📦 核心类

### Module — 模块管理

| 方法 | 签名 | 说明 |
|---|---|---|
| `newFn` | `(array $config, bool $debug=false): Func` | 创建函数 |
| `impFn` | `(string $modName, string $fnName, array $config, bool $debug=false): void` | 导入外部函数 |
| `commit` | `(Func $fn, bool $isExport=true): void` | 提交函数到模块 |
| `compile` | `(string $file): bool` | 编译为 `.wasm` 文件 |
| `enableDebug` | `(string $name): void` | 启用 Debug 模式 |
| `assignStart` | `(string $name): void` | 设置起始函数（类似 C main） |
| `assignMemory` | `(string $name, bool $exp, int $min, int $max): void` | 配置线性内存（每页 64KB） |
| `newGlobal` | `(string $name, bool $exp, ValType $vty, bool $mut, ConstExpression $init): int` | 创建全局变量，返回索引 |
| `newGlobaImp` | `(string $modName, string $globalName, ValType $vty, bool $mut): int` | 导入全局变量，返回索引 |
| `assignGlobalInit` | `(int $index, ConstExpression $init): void` | 设置全局变量初始化值 |
| `newDataSegment` | `(string $name, int $pos, string $data): int` | 创建数据段（指定位置） |
| `newPassiveDataSegment` | `(string $name, string $data): void` | 创建被动数据段 |

### Func — 函数体指令

| 类别 | 方法 | 说明 |
|---|---|---|
| **常量** | `const(int\|float)` | 压入 i32/f32 常量 |
| **局部变量** | `newLocal(ValType): int` / `newLocalNamed(ValType, string): int` | 创建局部变量 |
| | `getLocal(int)` / `setLocal(int)` / `teeLocal(int)` | 获取/设置/tee 局部变量 |
| **全局变量** | `getGlobal(int)` / `setGlobal(int)` | 获取/设置全局变量 |
| **算术** | `add` `sub` `mul` `div` `rem` `abs` `neg` | 加/减/乘/除/取余/绝对值/取反 |
| | `ceil` `floor` `trunc` `nearest` `sqrt` | 取整/平方根 |
| | `min` `max` `copysign` | 最值/符号复制 |
| **位运算** | `band` `bor` `bxor` `shl` `shr` `clz` `ctz` `popcnt` `rotl` `rotr` | 位操作 |
| **比较** | `eqz` `eq` `ne` `lt` `gt` `le` `ge` | 等于零/等于/不等于/小于/大于/小于等于/大于等于 |
| **类型转换** | `cast` `castTrapping` `reinterpret` | 类型转换/陷阱转换/位模式重解释 |
| | `signExtend8` `signExtend16` `signExtend32` | 符号扩展 |
| **控制流** | `block` `loop` `if_` `else_` `end` | 块/循环/条件分支 |
| | `br` `brIf` `return_` `select` `drop` | 跳转/返回/选择/丢弃 |
| | `unreachable` `nop` | 不可达/空操作 |
| **补丁** | `patchPos(): int` `patch(int $loc, int $begin)` | 补丁位置/设置补丁 |
| **函数调用** | `call(string)` `callImport(string, string)` | 调用内部/导入函数 |
| **内存** | `load` `load8` `load16` `load32I64` | 内存加载 |
| | `store` `store8` `store16` `store32I64` | 内存存储 |
| | `memorySize` `memoryGrow` `memoryInit` | 内存管理 |
| | `dataDrop` `memoryCopy` `memoryFill` | 数据段/复制/填充 |
| **引用** | `refNull` `refFunc` `refFuncImport` `refIsNull` | 引用操作 |

### 类型枚举

| 枚举 | 可选值 | 用途 |
|---|---|---|
| `ValType` | `I32`, `I64`, `F32`, `F64`, `V128`, `FuncRef`, `ExternRef` | 函数签名、局部变量声明 |
| `NumType` | `I32`, `I64`, `F32`, `F64` | 算术/位运算/内存指令 |
| `RefType` | `FuncRef`, `ExternRef` | 引用指令 |

### ConstExpression — 常量表达式

```php
new ConstExpression(100);                 // i32.const
new ConstExpression(3.14);                // f32.const
new ConstExpression(ValType::I32);        // i32.const 0
new ConstExpression(RefType::FuncRef);    // ref.null func
```

### FunType — 函数类型（Debug 模式）

```php
new FunType(
    [ValType::I32, ValType::I64],  // 参数类型
    [ValType::I32],                // 返回类型
    "add_type"                     // 类型名
);
```

## 📖 使用示例

### 条件分支 (if-else)

```php
$mod = new Module();
$fn = $mod->newFn([
    "name"    => "is_positive",
    "params"  => [ValType::I32],
    "results" => [ValType::I32],
]);

$label = $fn->if_([], [ValType::I32]);
$fn->const(1);                   // then: 返回 1
$fn->else_($label);
$fn->const(0);                   // else: 返回 0
$fn->end($label);

$mod->commit($fn);
$mod->compile("./is_positive.wasm");
```

### 循环 (loop)

```php
// sum(n) = 1 + 2 + ... + n
$fn = $mod->newFn([
    "name"    => "sum",
    "params"  => [ValType::I32],
    "results" => [ValType::I32],
]);

$i   = $fn->newLocal(ValType::I32);
$acc = $fn->newLocal(ValType::I32);
$fn->getLocal(0)->setLocal($i);
$fn->const(0)->setLocal($acc);

$loop = $fn->loop([], []);
$fn->getLocal($i)->const(0)->gt(NumType::I32, true);
$fn->brIf(1);  // i <= 0 则跳出

$fn->getLocal($acc)->getLocal($i)->add(NumType::I32)->setLocal($acc);
$fn->getLocal($i)->const(1)->sub(NumType::I32)->setLocal($i);
$fn->br(0);    // 继续循环
$fn->end($loop);
$fn->getLocal($acc);

$mod->commit($fn);
$mod->compile("./sum.wasm");
```

### 内存操作

```php
$mod = new Module();
$mod->assignMemory("mem", true, 1, 1);  // 1 页（64KB）

// store_value(addr, val)
$fn = $mod->newFn([
    "name"    => "store_value",
    "params"  => [ValType::I32, ValType::I32],
    "results" => [],
]);
$fn->getLocal(0)->getLocal(1)->store(NumType::I32, 2, 0);
$mod->commit($fn);

// load_value(addr) -> i32
$fn2 = $mod->newFn([
    "name"    => "load_value",
    "params"  => [ValType::I32],
    "results" => [ValType::I32],
]);
$fn2->getLocal(0)->load(NumType::I32, 2, 0);
$mod->commit($fn2);

$mod->compile("./memory.wasm");
```

### 函数导入与调用

```php
$mod = new Module();

// 导入外部函数 print_num(i32) -> ()
$mod->impFn("env", "print_num", [
    "params"  => [ValType::I32],
    "results" => [],
]);

// 创建函数调用导入
$fn = $mod->newFn([
    "name"    => "do_print",
    "params"  => [ValType::I32],
    "results" => [],
]);
$fn->getLocal(0)->callImport("env", "print_num");
$mod->commit($fn);

$mod->compile("./import.wasm");
```

### Debug 模式

```php
$mod = new Module();
$mod->enableDebug("my_module");

$fn = $mod->newFn([
    "name"        => "add",
    "params"      => [ValType::I32, ValType::I32],
    "results"     => [ValType::I32],
    "param_names" => ["a", "b"],       // Debug 必填
    "type_name"   => "add_type",       // Debug 必填
], true);

$fn->getLocal(0)->getLocal(1)->add(NumType::I32);
$mod->commit($fn);
$mod->compile("./add_debug.wasm");
```

### 全局变量

```php
use Kingbes\Wasm\ConstExpression;

$mod = new Module();

$counter = $mod->newGlobal("counter", true, ValType::I32, true, new ConstExpression(0));

$fn = $mod->newFn([
    "name"    => "increment",
    "params"  => [],
    "results" => [],
]);
$fn->getGlobal($counter)->const(1)->add(NumType::I32)->setGlobal($counter);
$mod->commit($fn);

$mod->compile("./counter.wasm");
```

### 数据段

```php
$mod = new Module();
$mod->assignMemory("mem", true, 1, 1);
$mod->newDataSegment("hello", 0, "Hello, World!");

$fn = $mod->newFn([
    "name"    => "get_byte",
    "params"  => [ValType::I32],
    "results" => [ValType::I32],
]);
$fn->getLocal(0)->load8(NumType::I32, false, 0, 0);  // i32.load8_u
$mod->commit($fn);

$mod->compile("./data.wasm");
```

## 📚 详细文档

| 文档 | 说明 |
|---|---|
| [Module 类](doc/module.md) | 模块创建、函数管理、全局变量、内存、数据段、编译 |
| [Func 类](doc/func.md) | 函数体指令：常量、局部/全局变量、算术、位运算、比较、类型转换、控制流、内存、引用 |
| [类型枚举](doc/types.md) | ValType、NumType、RefType 枚举说明 |
| [ConstExpression](doc/const-expression.md) | 常量表达式与全局变量初始化 |
| [使用示例](doc/examples.md) | 10 个完整示例覆盖常见场景 |

## 📄 License

[MIT](LICENSE)
