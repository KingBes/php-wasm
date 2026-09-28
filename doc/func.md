# Func 类

`Func` 类用于编写 Wasm 函数体中的指令序列，所有指令方法返回 `self` 以支持链式调用。

> 命名空间：`Kingbes\Wasm\Func`

## 目录

- [创建函数](#创建函数)
- [常量指令](#常量指令)
- [局部变量](#局部变量)
- [全局变量](#全局变量)
- [算术运算](#算术运算)
- [位运算](#位运算)
- [比较运算](#比较运算)
- [类型转换](#类型转换)
- [控制流](#控制流)
- [函数调用](#函数调用)
- [表操作](#表操作)
- [内存操作](#内存操作)
- [引用操作](#引用操作)

---

## 创建函数

通常通过 `Module::newFn()` 创建，也可直接构造：

```php
$fn = new Func($mod, [
    "name"    => "add",
    "params"  => [ValType::I32, ValType::I32],
    "results" => [ValType::I32],
]);
```

Debug 模式（需配合 `Module::enableDebug()`）：

```php
$fn = new Func($mod, [
    "name"       => "add",
    "params"     => [ValType::I32, ValType::I32],
    "results"    => [ValType::I32],
    "param_names"=> ["a", "b"],
    "type_name"  => "add_type",
], true);
```

> **典型工作流**：创建 `Func` → 链式编写指令 → `Module::commit()` 提交 → `Module::compile()` 输出。

---

## 常量指令

### const — 压入常量

```php
public function const(int|float $val): self
```

| 参数类型 | 生成指令 | 示例 |
|---|---|---|
| `int` | `i32.const` | `$fn->const(42);` |
| `float` | `f32.const` | `$fn->const(3.14);` |

```php
$fn->const(42)->const(3.14);
```

### constI64 / constF64 — 压入 64 位常量

```php
public function constI64(int $val): self
public function constF64(float $val): self
```

| 方法 | 生成指令 | 示例 |
|---|---|---|
| `constI64` | `i64.const` | `$fn->constI64(10000000000);` |
| `constF64` | `f64.const` | `$fn->constF64(3.141592653589793);` |

```php
$fn->constI64(100)->constI64(200)->add(NumType::I64);
```

---

## 局部变量

| 方法 | 签名 | Wasm 指令 | 说明 |
|---|---|---|---|
| `newLocal` | `(ValType $vty): int` | — | 声明局部变量，返回索引 |
| `newLocalNamed` | `(ValType $vty, string $name): int` | — | 声明带名称的局部变量（debug模式） |
| `getLocal` | `(int $index): self` | `local.get` | 将变量值压入栈 |
| `setLocal` | `(int $index): self` | `local.set` | 弹出栈顶值赋给变量 |
| `teeLocal` | `(int $index): self` | `local.tee` | 同 setLocal，但栈顶值保留 |

```php
$localIdx = $fn->newLocal(ValType::I32);
$fn->const(10)->setLocal($localIdx);           // i = 10
$fn->getLocal($localIdx)->teeLocal($localIdx); // 读取 i 同时保留栈顶
```

> **注意**：函数参数也占用局部变量索引。第一个参数索引为 `0`，第二个为 `1`，依此类推。因此 `newLocal()` 返回的索引从参数数量之后开始。

---

## 全局变量

| 方法 | 签名 | Wasm 指令 | 说明 |
|---|---|---|---|
| `getGlobal` | `(int $index): self` | `global.get` | 获取本地全局变量值压入栈 |
| `setGlobal` | `(int $index): self` | `global.set` | 弹出栈顶值赋给本地全局变量 |
| `getGlobalImport` | `(int $index): self` | `global.get` | 获取导入的全局变量值压入栈 |
| `setGlobalImport` | `(int $index): self` | `global.set` | 弹出栈顶值赋给导入的全局变量 |

全局变量索引由 `Module::newGlobal()` 或 `Module::newGlobaImp()` 返回。本地全局变量与导入全局变量分别使用各自的方法。

```php
$fn->getGlobal(0)->const(1)->add(NumType::I32)->setGlobal(0);
$fn->getGlobalImport(0)->setGlobalImport(1);
```

---

## 算术运算

所有算术方法接受 `NumType` 参数，返回 `self` 支持链式调用。

| 方法 | Wasm 指令 | 说明 |
|---|---|---|
| `add(NumType $typ)` | `xx.add` | 加法 |
| `sub(NumType $typ)` | `xx.sub` | 减法 |
| `mul(NumType $typ)` | `xx.mul` | 乘法 |
| `div(NumType $typ, bool $signed = false)` | `xx.div_s` / `xx.div_u` | 除法 |
| `rem(NumType $typ, bool $signed = false)` | `xx.rem_s` / `xx.rem_u` | 取余 |
| `abs(NumType $typ)` | `xx.abs` | 绝对值 |
| `neg(NumType $typ)` | `xx.neg` | 取反 |
| `ceil(NumType $typ)` | `xx.ceil` | 向上取整 |
| `floor(NumType $typ)` | `xx.floor` | 向下取整 |
| `trunc(NumType $typ)` | `xx.trunc` | 截断取整 |
| `nearest(NumType $typ)` | `xx.nearest` | 就近取整 |
| `sqrt(NumType $typ)` | `xx.sqrt` | 平方根 |
| `min(NumType $typ)` | `xx.min` | 最小值 |
| `max(NumType $typ)` | `xx.max` | 最大值 |
| `copysign(NumType $typ)` | `xx.copysign` | 复制符号位 |

```php
$fn->getLocal(0)->getLocal(1)->add(NumType::I32);
$fn->getLocal(0)->div(NumType::I32, true);  // 有符号除法
$fn->getLocal(0)->sqrt(NumType::F64);       // f64 平方根
```

---

## 位运算

| 方法 | Wasm 指令 | 说明 |
|---|---|---|
| `band(NumType $typ)` | `xx.and` | 按位与 |
| `bor(NumType $typ)` | `xx.or` | 按位或 |
| `bxor(NumType $typ)` | `xx.xor` | 按位异或 |
| `shl(NumType $typ)` | `xx.shl` | 左移 |
| `shr(NumType $typ, bool $signed = false)` | `xx.shr_s` / `xx.shr_u` | 右移 |
| `clz(NumType $typ)` | `xx.clz` | 前导零计数 |
| `ctz(NumType $typ)` | `xx.ctz` | 后导零计数 |
| `popcnt(NumType $typ)` | `xx.popcnt` | 置位计数 |
| `rotl(NumType $typ)` | `xx.rotl` | 循环左移 |
| `rotr(NumType $typ)` | `xx.rotr` | 循环右移 |

```php
$fn->getLocal(0)->getLocal(1)->band(NumType::I32);
$fn->const(1)->getLocal(0)->shl(NumType::I32);  // 1 << x
$fn->getLocal(0)->shr(NumType::I32, true);       // 有符号右移
```

> **兼容性说明**：`rotr(NumType::I64)` 按 Wasm 规范输出 `i64.rotr` 的 `0x8A`。上游 V 语言参考实现误写为 `0xA8`（该字节实为 `i32.trunc_f32_s`），本库有意修正。

---

## 比较运算

比较运算弹出两个栈值，压入 `i32` 结果（`1` 为 true，`0` 为 false）。

| 方法 | Wasm 指令 | 说明 |
|---|---|---|
| `eqz(NumType $typ)` | `xx.eqz` | 等于零 |
| `eq(NumType $typ)` | `xx.eq` | 等于 |
| `ne(NumType $typ)` | `xx.ne` | 不等于 |
| `lt(NumType $typ, bool $signed = false)` | `xx.lt_s` / `xx.lt_u` | 小于 |
| `gt(NumType $typ, bool $signed = false)` | `xx.gt_s` / `xx.gt_u` | 大于 |
| `le(NumType $typ, bool $signed = false)` | `xx.le_s` / `xx.le_u` | 小于等于 |
| `ge(NumType $typ, bool $signed = false)` | `xx.ge_s` / `xx.ge_u` | 大于等于 |

```php
$fn->getLocal(0)->const(0)->gt(NumType::I32, true);  // x > 0 ? (有符号)
$fn->getLocal(0)->eqz(NumType::I32);                   // x == 0 ?
```

---

## 类型转换

| 方法 | Wasm 指令 | 说明 |
|---|---|---|
| `cast(NumType $from, bool $signed, NumType $to)` | 浮点→整数为 `xx.trunc_sat_xx_s`；浮点↔浮点为 `promote` / `demote`；整数间为 `convert` / `extend` / `wrap` | 非陷阱类型转换 |
| `castTrapping(NumType $from, bool $signed, NumType $to)` | 浮点→整数为 `xx.trunc_xx_s`；其余同 `cast` | 陷阱类型转换（越界触发 trap） |
| `reinterpret(NumType $typ)` | `xx.reinterpret_xx` | 位模式重解释 |
| `signExtend8(ValType $typ)` | `xx.extend8_s` | 符号扩展 8 位 |
| `signExtend16(ValType $typ)` | `xx.extend16_s` | 符号扩展 16 位 |
| `signExtend32()` | `i64.extend32_s` | 符号扩展 32 位（i64 专用） |

```php
$fn->getLocal(0)->cast(NumType::I32, true, NumType::I64);   // i32 -> i64（有符号）
$fn->getLocal(0)->reinterpret(NumType::F32);                 // i32.reinterpret_f32（把 f32 的位当作 i32）
$fn->getLocal(0)->signExtend8(ValType::I32);                 // i32.extend8_s
```

> **cast 与 castTrapping 的区别**：两者仅在「浮点 → 整数」时不同。`cast` 使用饱和转换（`trunc_sat`，越界结果被截断到边界值，不触发 trap）；`castTrapping` 使用陷阱版（`trunc`，越界触发 trap）。其余类型组合两者编码相同。
>
> **`$signed` 的作用范围**：`$signed` 对整数源（`i32`/`i64`）与 `castTrapping` 的浮点源均生效；但 `cast` 在浮点源时**忽略 `$signed`**，固定生成有符号饱和指令（`trunc_sat_..._s`），此为上游参考实现的既有行为。

> **reinterpret 说明**：`reinterpret(NumType $typ)` 的 `$typ` 是**源类型**，生成 `结果类型.reinterpret_源类型`。例如 `reinterpret(NumType::I32)` 生成 `f32.reinterpret_i32`，把栈顶 i32 的位模式直接当作 f32 解释——位不变、仅改变解释方式。

---

## 控制流

### 块结构

| 方法 | 签名 | 返回 |
|---|---|---|
| `block` | `(array $params, array $results): int` | 标签句柄 |
| `loop` | `(array $params, array $results): int` | 标签句柄 |
| `if_` | `(array $params, array $results): int` | 标签句柄 |
| `else_` | `(int $label): self` | — |
| `end` | `(int $label): self` | — |

```php
// if-else 基础用法：条件需在 if_() 之前压入
$fn->getLocal(0)->const(0)->gt(NumType::I32, true); // 压入条件 (i32)
$label = $fn->if_([], [ValType::I32]);
// then 分支
$fn->else_($label);
// else 分支
$fn->end($label);
```

> **注意**：`if_` 使用栈顶的 `i32` 值作为条件（非零为 true）。条件需在 `if_()` 之前压入。

### 跳转指令

| 方法 | 签名 | Wasm 指令 | 说明 |
|---|---|---|---|
| `br` | `(int $label): self` | `br` | 无条件跳转，`$label` 为块句柄 |
| `brIf` | `(int $label): self` | `br_if` | 条件跳转（弹出栈顶 i32），`$label` 为块句柄 |
| `return_` | `(): self` | `return` | 函数返回 |
| `select` | `(): self` | `select` | 三目选择 |
| `drop` | `(): self` | `drop` | 丢弃栈顶值 |
| `unreachable` | `(): self` | `unreachable` | 不可达指令（触发陷阱） |
| `nop` | `(): self` | `nop` | 空操作 |

**标签工作原理**：`block` / `loop` / `if_` 返回一个**标签句柄**（label handle）。`br()` / `brIf()` 传入的是该句柄本身，而不是手写的相对深度；底层会自动换算：跳转到最内层块时深度为 `0`，向外每层递增 `1`。因此务必保存创建块时的返回值并传回，切勿手写 `0` / `1`。

```php
// 循环示例：死循环
$loopLabel = $fn->loop([], []);
$fn->br($loopLabel);   // 跳回该 loop 起始（换算后相对深度为 0）
$fn->end($loopLabel);
```

> **警告**：写成 `$fn->br(0)` 并不表示"跳回最内层"，而会被换算成相对深度 `1`（跳到外层），生成语义错误的跳转。始终使用 `block()` / `loop()` / `if_()` 的返回值。

```php
// select 示例：相当于 cond ? a : b
$fn->getLocal(0)->getLocal(1)  // 压入 val1, val2
   ->getLocal(2)               // 压入 cond (i32)
   ->select();                 // 栈顶 = cond ? val1 : val2
```

### 补丁与导出

| 方法 | 签名 | 说明 |
|---|---|---|
| `patchPos` | `(): int` | 获取当前代码末尾的字节偏移（用于后续补丁锚点） |
| `patch` | `(int $loc, int $begin): self` | 把 `begin` 之后的指令搬到 `loc` 处，并同步修正已有补丁位置 |
| `exportName` | `(string $name): self` | 设置函数的导出名称 |

`patchPos()` 返回当前代码末尾的字节偏移，可作为代码段的锚点；`patch($loc, $begin)` 把 `$begin` 起至末尾的指令整体搬到 `$loc` 处，并同步调整既有补丁位置（要求 `$loc <= $begin`，当 `$loc === $begin` 时为空操作）：

```php
$begin = $fn->patchPos();  // 记录要搬移的代码段起始位置
// ... 生成一段代码 ...
$fn->patch($loc, $begin);  // 把该代码段搬到 $loc 处
```

---

## 函数调用

| 方法 | 签名 | Wasm 指令 | 说明 |
|---|---|---|---|
| `call` | `(string $name): self` | `call` | 调用模块内函数 |
| `callImport` | `(string $mod_name, string $fn_name): self` | `call` | 调用导入的外部函数 |
| `callIndirect` | `(int $typeidx, int $tableidx): self` | `call_indirect` | 经表间接调用 |

```php
$fn->call("helper_fn");               // 调用内部函数
$fn->callImport("env", "log");        // 调用导入函数
```

> **callIndirect 栈顺序**：需先压入函数参数，最后压入表索引（栈顶）。`$typeidx` 由 `Module::newFnType()` 返回。

```php
// call_via_table(op, a, b)：先压 a、b，再压 op 作为表索引
$fn->getLocal(1)->getLocal(2)->getLocal(0)->callIndirect($typeIdx, 0);
```

---

## 表操作

| 方法 | 签名 | Wasm 指令 | 说明 |
|---|---|---|---|
| `tableGet` | `(int $tableidx): self` | `table.get` | 读取表中元素 |
| `tableSet` | `(int $tableidx): self` | `table.set` | 写入表中元素 |
| `tableSize` | `(int $tableidx): self` | `table.size` | 获取表元素数量 |
| `tableGrow` | `(int $tableidx): self` | `table.grow` | 增长表 |
| `tableFill` | `(int $tableidx): self` | `table.fill` | 填充表 |

```php
$fn->getLocal(0)->tableGet(0);              // table.get 0
$fn->getLocal(0)->getLocal(1)->tableSet(0); // table.set 0
$fn->tableSize(0);                          // table.size 0
```

> **注意**：使用表操作前需通过 `Module::assignTable()` 声明表。

---

## 内存操作

| 方法 | 签名 | 说明 |
|---|---|---|
| `load` | `(NumType $typ, int $align, int $offset): self` | 内存加载（弹出地址，压入值） |
| `load8` | `(NumType $typ, bool $signed, int $align, int $offset): self` | 8 位加载 |
| `load16` | `(NumType $typ, bool $signed, int $align, int $offset): self` | 16 位加载 |
| `load32I64` | `(bool $signed, int $align, int $offset): self` | 32 位加载（i64 专用） |
| `store` | `(NumType $typ, int $align, int $offset): self` | 内存存储（弹出 address, value） |
| `store8` | `(NumType $typ, int $align, int $offset): self` | 8 位存储 |
| `store16` | `(NumType $typ, int $align, int $offset): self` | 16 位存储 |
| `store32I64` | `(int $align, int $offset): self` | 32 位存储（i64 专用） |
| `memorySize` | `(): self` | 获取当前内存页数 |
| `memoryGrow` | `(): self` | 增长内存（弹出增长页数，压入之前页数） |
| `memoryInit` | `(int $idx): self` | 从被动数据段初始化内存 |
| `dataDrop` | `(int $idx): self` | 丢弃被动数据段 |
| `memoryCopy` | `(): self` | 内存复制 |
| `memoryFill` | `(): self` | 内存填充 |

```php
// 存储：memory[addr] = value
$fn->getLocal(0)                      // 地址 (addr)
   ->getLocal(1)                      // 值 (value)
   ->store(NumType::I32, 2, 0);      // i32.store align=2 offset=0

// 加载：从内存读取
$fn->getLocal(0)                      // 地址
   ->load(NumType::I32, 2, 0);       // i32.load

// 被动数据段操作
$fn->getLocal(0)                      // 目标内存偏移
   ->const(0)                          // 数据段偏移
   ->const(10)                         // 长度
   ->memoryInit($dataIdx);            // 加载数据段
```

> **注意**：使用内存操作前需通过 `Module::assignMemory()` 配置线性内存。

---

## 引用操作

| 方法 | 签名 | 说明 |
|---|---|---|
| `refNull` | `(RefType $rt): self` | 创建空引用 |
| `refFunc` | `(string $name): self` | 创建内部函数引用 |
| `refFuncImport` | `(string $mod_name, string $fn_name): self` | 创建导入函数引用 |
| `refIsNull` | `(RefType $rt): self` | 判断栈顶引用是否为空 |

```php
$fn->refNull(RefType::FuncRef);                     // ref.null func
$fn->refFunc("my_func");                            // ref.func my_func
$fn->refFuncImport("env", "callback");              // 导入函数引用
$fn->refIsNull(RefType::ExternRef);                 // ref.is_null
```

> **注意**：`ref.is_null` 指令本身不带立即数，`refIsNull()` 的 `$rt` 参数仅为与参考实现保持签名一致而保留，实际不参与编码。
