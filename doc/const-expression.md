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
| `$data` | `ConstExprState` | 表达式内部状态（供 `Module` 使用，通常无需直接操作） |

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

用于重设本地全局变量的初始化表达式（`$index` 为 `Module::newGlobal()` 返回的索引）：

```php
$idx = $mod->newGlobal("max_size", true, ValType::I32, false, new ConstExpression(0));
$mod->assignGlobalInit($idx, new ConstExpression(256));
```

## 静态工厂方法

除构造函数外，还提供以下静态工厂方法构造特定类型的常量表达式：

| 方法 | 签名 | 生成的 Wasm 指令 |
|---|---|---|
| `i64` | `(int $val): self` | `i64.const` |
| `f64` | `(float $val): self` | `f64.const` |
| `globalGet` | `(int $index): self` | `global.get`（引用导入的全局变量） |
| `refFunc` | `(string $name): self` | `ref.func` |
| `refFuncImport` | `(string $mod_name, string $fn_name): self` | `ref.func`（引用导入函数） |

```php
use Kingbes\Wasm\ConstExpression;

$e1 = ConstExpression::i64(100);                 // i64.const 100
$e2 = ConstExpression::f64(1.5);                 // f64.const 1.5
$e3 = ConstExpression::globalGet(0);             // global.get 0
$e4 = ConstExpression::refFunc("add");           // ref.func add
$e5 = ConstExpression::refFuncImport("env", "log");
```

## 表达式运算

可在已有表达式上追加常量与整数运算，构成复合常量表达式：

| 方法 | 签名 | 说明 |
|---|---|---|
| `i32Const` | `(int $val): self` | 追加 `i32.const` |
| `i64Const` | `(int $val): self` | 追加 `i64.const` |
| `f32Const` | `(float $val): self` | 追加 `f32.const` |
| `f64Const` | `(float $val): self` | 追加 `f64.const` |
| `add` | `(NumType $typ): self` | 加法（仅 `i32` / `i64`） |
| `sub` | `(NumType $typ): self` | 减法（仅 `i32` / `i64`） |
| `mul` | `(NumType $typ): self` | 乘法（仅 `i32` / `i64`） |

```php
use Kingbes\Wasm\ConstExpression;
use Kingbes\Wasm\NumType;

// (2 + 3) * 4
$expr = (new ConstExpression(2))
    ->i32Const(3)->add(NumType::I32)
    ->i32Const(4)->mul(NumType::I32);
```

> **说明**：`add` / `sub` / `mul` 传入 `F32` / `F64` 会抛出 `InvalidArgumentException`，因为 Wasm 常量表达式不允许浮点运算；`f32Const` / `f64Const` 仅用于追加浮点常量，不能参与算术。
