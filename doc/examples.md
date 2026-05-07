# 使用示例

本文档提供 `php-wasm` 的完整使用示例。

---

## 示例 1：简单的加法函数

创建一个导出的 `add` 函数，接收两个 `i32` 参数，返回它们的和。

```php
<?php

require "vendor/autoload.php";

use Kingbes\Wasm\Module;
use Kingbes\Wasm\ValType;
use Kingbes\Wasm\NumType;

$mod = new Module();

// 创建函数
$fn = $mod->newFn([
    "name"    => "add",
    "params"  => [ValType::I32, ValType::I32],
    "results" => [ValType::I32],
]);

// 编写函数体：获取参数 -> 加法
$fn->getLocal(0)     // local.get 0 (第一个参数)
   ->getLocal(1)     // local.get 1 (第二个参数)
   ->add(NumType::I32); // i32.add

// 提交并导出
$mod->commit($fn);

// 编译
$mod->compile("./add.wasm");
```

等价的 WAT 文本：

```wat
(module
  (func (export "add") (param i32 i32) (result i32)
    local.get 0
    local.get 1
    i32.add
  )
)
```

---

## 示例 2：使用局部变量

计算平方：\( x^2 \)

```php
$mod = new Module();

$fn = $mod->newFn([
    "name"    => "square",
    "params"  => [ValType::I32],
    "results" => [ValType::I32],
]);

// 创建一个局部变量用于暂存
$tmp = $fn->newLocal(ValType::I32);

$fn->getLocal(0)      // 获取参数 x
   ->teeLocal($tmp)   // 暂存到 tmp（栈顶值不变）
   ->getLocal($tmp)   // 再次读取 tmp
   ->mul(NumType::I32); // i32.mul = x * x

$mod->commit($fn);
$mod->compile("./square.wasm");
```

---

## 示例 3：条件分支 (if-else)

实现一个函数：如果 `x > 0` 返回 1，否则返回 0。

```php
$mod = new Module();

$fn = $mod->newFn([
    "name"    => "is_positive",
    "params"  => [ValType::I32],
    "results" => [ValType::I32],
]);

$label = $fn->if_([], [ValType::I32]); // if 块，返回值类型 i32
    $fn->const(1);                      // then: 压入 1
$fn->else_($label);                    // else
    $fn->const(0);                      // else: 压入 0
$fn->end($label);                      // 结束 if

$mod->commit($fn);
$mod->compile("./is_positive.wasm");
```

---

## 示例 4：循环（累加 1 到 N）

实现 `sum(n)` = 1 + 2 + ... + n

```php
$mod = new Module();

$fn = $mod->newFn([
    "name"    => "sum",
    "params"  => [ValType::I32],
    "results" => [ValType::I32],
]);

$i    = $fn->newLocal(ValType::I32); // 循环变量 i
$acc  = $fn->newLocal(ValType::I32); // 累加器

// 初始化: i = n, acc = 0
$fn->getLocal(0)->setLocal($i);
$fn->const(0)->setLocal($acc);

// loop 开始
$loopLabel = $fn->loop([], []);

// 条件: i > 0
$fn->getLocal($i)->const(0)->gt(NumType::I32, true);

// 如果 i <= 0，跳出循环
$fn->brIf(1); // 跳出 loop（向外一层是 block）

// acc += i
$fn->getLocal($acc)->getLocal($i)->add(NumType::I32)->setLocal($acc);

// i--
$fn->getLocal($i)->const(1)->sub(NumType::I32)->setLocal($i);

// 继续循环
$fn->br(0); // 跳回 loop 起始

$fn->end($loopLabel);

// 返回 acc
$fn->getLocal($acc);

$mod->commit($fn);
$mod->compile("./sum.wasm");
```

---

## 示例 5：全局变量

使用可变全局变量实现计数器。

