# php-wasm

通过 PHP FFI 调用 C 动态库，以编程方式构建 WebAssembly (Wasm) 模块的 PHP 库。

## 特性

- 纯 PHP 代码生成 Wasm 二进制模块
- 支持完整的 Wasm 指令集（算术、位运算、比较、控制流、内存、引用等）
- 支持 debug 模式，生成带调试信息的 Wasm 模块
- 链式调用 API 设计，代码更简洁
- 支持函数导入/导出、全局变量、内存管理、数据段等

## 环境要求

- PHP >= 8.2
- 启用 FFI 扩展 (`ext-ffi`)
- Windows Linux Macos

## 安装

```bash
composer require kingbes/wasm
```

## 快速开始

```php
<?php

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
$mod->compile("./add.wasm");
```

## 核心类

| 类 | 说明 |
|---|---|
| `Module` | Wasm 模块管理：创建函数、全局变量、内存、数据段、编译输出 |
| `Func` | Wasm 函数体编写：常量、变量、算术、位运算、比较、控制流、内存、引用等指令 |
| `ValType` | 值类型枚举：I32, I64, F32, F64, V128, FuncRef, ExternRef |
| `NumType` | 数值类型枚举：I32, I64, F32, F64 |
| `RefType` | 引用类型枚举：FuncRef, ExternRef |
| `ConstExpression` | 常量表达式，用于全局变量初始化 |
| `FunType` | 函数类型对象，用于 debug 模式 |

## 详细文档

| 文档 | 说明 |
|---|---|
| [Module 类](doc/module.md) | 模块创建、函数管理、全局变量、内存、数据段、编译 |
| [Func 类](doc/func.md) | 函数体指令：常量、局部/全局变量、算术、位运算、比较、类型转换、控制流、内存、引用 |
| [类型枚举](doc/types.md) | ValType、NumType、RefType 枚举说明 |
| [ConstExpression](doc/const-expression.md) | 常量表达式与全局变量初始化 |
| [使用示例](doc/examples.md) | 完整使用示例：加法函数、循环、条件分支、内存操作等 |

## 项目结构

```
php-wasm/
├── src/                    # PHP 源码
│   ├── Base.php            # 基类（FFI 加载、数组辅助方法）
│   ├── Module.php          # 模块类
│   ├── Func.php            # 函数类
│   ├── ValType.php         # 值类型枚举
│   ├── NumType.php         # 数值类型枚举
│   ├── RefType.php         # 引用类型枚举
│   ├── ConstExpression.php # 常量表达式类
│   ├── FunType.php         # 函数类型类
│   └── Wasm.h              # C 头文件（FFI 定义）
├── core/                   # C/V 源码
├── lib/windows/            # 动态链接库
│   └── wasm.dll
├── test/                   # 测试
├── doc/                    # 文档
├── composer.json
└── LICENSE                 # MIT 许可证
```

## License

[MIT](LICENSE)
