# ConstExpression — 常量表达式

常量表达式用于全局变量的初始化，是 Wasm 规范中 `constexpr` 的 PHP 封装。

> 命名空间：`Kingbes\Wasm\ConstExpression`

## 构造函数

```php
public function __construct(int|float|ValType|RefType $val)
```

根据参数类型自动选择合适的 Wasm 常量表达式：

| 参数类型 | 生成的 Wasm 指令 | 说明 |
|---|---|---|
| `int` | `i32.const` | 32 位整数常量 |
| `float` | `f32.const` | 32 位浮点常量 |
| `ValType` | 对应类型的 `.const 0` | 零值初始化 |
| `RefType` | `ref.null` | 空引用 |

## 属性

| 属性 | 类型 | 说明 |
|---|---|---|
| `$data` | `CData` | 底层 C 数据指针 |

## 使用示例

### 整数初始化

```php
use Kingbes\Wasm\ConstExpression;

$expr = new ConstExpression(42);   // i32.const 42
$expr = new ConstExpression(0);    // i32.const 0
```

### 浮点数初始化

```php
$expr = new ConstExpression(3.14); // f32.const 3.14
```

### 零值初始化

```php
use Kingbes\Wasm\ValType;

$expr = new ConstExpression(ValType::I32); // i32.const 0
$expr = new ConstExpression(ValType::F64); // f64.const 0.0
```

### 空引用初始化

```php
use Kingbes\Wasm\RefType;

$expr = new ConstExpression(RefType::FuncRef);   // ref.null func
$expr = new ConstExpression(RefType::ExternRef);  // ref.null extern
```

## 与 Module::newGlobal 配合使用

```php
use Kingbes\Wasm\Module;
use Kingbes\Wasm\ValType;
use Kingbes\Wasm\ConstExpression;

$mod = new Module();

// 不可变全局变量，初始值 100
$idx1 = $mod->newGlobal("max_size", true, ValType::I32, false, new ConstExpression(100));

// 可变全局变量，初始值 0
$idx2 = $mod->newGlobal("counter", true, ValType::I32, true, new ConstExpression(0));

// 可变全局变量，浮点零值
$idx3 = $mod->newGlobal("pi_approx", true, ValType::F64, true, new ConstExpression(ValType::F64));
```

## 与 Module::assignGlobalInit 配合使用

用于为导入的全局变量设置初始化表达式：

```php
$mod->assignGlobalInit(0, new ConstExpression(256));
```

## 底层 C 函数映射

| PHP 参数类型 | 调用的 C 函数 |
|---|---|
| `int` | `constexpr_value_i32(val)` |
| `float` | `constexpr_value_f32(val)` |
| `ValType` | `constexpr_value_zero(vty)` |
| `RefType` | `constexpr_ref_null(rt)` |

此外，C 层还提供了以下函数（可按需扩展封装）：

| C 函数 | 说明 |
|---|---|
| `constexpr_value_i64(val)` | i64 常量表达式 |
| `constexpr_value_f64(val)` | f64 常量表达式 |
