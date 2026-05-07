# 类型枚举

php-wasm 提供三个枚举类型，用于定义 Wasm 值的类型。

---

## ValType — 值类型

> 命名空间：`Kingbes\Wasm\ValType`

对应 Wasm 规范中的 valtype，用于函数参数和返回值的类型声明。

| 枚举值 | Wasm 类型 | 说明 |
|---|---|---|
| `ValType::I32` | `i32` | 32 位整数 |
| `ValType::I64` | `i64` | 64 位整数 |
| `ValType::F32` | `f32` | 32 位浮点数 |
| `ValType::F64` | `f64` | 64 位浮点数 |
| `ValType::V128` | `v128` | 128 位 SIMD 向量 |
| `ValType::FuncRef` | `funcref` | 函数引用 |
| `ValType::ExternRef` | `externref` | 外部引用 |

### data() 方法

```php
public function data(): int
```

返回该枚举对应的 C 层整数值。

```php
$val = ValType::I32;
$cdata = $val->data(); // 返回 FFI 编码的值
```

### 使用场景

- `Module::newFn()` 的 `params` 和 `results` 数组
- `Func::newLocal()` 创建局部变量
- `Func::block()` / `Func::loop()` / `Func::if_()` 的类型签名
- `Func::signExtend8()` / `Func::signExtend16()` 的类型参数

```php
use Kingbes\Wasm\ValType;

$fn = $mod->newFn("add", [ValType::I32, ValType::I32], [ValType::I32]);
$localIdx = $fn->newLocal(ValType::F64);
```

---

## NumType — 数值类型

> 命名空间：`Kingbes\Wasm\NumType`

对应 Wasm 规范中的 numtype，用于算术、位运算、比较等数值指令的类型标识。

| 枚举值 | Wasm 类型 | 说明 |
|---|---|---|
| `NumType::I32` | `i32` | 32 位整数 |
| `NumType::I64` | `i64` | 64 位整数 |
| `NumType::F32` | `f32` | 32 位浮点数 |
| `NumType::F64` | `f64` | 64 位浮点数 |

### data() 方法

```php
public function data(): int
```

### 使用场景

- `Func` 的算术运算：`add()`、`sub()`、`mul()`、`div()` 等
- `Func` 的位运算：`band()`、`bor()`、`shl()` 等
- `Func` 的比较运算：`eq()`、`lt()`、`gt()` 等
- `Func` 的内存操作：`load()`、`store()` 等
- `Func` 的类型转换：`cast()`、`reinterpret()` 等

```php
use Kingbes\Wasm\NumType;

$fn->getLocal(0)->getLocal(1)->add(NumType::I32);
$fn->getLocal(0)->sqrt(NumType::F64);
$fn->const(0)->load(NumType::I32, 2, 0);
```

---

## RefType — 引用类型

> 命名空间：`Kingbes\Wasm\RefType`

对应 Wasm 规范中的 reftype，用于引用相关指令。

| 枚举值 | Wasm 类型 | 说明 |
|---|---|---|
| `RefType::FuncRef` | `funcref` | 函数引用 |
| `RefType::ExternRef` | `externref` | 外部引用 |

### data() 方法

```php
public function data(): int
```

### 使用场景

- `Func::refNull()` 创建空引用
- `Func::refIsNull()` 判断引用是否为空
- `ConstExpression` 构造 `ref.null` 常量

```php
use Kingbes\Wasm\RefType;

$fn->refNull(RefType::FuncRef);
$fn->refIsNull(RefType::ExternRef);
$expr = new ConstExpression(RefType::FuncRef);
```

---

## ValType 与 NumType 的区别

| 特性 | ValType | NumType |
|---|---|---|
| 覆盖范围 | 所有值类型（含引用和 SIMD） | 仅数值类型 |
| 典型用途 | 函数签名、局部变量、块类型 | 算术/位运算/比较/内存指令 |
| 枚举值数量 | 7 个 | 4 个 |

简单来说，`ValType` 用于声明"这个位置是什么类型"，`NumType` 用于指定"这条数值指令作用于什么类型"。
