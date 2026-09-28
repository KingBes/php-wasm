# Module 类

`Module` 是 Wasm 模块的核心管理类，负责模块的创建、函数/全局变量/内存/数据段的管理以及编译输出。

> 命名空间：`Kingbes\Wasm\Module`

## 目录

- [创建模块](#创建模块)
- [函数管理](#函数管理)
- [全局变量](#全局变量)
- [内存管理](#内存管理)
- [表管理](#表管理)
- [数据段](#数据段)
- [调试](#调试)
- [编译](#编译)

---

## 创建模块

```php
$mod = new Module();
```

## 函数管理

### newFn — 创建函数

```php
public function newFn(array $config, bool $debug = false): Func
```

创建一个 Wasm 函数对象，返回 `Func` 实例。`$config` 结构如下：

```php
$config = [
    "name"    => "add",                        // 函数名（必填）
    "params"  => [ValType::I32, ValType::I32], // 参数类型数组（必填）
    "results" => [ValType::I32],               // 返回值类型数组（必填）
    // 以下为 debug 模式必填
    "param_names" => ["a", "b"],               // 参数名数组，与 params 顺序一致
    "type_name"    => "add_type",              // 类型名称
];
$fn = $mod->newFn($config);
```

> **提示**：函数体指令编写参见 [Func 类文档](func.md)。

### impFn — 导入外部函数

```php
public function impFn(
    string $mod_name,
    string $fn_name,
    array $config,
    bool $debug = false
): void
```

从外部模块导入函数。`$config` 结构同 `newFn`，但不需要 `name` 字段：

```php
$mod->impFn("env", "log", [
    "params"  => [ValType::I32],
    "results" => [],
]);
```

### newFnType — 创建函数类型

```php
public function newFnType(array $params, array $results, ?string $type_name = null): int
```

创建函数类型并返回其类型索引。相同签名的类型会被复用（驻留），返回的索引可用于 `Func::callIndirect()`。

| 参数 | 说明 |
|---|---|
| `$params` | 参数类型数组 |
| `$results` | 返回值类型数组 |
| `$type_name` | 类型名称（仅调试信息使用，可省略） |

```php
$typeIdx = $mod->newFnType([ValType::I32, ValType::I32], [ValType::I32]);
```

### commit — 提交函数

```php
public function commit(Func $fn, bool $is_export = true): void
```

将函数提交到模块中。`$is_export` 控制是否导出该函数（默认 `true`）。

```php
$mod->commit($fn);            // 导出
$mod->commit($fn, false);     // 不导出（内部函数）
```

### assignStart — 设置起始函数

```php
public function assignStart(string $name): void
```

设置模块的起始函数（类似 C 的 `main` 函数），模块加载时自动执行。

```php
$mod->assignStart("_start");
```

> **注意**：指定的函数必须已通过 `commit()` 提交到模块中。

## 全局变量

### newGlobal — 创建全局变量

```php
public function newGlobal(
    string $name,
    bool $exp,
    ValType $vty,
    bool $mut,
    ConstExpression $init
): int
```

| 参数 | 说明 |
|---|---|
| `$name` | 变量名称 |
| `$exp` | 是否导出 |
| `$vty` | 变量类型 |
| `$mut` | 是否可变（`false` 为不可变常量） |
| `$init` | 初始化表达式（参见 [ConstExpression](const-expression.md)） |

返回全局变量索引，该索引用于 `Func::getGlobal()` / `Func::setGlobal()` 操作。

```php
use Kingbes\Wasm\ConstExpression;

$idx = $mod->newGlobal("counter", true, ValType::I32, true, new ConstExpression(0));
```

### newGlobaImp — 导入全局变量

```php
public function newGlobaImp(
    string $mod_name,
    string $global_name,
    ValType $vty,
    bool $mut
): int
```

从外部模块（如 `env`）导入全局变量，返回变量索引。

```php
$idx = $mod->newGlobaImp("env", "stack_ptr", ValType::I32, true);
```

### assignGlobalInit — 重设全局变量初始值

```php
public function assignGlobalInit(int $index, ConstExpression $init): void
```

为已通过 `newGlobal()` 创建的本地全局变量重新设置初始化表达式（`$index` 即 `newGlobal()` 返回的索引）。

```php
$idx = $mod->newGlobal("max_size", true, ValType::I32, false, new ConstExpression(0));
$mod->assignGlobalInit($idx, new ConstExpression(256)); // 初始值改为 256
```

## 内存管理

### assignMemory — 配置线性内存

```php
public function assignMemory(
    string $name,
    bool $exp,
    int $min,
    int $max
): void
```

| 参数 | 说明 |
|---|---|
| `$name` | 内存名称 |
| `$exp` | 是否导出 |
| `$min` | 最小页数（每页 64KB） |
| `$max` | 最大页数，`0` 表示无上限 |

```php
$mod->assignMemory("mem", true, 1, 10);
```

## 表管理

### assignTable — 声明函数表

```php
public function assignTable(string $name, bool $exp, RefType $reftype, int $min, int $max): int
```

声明一个表并返回其索引。

| 参数 | 说明 |
|---|---|
| `$name` | 表名称 |
| `$exp` | 是否导出 |
| `$reftype` | 元素类型（`RefType::FuncRef` / `RefType::ExternRef`） |
| `$min` | 最小元素数量 |
| `$max` | 最大元素数量，`0` 表示无上限 |

```php
use Kingbes\Wasm\RefType;

$tableIdx = $mod->assignTable("tbl", true, RefType::FuncRef, 2, 0);
```

### newActiveElement — 创建主动元素段

```php
public function newActiveElement(int $tableidx, int $offset, array $funcs): int
```

在表的指定位置填入函数引用，模块加载时自动写入。

```php
$mod->newActiveElement(0, 0, ["add", "mul"]);
```

### newDeclarativeElement — 创建声明式元素段

```php
public function newDeclarativeElement(array $funcs): int
```

声明函数引用但不占用表空间（用于满足 `ref.func` 的声明要求）。

```php
$mod->newDeclarativeElement(["add"]);
```

## 数据段

### newDataSegment — 创建数据段

```php
public function newDataSegment(string $name, int $pos, string $data): int
```

在内存指定位置创建主动数据段，模块加载时自动写入内存。

```php
$mod->newDataSegment("msg", 0, "Hello, Wasm!");
```

### newPassiveDataSegment — 创建被动数据段

```php
public function newPassiveDataSegment(string $name, string $data): void
```

创建被动数据段。不会自动写入内存，需通过 `Func::memoryInit()` 指令手动加载。

```php
$mod->newPassiveDataSegment("lazy_data", "some bytes");
```

> **配合使用**：在函数体中调用 `$fn->memoryInit($dataIdx)` 和 `$fn->dataDrop($dataIdx)` 来加载和释放被动数据段。

## 调试

### enableDebug — 启用调试模式

```php
public function enableDebug(string $name): void
```

为模块启用调试信息输出（编译时生成 `name` 自定义段）。需在 `compile()` 之前调用，并与 `newFn()` / `impFn()` 的 `$debug` 参数配合使用。

```php
$mod->enableDebug("my_module");

// 创建 debug 函数：传 $debug=true 并提供 param_names 和 type_name
$fn = $mod->newFn([
    "name"        => "add",
    "params"      => [ValType::I32, ValType::I32],
    "results"     => [ValType::I32],
    "param_names" => ["a", "b"],
    "type_name"   => "add_type",
], true);

$fn->getLocal(0)->getLocal(1)->add(NumType::I32);
$mod->commit($fn);
$mod->compile("./add_debug.wasm");
```

## 编译

### compile — 编译为 Wasm 文件

```php
public function compile(string $file): bool
```

将所有提交的内容编译为 `.wasm` 二进制文件。返回 `true` 成功，`false` 失败。

```php
$result = $mod->compile("./output.wasm");
if ($result) {
    echo "编译成功！";
}
```

### toBytes — 编译为二进制字节

```php
public function toBytes(): string
```

返回编译后的 Wasm 二进制字节，便于直接写入自定义目标或做进一步处理。

```php
$bytes = $mod->toBytes();
file_put_contents("./output.wasm", $bytes);
```
