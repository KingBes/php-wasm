# Func 类

`Func` 类用于编写 Wasm 函数体中的指令序列，支持链式调用。

> 命名空间：`Kingbes\Wasm\Func`

## 创建函数

通常通过 `Module::newFn()` 创建，也可直接构造：

```php
$fn = new Func($mod, [
    "name"    => "add",
    "params"  => [ValType::I32, ValType::I32],
    "results" => [ValType::I32],
]);
```

Debug 模式：

```php
$fn = new Func($mod, [
    "name"       => "add",
    "params"     => [ValType::I32, ValType::I32],
    "results"    => [ValType::I32],
    "param_names"=> ["a", "b"],
    "type_name"  => "add_type",
], true);
```

---

## 常量指令

### const — 压入常量

```php
public function const(int|float $val): self
```

- 传入 `int` 时生成 `i32.const`
- 传入 `float` 时生成 `f32.const`

```php
$fn->const(42)->const(3.14);
```

---

## 局部变量

| 方法 | 签名 | 说明 |
|---|---|---|
| `newLocal` | `(ValType $vty): int` | 创建局部变量，返回索引 |
| `newLocalNamed` | `(ValType $vty, string $name): int` | 创建带名称的局部变量 |
| `getLocal` | `(int $index): self` | 获取局部变量值压入栈 |
| `setLocal` | `(int $index): self` | 将栈顶值设置到局部变量 |
| `teeLocal` | `(int $index): self` | 类似 setLocal 但保留栈顶值 |

```php
$localIdx = $fn->newLocal(ValType::I32);
$fn->const(10)->setLocal($localIdx);
$fn->getLocal($localIdx)->teeLocal($localIdx);
```

---

## 全局变量

| 方法 | 签名 | 说明 |
|---|---|---|
| `getGlobal` | `(int $index): self` | 获取全局变量值压入栈 |
| `setGlobal` | `(int $index): self` | 将栈顶值设置到全局变量 |

```php
$fn->getGlobal(0)->const(1)->add(NumType::I32)->setGlobal(0);
```

---

## 算术运算

所有算术方法接受 `NumType` 参数，返回 `self` 支持链式调用。

| 方法 | 说明 |
|---|---|
| `add(NumType $typ)` | 加法 |
| `sub(NumType $typ)` | 减法 |
| `mul(NumType $typ)` | 乘法 |
| `div(NumType $typ, bool $signed = false)` | 除法 |
| `rem(NumType $typ, bool $signed = false)` | 取余 |
| `abs(NumType $typ)` | 绝对值 |
| `neg(NumType $typ)` | 取反 |
| `ceil(NumType $typ)` | 向上取整 |
| `floor(NumType $typ)` | 向下取整 |
| `trunc(NumType $typ)` | 截断取整 |
| `nearest(NumType $typ)` | 就近取整 |
| `sqrt(NumType $typ)` | 平方根 |
| `min(NumType $typ)` | 最小值 |
| `max(NumType $typ)` | 最大值 |
| `copysign(NumType $typ)` | 复制符号位 |

```php
$fn->getLocal(0)->getLocal(1)->add(NumType::I32);
$fn->getLocal(0)->div(NumType::I32, true); // 有符号除法
```

---

## 位运算

| 方法 | 说明 |
|---|---|
| `band(NumType $typ)` | 按位与 |
| `bor(NumType $typ)` | 按位或 |
| `bxor(NumType $typ)` | 按位异或 |
| `shl(NumType $typ)` | 左移 |
| `shr(NumType $typ, bool $signed = false)` | 右移 |
| `clz(NumType $typ)` | 前导零计数 |
| `ctz(NumType $typ)` | 后导零计数 |
| `popcnt(NumType $typ)` | 置位计数 |
| `rotl(NumType $typ)` | 循环左移 |
| `rotr(NumType $typ)` | 循环右移 |

```php
$fn->getLocal(0)->getLocal(1)->band(NumType::I32);
$fn->const(1)->getLocal(0)->shl(NumType::I32);
```

---

## 比较运算

| 方法 | 说明 |
|---|---|
| `eqz(NumType $typ)` | 等于零 |
| `eq(NumType $typ)` | 等于 |
| `ne(NumType $typ)` | 不等于 |
| `lt(NumType $typ, bool $signed = false)` | 小于 |
| `gt(NumType $typ, bool $signed = false)` | 大于 |
| `le(NumType $typ, bool $signed = false)` | 小于等于 |
| `ge(NumType $typ, bool $signed = false)` | 大于等于 |

```php
$fn->getLocal(0)->const(0)->gt(NumType::I32, true); // 有符号比较
```

