# Module 类

`Module` 是 Wasm 模块的核心管理类，负责模块的创建、函数/全局变量/内存/数据段的管理以及编译输出。

> 命名空间：`Kingbes\Wasm\Module`

## 创建模块

```php
$mod = new Module();
```

## 函数管理

### newFn — 创建函数

```php
public function newFn(array $config, bool $debug = false): Func
```

创建一个 Wasm 函数对象。`$config` 结构如下：

```php
$config = [
    "name"    => "add",                        // 函数名（必填）
    "params"  => [ValType::I32, ValType::I32], // 参数类型数组（必填）
    "results" => [ValType::I32],               // 返回值类型数组（必填）
    // 以下为 debug 模式必填
    "param_names" => ["a", "b"],              // 参数名数组
    "type_name"    => "add_type",              // 类型名称
];
$fn = $mod->newFn($config, false);
```

### impFn — 导入函数

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

### commit — 提交函数

```php
public function commit(Func $fn, bool $is_export = true): void
```

将函数提交到模块中。`$is_export` 控制是否导出该函数。

```php
$mod->commit($fn, true);
```

### assignStart — 设置起始函数

```php
public function assignStart(string $name): void
```

设置模块的起始函数（类似 C 的 `main` 函数）。

```php
$mod->assignStart("_start");
```

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
| `$mut` | 是否可变 |
| `$init` | 初始化表达式 |

返回全局变量索引。

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

从外部模块导入全局变量，返回变量索引。

```php
$idx = $mod->newGlobaImp("env", "stack_ptr", ValType::I32, true);
```

### assignGlobalInit — 设置全局变量初始值

```php
public function assignGlobalInit(int $index, ConstExpression $init): void
```

为之前导入的全局变量设置初始化表达式。

```php
$mod->assignGlobalInit(0, new ConstExpression(100));
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
| `$max` | 最大页数 |

```php
$mod->assignMemory("mem", true, 1, 10);
```

## 数据段

### newDataSegment — 创建数据段

```php
public function newDataSegment(string $name, int $pos, string $data): int
```

在内存指定位置创建数据段。

```php
$mod->newDataSegment("msg", 0, "Hello, Wasm!");
```

### newPassiveDataSegment — 创建被动数据段

```php
public function newPassiveDataSegment(string $name, string $data): void
```

创建被动数据段（需通过 `memory.init` 指令手动加载到内存）。

```php
$mod->newPassiveDataSegment("lazy_data", "some bytes");
```

## 调试

### enableDebug — 启用调试模式

```php
public function enableDebug(string $name): void
```

为模块启用调试信息输出。

```php
$mod->enableDebug("my_module");
```

## 编译

### compile — 编译为 Wasm 文件

```php
public function compile(string $file): bool
```

将模块编译为 `.wasm` 二进制文件，返回 `true` 成功 / `false` 失败。

```php
$result = $mod->compile("./output.wasm");
```