```php
use Kingbes\Wasm\ConstExpression;

$mod = new Module();

// 创建可变全局变量 counter，初始值 0
$counterIdx = $mod->newGlobal("counter", true, ValType::I32, true, new ConstExpression(0));

// increment(): counter += 1
$fn = $mod->newFn([
    "name"    => "increment",
    "params"  => [],
    "results" => [],
]);
$fn->getGlobal($counterIdx)
   ->const(1)
   ->add(NumType::I32)
   ->setGlobal($counterIdx);
$mod->commit($fn);

// get_counter(): 获取 counter 值
$fn2 = $mod->newFn([
    "name"    => "get_counter",
    "params"  => [],
    "results" => [ValType::I32],
]);
$fn2->getGlobal($counterIdx);
$mod->commit($fn2);

$mod->compile("./counter.wasm");
```

---

## 示例 6：内存操作

在内存中存储和读取数据。

```php
$mod = new Module();

// 配置内存（1 页 = 64KB）
$mod->assignMemory("mem", true, 1, 1);

// store_value(addr, val): 将 i32 存入指定地址
$fn = $mod->newFn([
    "name"    => "store_value",
    "params"  => [ValType::I32, ValType::I32],
    "results" => [],
]);
$fn->getLocal(0)  // addr
   ->getLocal(1)  // value
   ->store(NumType::I32, 2, 0); // i32.store align=2 offset=0
$mod->commit($fn);

// load_value(addr): 从指定地址读取 i32
$fn2 = $mod->newFn([
    "name"    => "load_value",
    "params"  => [ValType::I32],
    "results" => [ValType::I32],
]);
$fn2->getLocal(0)
    ->load(NumType::I32, 2, 0);
$mod->commit($fn2);

$mod->compile("./memory.wasm");
```

---

## 示例 7：函数导入与调用

导入外部函数并在 Wasm 中调用。

```php
$mod = new Module();

// 导入外部函数 print_num(i32) -> ()
$mod->impFn("env", "print_num", [
    "params"  => [ValType::I32],
    "results" => [],
]);

// 创建函数，调用导入的 print_num
$fn = $mod->newFn([
    "name"    => "do_print",
    "params"  => [ValType::I32],
    "results" => [],
]);
$fn->getLocal(0)
   ->callImport("env", "print_num");
$mod->commit($fn);

$mod->compile("./import.wasm");
```

---

## 示例 8：Debug 模式

生成带调试信息的 Wasm 模块。

```php
$mod = new Module();
$mod->enableDebug("my_module");

$fn = $mod->newFn([
    "name"       => "add",
    "params"     => [ValType::I32, ValType::I32],
    "results"    => [ValType::I32],
    "param_names"=> ["a", "b"],
    "type_name"  => "add_type",
], true);

$fn->getLocal(0)->getLocal(1)->add(NumType::I32);

$mod->commit($fn);
$mod->compile("./add_debug.wasm");
```

---

## 示例 9：数据段

在模块中嵌入静态数据。

```php
$mod = new Module();
$mod->assignMemory("mem", true, 1, 1);

// 在内存偏移 0 处放入字符串
$mod->newDataSegment("hello", 0, "Hello, World!");

// 创建函数读取内存
$fn = $mod->newFn([
    "name"    => "get_byte",
    "params"  => [ValType::I32],
    "results" => [ValType::I32],
]);
$fn->getLocal(0)
   ->load8(NumType::I32, false, 0, 0); // i32.load8_u
$mod->commit($fn);

$mod->compile("./data_segment.wasm");
```

---

## 示例 10：位运算

位操作示例：将值左移 N 位。

```php
$mod = new Module();

$fn = $mod->newFn([
    "name"    => "shl_n",
    "params"  => [ValType::I32, ValType::I32],
    "results" => [ValType::I32],
]);
$fn->getLocal(0)    // 值
   ->getLocal(1)    // 位数
   ->shl(NumType::I32); // i32.shl

$mod->commit($fn);
$mod->compile("./bitwise.wasm");
```