---

## 类型转换

| 方法 | 说明 |
|---|---|
| `cast(NumType $from, bool $signed, NumType $to)` | 类型转换 |
| `castTrapping(NumType $from, bool $signed, NumType $to)` | 陷阱类型转换 |
| `reinterpret(NumType $typ)` | 重新解释类型（位模式不变） |
| `signExtend8(ValType $typ)` | 符号扩展 8 位 |
| `signExtend16(ValType $typ)` | 符号扩展 16 位 |
| `signExtend32()` | 符号扩展 32 位（i64 专用） |

```php
$fn->getLocal(0)->cast(NumType::I32, true, NumType::I64);  // i32 -> i64
$fn->getLocal(0)->reinterpret(NumType::F32);                // f32.reinterpret_i32
```

---

## 控制流

| 方法 | 签名 | 说明 |
|---|---|---|
| `block` | `(array $params, array $results): int` | 创建 block 块，返回标签 |
| `loop` | `(array $params, array $results): int` | 创建 loop 块，返回标签 |
| `if_` | `(array $params, array $results): int` | 创建 if 块，返回标签 |
| `else_` | `(int $label): self` | else 分支 |
| `end` | `(int $label): self` | 结束块 |
| `br` | `(int $label): self` | 无条件跳转 |
| `brIf` | `(int $label): self` | 条件跳转 |
| `return_` | `(): self` | 返回 |
| `select` | `(): self` | select 指令 |
| `drop` | `(): self` | 丢弃栈顶值 |
| `unreachable` | `(): self` | 不可达指令 |
| `nop` | `(): self` | 空操作 |

```php
// if-else 示例
$label = $fn->if_([], [ValType::I32]);
$fn->const(1);
$fn->else_($label);
$fn->const(0);
$fn->end($label);

// loop 示例
$label = $fn->loop([], []);
$fn->br(0); // 跳回 loop 起始
$fn->end($label);
```

### 补丁与导出

| 方法 | 签名 | 说明 |
|---|---|---|
| `patchPos` | `(): int` | 获取当前补丁位置 |
| `patch` | `(int $loc, int $begin): self` | 设置补丁 |
| `exportName` | `(string $name): self` | 设置导出名称 |

---

## 函数调用

| 方法 | 签名 | 说明 |
|---|---|---|
| `call` | `(string $name): self` | 调用内部函数 |
| `callImport` | `(string $mod_name, string $fn_name): self` | 调用导入函数 |

```php
$fn->call("helper_fn");
$fn->callImport("env", "log");
```

---

## 内存操作

| 方法 | 签名 | 说明 |
|---|---|---|
| `load` | `(NumType $typ, int $align, int $offset): self` | 内存加载 |
| `load8` | `(NumType $typ, bool $signed, int $align, int $offset): self` | 8 位加载 |
| `load16` | `(NumType $typ, bool $signed, int $align, int $offset): self` | 16 位加载 |
| `load32I64` | `(bool $signed, int $align, int $offset): self` | 32 位加载（i64 专用） |
| `store` | `(NumType $typ, int $align, int $offset): self` | 内存存储 |
| `store8` | `(NumType $typ, int $align, int $offset): self` | 8 位存储 |
| `store16` | `(NumType $typ, int $align, int $offset): self` | 16 位存储 |
| `store32I64` | `(int $align, int $offset): self` | 32 位存储（i64 专用） |
| `memorySize` | `(): self` | 获取内存页数 |
| `memoryGrow` | `(): self` | 增长内存 |
| `memoryInit` | `(int $idx): self` | 初始化内存（从数据段） |
| `dataDrop` | `(int $idx): self` | 丢弃数据段 |
| `memoryCopy` | `(): self` | 内存复制 |
| `memoryFill` | `(): self` | 内存填充 |

```php
// 存储值到内存偏移 0
$fn->const(0)->const(42)->store(NumType::I32, 2, 0);
// 从内存偏移 0 加载
$fn->const(0)->load(NumType::I32, 2, 0);
```

---

## 引用操作

| 方法 | 签名 | 说明 |
|---|---|---|
| `refNull` | `(RefType $rt): self` | 创建空引用 |
| `refFunc` | `(string $name): self` | 创建函数引用 |
| `refFuncImport` | `(string $mod_name, string $fn_name): self` | 创建导入函数引用 |
| `refIsNull` | `(RefType $rt): self` | 判断引用是否为空 |

```php
$fn->refNull(RefType::FuncRef);
$fn->refFunc("my_func");
$fn->refIsNull(RefType::ExternRef);
```
