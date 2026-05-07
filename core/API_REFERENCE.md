# V语言 wasm 模块 API 参考文档

## 概述

`wasm` 模块是 V 语言官方提供的纯 V 实现的 WebAssembly 字节码生成器，采用 Builder 模式，允许开发者在内存中构建完整的 WASM 模块。

---

## 目录

1. [Module 模块级方法](#module)
2. [Function 函数级方法](#function)
3. [ConstExpression 常量表达式方法](#constexpression)
4. [辅助函数](#helpers)
5. [类型索引汇总](#types)

---

## <a name="module"></a>一、Module 模块级方法

### 1.1 函数创建

#### `new_function`
```v
pub fn (mut mod Module) new_function(name string, parameters []ValType, results []ValType) Function
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `name` | `string` | 函数名称，必须唯一 |
| `parameters` | `[]ValType` | 参数类型列表（如 `[.i32_t, .i32_t]`） |
| `results` | `[]ValType` | 返回值类型列表（如 `[.i32_t]`） |

**返回值**：`Function` - 函数对象

**作用**：创建一个新的空函数，用于后续编写字节码。

---

#### `new_debug_function`
```v
pub fn (mut mod Module) new_debug_function(name string, typ FuncType, argument_names []?string) Function
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `name` | `string` | 函数名称 |
| `typ` | `FuncType` | 函数类型（包含参数、返回值、可选类型名称） |
| `argument_names` | `[]?string` | 参数名称列表，长度必须与参数数量一致 |

**作用**：创建带调试信息的函数，支持参数命名和类型命名。

---

### 1.2 函数导入

#### `new_function_import`
```v
pub fn (mut mod Module) new_function_import(modn string, name string, parameters []ValType, results []ValType)
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `modn` | `string` | 导入模块名（如 `"env"`, `"wasi_unstable"`） |
| `name` | `string` | 导入函数名 |
| `parameters` | `[]ValType` | 参数类型列表 |
| `results` | `[]ValType` | 返回值类型列表 |

**作用**：声明从外部模块导入的函数。

---

#### `new_function_import_debug`
```v
pub fn (mut mod Module) new_function_import_debug(modn string, name string, typ FuncType)
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `modn` | `string` | 导入模块名 |
| `name` | `string` | 导入函数名 |
| `typ` | `FuncType` | 函数类型（包含调试名称） |

**作用**：导入带调试信息的函数。

---

### 1.3 全局变量

#### `new_global`
```v
pub fn (mut mod Module) new_global(name string, export bool, typ ValType, is_mut bool, init ConstExpression) GlobalIndex
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `name` | `string` | 全局变量名称 |
| `export` | `bool` | 是否导出 |
| `typ` | `ValType` | 变量类型 |
| `is_mut` | `bool` | 是否可变 |
| `init` | `ConstExpression` | 初始化表达式 |

**返回值**：`GlobalIndex` - 全局变量索引

**作用**：创建一个新的全局变量。

---

#### `new_global_import`
```v
pub fn (mut mod Module) new_global_import(modn string, name string, typ ValType, is_mut bool) GlobalImportIndex
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `modn` | `string` | 导入模块名 |
| `name` | `string` | 导入变量名 |
| `typ` | `ValType` | 变量类型 |
| `is_mut` | `bool` | 是否可变 |

**返回值**：`GlobalImportIndex` - 导入全局变量索引

**作用**：声明从外部导入的全局变量。

---

#### `assign_global_init`
```v
pub fn (mut mod Module) assign_global_init(global GlobalIndex, init ConstExpression)
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `global` | `GlobalIndex` | 全局变量索引 |
| `init` | `ConstExpression` | 初始化表达式 |

**作用**：为已创建的全局变量设置初始化表达式。

---

### 1.4 内存管理

#### `assign_memory`
```v
pub fn (mut mod Module) assign_memory(name string, export bool, min u32, max ?u32)
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `name` | `string` | 内存段名称 |
| `export` | `bool` | 是否导出内存 |
| `min` | `u32` | 最小内存页数（每页 64KB） |
| `max` | `?u32` | 最大内存页数（可选） |

**作用**：配置模块的线性内存。

---

### 1.5 数据段

#### `new_data_segment`
```v
pub fn (mut mod Module) new_data_segment(name ?string, pos int, data []u8) DataSegmentIndex
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `name` | `?string` | 数据段名称（可选，用于调试） |
| `pos` | `int` | 内存起始位置 |
| `data` | `[]u8` | 数据内容 |

**返回值**：`DataSegmentIndex` - 数据段索引

**作用**：创建一个**活跃数据段**（Active Data Segment），会在模块加载时自动初始化到指定内存位置。

---

#### `new_passive_data_segment`
```v
pub fn (mut mod Module) new_passive_data_segment(name ?string, data []u8)
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `name` | `?string` | 数据段名称（可选） |
| `data` | `[]u8` | 数据内容 |

**作用**：创建一个**被动数据段**（Passive Data Segment），不会自动初始化，需通过 `memory.init` 显式初始化。

---

### 1.6 入口函数

#### `assign_start`
```v
pub fn (mut mod Module) assign_start(name string)
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `name` | `string` | 入口函数名称 |

**作用**：设置模块的入口函数（`_start`），模块加载时自动执行。

---

### 1.7 调试模式

#### `enable_debug`
```v
pub fn (mut mod Module) enable_debug(mod_name ?string)
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `mod_name` | `?string` | 模块名称（可选） |

**作用**：启用调试模式，编译时生成 Name Section。

---

### 1.8 函数提交与编译

#### `commit`
```v
pub fn (mut mod Module) commit(func Function, export bool)
```

**参数说明：**

| 参数 | 类型 | 说明 |
|-----|------|------|
| `func` | `Function` | 函数对象 |
| `export` | `bool` | 是否导出该函数 |

**作用**：将函数提交到模块，使其成为模块的一部分。

---

#### `compile`
```v
pub fn (mut mod Module) compile() []u8
```

**返回值**：`[]u8` - WASM 字节码

**作用**：将模块编译为 WASM 二进制格式。

---

## <a name="function"></a>二、Function 函数级方法

### 2.1 常量指令

| 方法 | 参数 | 作用 |
|-----|------|------|
| `i32_const(v i32)` | `v` - 32位整数常量 | 将 `i32.const v` 推入栈 |
| `i64_const(v i64)` | `v` - 64位整数常量 | 将 `i64.const v` 推入栈 |
| `f32_const(v f32)` | `v` - 32位浮点数常量 | 将 `f32.const v` 推入栈 |
| `f64_const(v f64)` | `v` - 64位浮点数常量 | 将 `f64.const v` 推入栈 |

---

### 2.2 局部变量操作

| 方法 | 参数 | 返回值 | 作用 |
|-----|------|-------|------|
| `local_get(local LocalIndex)` | `local` - 局部变量索引 | - | 将指定局部变量的值推入栈 |
| `local_set(local LocalIndex)` | `local` - 局部变量索引 | - | 将栈顶值存入指定局部变量 |
| `local_tee(local LocalIndex)` | `local` - 局部变量索引 | - | 将栈顶值存入指定局部变量，并保留在栈上 |
| `new_local(v ValType)` | `v` - 变量类型 | `LocalIndex` | 创建一个新的无名局部变量 |
| `new_local_named(v ValType, name string)` | `v` - 类型, `name` - 名称 | `LocalIndex` | 创建一个命名局部变量 |

---

### 2.3 全局变量操作

| 方法 | 参数 | 作用 |
|-----|------|------|
| `global_get(global GlobalIndices)` | `global` - 全局变量索引 | 将全局变量值推入栈 |
| `global_set(global GlobalIndices)` | `global` - 全局变量索引 | 将栈顶值存入全局变量 |

---

### 2.4 算术运算

| 方法 | 参数 | 支持类型 |
|-----|------|---------|
| `add(typ NumType)` | `typ` - 数值类型 | i32, i64, f32, f64 |
| `sub(typ NumType)` | `typ` - 数值类型 | i32, i64, f32, f64 |
| `mul(typ NumType)` | `typ` - 数值类型 | i32, i64, f32, f64 |
| `div(typ NumType, is_signed bool)` | `typ` - 类型, `is_signed` - 是否有符号 | i32, i64, f32, f64 |
| `rem(typ NumType, is_signed bool)` | `typ` - 类型, `is_signed` - 是否有符号 | i32, i64 |

---

### 2.5 位运算

| 方法 | 参数 | 支持类型 |
|-----|------|---------|
| `b_and(typ NumType)` | `typ` - 数值类型 | i32, i64 |
| `b_or(typ NumType)` | `typ` - 数值类型 | i32, i64 |
| `b_xor(typ NumType)` | `typ` - 数值类型 | i32, i64 |
| `b_shl(typ NumType)` | `typ` - 数值类型 | i32, i64 |
| `b_shr(typ NumType, is_signed bool)` | `typ` - 类型, `is_signed` - 有符号右移 | i32, i64 |
| `clz(typ NumType)` | `typ` - 数值类型 | i32, i64 |
| `ctz(typ NumType)` | `typ` - 数值类型 | i32, i64 |
| `popcnt(typ NumType)` | `typ` - 数值类型 | i32, i64 |
| `rotl(typ NumType)` | `typ` - 数值类型 | i32, i64 |
| `rotr(typ NumType)` | `typ` - 数值类型 | i32, i64 |

---

### 2.6 浮点运算

| 方法 | 参数 | 作用 |
|-----|------|------|
| `abs(typ NumType)` | `typ` - 数值类型（f32, f64） | 取绝对值 |
| `neg(typ NumType)` | `typ` - 数值类型（f32, f64） | 取反 |
| `ceil(typ NumType)` | `typ` - 数值类型（f32, f64） | 向上取整 |
| `floor(typ NumType)` | `typ` - 数值类型（f32, f64） | 向下取整 |
| `trunc(typ NumType)` | `typ` - 数值类型（f32, f64） | 截断取整 |
| `nearest(typ NumType)` | `typ` - 数值类型（f32, f64） | 四舍五入 |
| `sqrt(typ NumType)` | `typ` - 数值类型（f32, f64） | 平方根 |
| `min(typ NumType)` | `typ` - 数值类型（f32, f64） | 取最小值 |
| `max(typ NumType)` | `typ` - 数值类型（f32, f64） | 取最大值 |
| `copysign(typ NumType)` | `typ` - 数值类型（f32, f64） | 复制符号位 |

---

### 2.7 比较运算

| 方法 | 参数 | 支持类型 |
|-----|------|---------|
| `eqz(typ NumType)` | `typ` - 数值类型 | i32, i64 |
| `eq(typ NumType)` | `typ` - 数值类型 | i32, i64, f32, f64 |
| `ne(typ NumType)` | `typ` - 数值类型 | i32, i64, f32, f64 |
| `lt(typ NumType, is_signed bool)` | `typ` - 类型, `is_signed` - 是否有符号 | i32, i64, f32, f64 |
| `gt(typ NumType, is_signed bool)` | `typ` - 类型, `is_signed` - 是否有符号 | i32, i64, f32, f64 |
| `le(typ NumType, is_signed bool)` | `typ` - 类型, `is_signed` - 是否有符号 | i32, i64, f32, f64 |
| `ge(typ NumType, is_signed bool)` | `typ` - 类型, `is_signed` - 是否有符号 | i32, i64, f32, f64 |

---

### 2.8 类型转换

| 方法 | 参数 | 作用 |
|-----|------|------|
| `cast(a NumType, is_signed bool, b NumType)` | `a` - 源类型, `is_signed` - 是否有符号, `b` - 目标类型 | 类型转换（非陷阱模式） |
| `cast_trapping(a NumType, is_signed bool, b NumType)` | `a` - 源类型, `is_signed` - 是否有符号, `b` - 目标类型 | 类型转换（陷阱模式） |
| `reinterpret(a NumType)` | `a` - 源类型 | 位重新解释 |
| `sign_extend8(typ ValType)` | `typ` - 目标类型 | 8位符号扩展 |
| `sign_extend16(typ ValType)` | `typ` - 目标类型 | 16位符号扩展 |
| `sign_extend32()` | - | 32位符号扩展到 i64 |

---

### 2.9 控制流

| 方法 | 参数 | 返回值 | 作用 |
|-----|------|-------|------|
| `c_block(parameters []ValType, results []ValType)` | `parameters` - 块参数, `results` - 返回类型 | `LabelIndex` | 创建代码块 |
| `c_loop(parameters []ValType, results []ValType)` | `parameters` - 循环参数, `results` - 返回类型 | `LabelIndex` | 创建循环块 |
| `c_if(parameters []ValType, results []ValType)` | `parameters` - 参数, `results` - 返回类型 | `LabelIndex` | 创建 if 表达式 |
| `c_else(label LabelIndex)` | `label` - if 标签索引 | - | 开启 else 分支 |
| `c_end(label LabelIndex)` | `label` - 标签索引 | - | 结束块/循环/if |
| `c_br(label LabelIndex)` | `label` - 目标标签索引 | - | 无条件跳转 |
| `c_br_if(label LabelIndex)` | `label` - 目标标签索引 | - | 条件跳转 |
| `c_return()` | - | - | 从函数返回 |
| `c_select()` | - | - | 根据条件选择值 |
| `drop()` | - | - | 丢弃栈顶值 |
| `unreachable()` | - | - | 标记不可达代码 |
| `nop()` | - | - | 空操作 |

---

### 2.10 函数调用

| 方法 | 参数 | 作用 |
|-----|------|------|
| `call(name string)` | `name` - 目标函数名 | 调用本地定义的函数 |
| `call_import(mod string, name string)` | `mod` - 模块名, `name` - 函数名 | 调用导入的函数 |

---

### 2.11 内存操作

| 方法 | 参数 | 作用 |
|-----|------|------|
| `load(typ NumType, align int, offset int)` | `typ` - 加载类型, `align` - 对齐值, `offset` - 偏移量 | 从内存加载完整值 |
| `load8(typ NumType, is_signed bool, align int, offset int)` | `typ` - 类型, `is_signed` - 是否有符号, `align` - 对齐, `offset` - 偏移 | 从内存加载8位值 |
| `load16(typ NumType, is_signed bool, align int, offset int)` | `typ` - 类型, `is_signed` - 是否有符号, `align` - 对齐, `offset` - 偏移 | 从内存加载16位值 |
| `load32_i64(is_signed bool, align int, offset int)` | `is_signed` - 是否有符号, `align` - 对齐, `offset` - 偏移 | 从内存加载32位值到 i64 |
| `store(typ NumType, align int, offset int)` | `typ` - 存储类型, `align` - 对齐, `offset` - 偏移 | 向内存存储完整值 |
| `store8(typ NumType, align int, offset int)` | `typ` - 类型, `align` - 对齐, `offset` - 偏移 | 向内存存储8位值 |
| `store16(typ NumType, align int, offset int)` | `typ` - 类型, `align` - 对齐, `offset` - 偏移 | 向内存存储16位值 |
| `store32_i64(align int, offset int)` | `align` - 对齐, `offset` - 偏移 | 向内存存储32位值 |
| `memory_size()` | - | 获取内存页数 |
| `memory_grow()` | - | 增长内存 |
| `memory_init(idx DataSegmentIndex)` | `idx` - 数据段索引 | 从被动数据段初始化内存 |
| `memory_copy()` | - | 内存复制 |
| `memory_fill()` | - | 内存填充 |
| `data_drop(idx DataSegmentIndex)` | `idx` - 数据段索引 | 释放被动数据段 |

---

### 2.12 引用类型

| 方法 | 参数 | 作用 |
|-----|------|------|
| `ref_null(rt RefType)` | `rt` - 引用类型 | 将 null 引用推入栈 |
| `ref_is_null(rt RefType)` | `rt` - 引用类型 | 判断引用是否为 null |
| `ref_func(name string)` | `name` - 函数名 | 将函数引用推入栈 |
| `ref_func_import(mod string, name string)` | `mod` - 模块名, `name` - 函数名 | 将导入函数引用推入栈 |

---

### 2.13 补丁与导出

| 方法 | 参数 | 返回值 | 作用 |
|-----|------|-------|------|
| `patch_pos()` | - | `PatchPos` | 获取当前代码位置 |
| `patch(loc PatchPos, begin PatchPos)` | `loc` - 目标位置, `begin` - 起始位置 | - | 将代码从 `begin` 移动到 `loc` |
| `export_name(name string)` | `name` - 导出名称 | - | 设置函数的导出名称 |

---

## <a name="constexpression"></a>三、ConstExpression 常量表达式方法

### 3.1 常量指令

| 方法 | 参数 | 作用 |
|-----|------|------|
| `i32_const(v i32)` | `v` - 整数值 | i32 常量 |
| `i64_const(v i64)` | `v` - 整数值 | i64 常量 |
| `f32_const(v f32)` | `v` - 浮点值 | f32 常量 |
| `f64_const(v f64)` | `v` - 浮点值 | f64 常量 |

---

### 3.2 算术运算

| 方法 | 参数 | 支持类型 |
|-----|------|---------|
| `add(typ NumType)` | `typ` - 数值类型 | i32, i64 |
| `sub(typ NumType)` | `typ` - 数值类型 | i32, i64 |
| `mul(typ NumType)` | `typ` - 数值类型 | i32, i64 |

---

### 3.3 全局变量与引用

| 方法 | 参数 | 作用 |
|-----|------|------|
| `global_get(global GlobalImportIndex)` | `global` - 导入全局变量索引 | 获取导入全局变量值 |
| `ref_null(rt RefType)` | `rt` - 引用类型 | null 引用 |
| `ref_func(name string)` | `name` - 函数名 | 函数引用 |
| `ref_func_import(mod string, name string)` | `mod` - 模块名, `name` - 函数名 | 导入函数引用 |

---

## <a name="helpers"></a>四、辅助函数

| 方法 | 参数 | 返回值 | 作用 |
|-----|------|-------|------|
| `constexpr_value[T](v T)` | `v` - 值（int, i32, i64, f32, f64） | `ConstExpression` | 从值创建常量表达式 |
| `constexpr_value_zero(v ValType)` | `v` - 目标类型 | `ConstExpression` | 创建指定类型的零值表达式 |
| `constexpr_ref_null(rt RefType)` | `rt` - 引用类型 | `ConstExpression` | 创建 null 引用表达式 |

---

## <a name="types"></a>五、类型索引汇总

| 类型别名 | 实际类型 | 说明 |
|---------|---------|------|
| `LocalIndex` | `int` | 局部变量索引 |
| `GlobalIndex` | `int` | 本地全局变量索引 |
| `GlobalImportIndex` | `int` | 导入全局变量索引 |
| `GlobalIndices` | `GlobalIndex \| GlobalImportIndex` | 全局变量索引（联合类型） |
| `DataSegmentIndex` | `int` | 数据段索引 |
| `LabelIndex` | `int` | 标签索引 |
| `PatchPos` | `int` | 补丁位置 |

---

## 六、使用示例

```v
import wasm
import os

fn main() {
    mut m := wasm.Module{}
    
    mut func := m.new_function('add', [.i32_t, .i32_t], [.i32_t])
    {
        func.local_get(0)
        func.local_get(1)
        func.add(.i32_t)
    }
    m.commit(func, true)
    
    mod := m.compile()
    os.write_file_array('add.wasm', mod)!
}
```

---

## 七、数据类型枚举

### NumType
```v
pub enum NumType as u8 {
    i32_t = 0x7f
    i64_t = 0x7e
    f32_t = 0x7d
    f64_t = 0x7c
}
```

### ValType
```v
pub enum ValType as u8 {
    i32_t       = 0x7f
    i64_t       = 0x7e
    f32_t       = 0x7d
    f64_t       = 0x7c
    v128_t      = 0x7b
    funcref_t   = 0x70
    externref_t = 0x6f
}
```

### RefType
```v
pub enum RefType as u8 {
    funcref_t   = 0x70
    externref_t = 0x6f
}
```
