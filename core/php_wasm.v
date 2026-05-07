module main

import wasm
import os

// --------------------------------------------------------------
// ---------------------------- 模块 ----------------------------
// --------------------------------------------------------------

// 创建一个wasm模块
@[export: 'create_mod']
fn create_mod() voidptr {
	mod := &wasm.Module{}
	return voidptr(mod)
}

// 创建一个函数
@[export: 'new_fn']
fn new_fn(mod voidptr, name &char, params []wasm.ValType, results []wasm.ValType) voidptr {
	mut m := unsafe { &wasm.Module(mod) }
	v_name := unsafe { cstring_to_vstring(name) }
	f := m.new_function(v_name, params, results)
	return voidptr(&f)
}

// 创建一个调试函数
@[export: 'new_debug_fn']
fn new_debug_fn(mod voidptr, name &char, typ voidptr, arg_names []string) voidptr {
	mut m := unsafe { &wasm.Module(mod) }
	v_name := unsafe { cstring_to_vstring(name) }
	v_typ := unsafe { &wasm.FuncType(typ) }
	mut v_arg_names := []?string{}
	for arg_name in arg_names {
		v_arg_names << arg_name
	}
	f := m.new_debug_function(v_name, v_typ, v_arg_names)
	return voidptr(&f)
}

// 创建一个函数类型
@[export: 'new_fn_type']
fn new_fn_type(param []wasm.ValType, results []wasm.ValType, name &char) voidptr {
	t := wasm.FuncType{param, results, unsafe { cstring_to_vstring(name) }}
	return voidptr(&t)
}

// 创建一个函数导入
@[export: 'new_fn_import']
fn new_fn_import(mod voidptr, modn &char, name &char, param []wasm.ValType, results []wasm.ValType) {
	mut m := unsafe { &wasm.Module(mod) }
	modn_v := unsafe { cstring_to_vstring(modn) }
	name_v := unsafe { cstring_to_vstring(name) }
	m.new_function_import(modn_v, name_v, param, results)
}

// 创建一个调试函数导入
@[export: 'new_fn_import_debug']
fn new_fn_import_debug(mod voidptr, modn &char, name &char, typ voidptr) {
	mut m := unsafe { &wasm.Module(mod) }
	modn_v := unsafe { cstring_to_vstring(modn) }
	name_v := unsafe { cstring_to_vstring(name) }
	v_typ := unsafe { &wasm.FuncType(typ) }
	m.new_function_import_debug(modn_v, name_v, v_typ)
}

// 创建全局变量
@[export: 'new_global']
fn new_global(mod voidptr, name &char, exp bool, typ wasm.ValType, is_mut bool, init wasm.ConstExpression) int {
	mut m := unsafe { &wasm.Module(mod) }
	name_v := unsafe { cstring_to_vstring(name) }
	g := m.new_global(name_v, exp, typ, is_mut, init)
	return g
}

// 创建全局变量导入
@[export: 'new_global_import']
fn new_global_import(mod voidptr, modn &char, name &char, typ wasm.ValType, is_mut bool) int {
	mut m := unsafe { &wasm.Module(mod) }
	modn_v := unsafe { cstring_to_vstring(modn) }
	name_v := unsafe { cstring_to_vstring(name) }
	g := m.new_global_import(modn_v, name_v, typ, is_mut)
	return g
}

// 设置全局变量初始值
@[export: 'assign_global_init']
fn assign_global_init(mod voidptr, index int, init wasm.ConstExpression) {
	mut m := unsafe { &wasm.Module(mod) }
	m.assign_global_init(index, init)
}

// 分配内存
@[export: 'assign_memory']
fn assign_memory(mod voidptr, name &char, export bool, min u32, max u32) {
	mut m := unsafe { &wasm.Module(mod) }
	name_v := unsafe { cstring_to_vstring(name) }
	mut max_t := if max == 0 { none } else { max }
	m.assign_memory(name_v, export, min, max_t)
}

// 设置入口函数
@[export: 'assign_start']
fn assign_start(mod voidptr, name &char) {
	name_v := unsafe { cstring_to_vstring(name) }
	mut m := unsafe { &wasm.Module(mod) }
	m.assign_start(name_v)
}

// 提交模块中的函数
@[export: 'mod_commit']
fn mod_commit(mod voidptr, func_ptr voidptr, export bool) {
	mut m := unsafe { &wasm.Module(mod) }
	mut f := unsafe { &wasm.Function(func_ptr) }
	m.commit(*f, export)
}

// 编译模块
@[export: 'mod_compile']
fn mod_compile(file &char, mod voidptr) bool {
	mut m := unsafe { &wasm.Module(mod) }
	file_v := unsafe { cstring_to_vstring(file) }
	c := m.compile()
	os.write_file_array(file_v, c) or { return false }
	return true
}

// 启动调试信息
@[export: 'mod_enable_debug']
fn mod_enable_debug(mod voidptr, name &char) {
	mut m := unsafe { &wasm.Module(mod) }
	name_v := unsafe { cstring_to_vstring(name) }
	m.enable_debug(name_v)
}

// 创建主动数据段
@[export: 'mod_new_data_segment']
fn mod_new_data_segment(mod voidptr, name &char, pos int, data []u8) int {
	mut m := unsafe { &wasm.Module(mod) }
	name_v := unsafe { cstring_to_vstring(name) }
	return m.new_data_segment(name_v, pos, data)
}

// 创建被动数据段
@[export: 'mod_new_passive_data_segment']
fn mod_new_passive_data_segment(mod voidptr, name &char, data []u8) {
	mut m := unsafe { &wasm.Module(mod) }
	name_v := unsafe { cstring_to_vstring(name) }
	m.new_passive_data_segment(name_v, data)
}

// --------------------------------------------------------------
// ---------------------------- 函数 ----------------------------
// --------------------------------------------------------------

// -------------------------- 常量 ------------------------------

// i32 常量
@[export: 'fn_i32_const']
fn fn_i32_const(func_ptr voidptr, val i32) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.i32_const(val)
}

// i64 常量
@[export: 'fn_i64_const']
fn fn_i64_const(func_ptr voidptr, val i64) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.i64_const(val)
}

// f32 常量
@[export: 'fn_f32_const']
fn fn_f32_const(func_ptr voidptr, val f32) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.f32_const(val)
}

// f64 常量
@[export: 'fn_f64_const']
fn fn_f64_const(func_ptr voidptr, val f64) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.f64_const(val)
}

// -------------------------- 局部变量 ---------------------------

// 创建局部变量
@[export: 'fn_new_local']
fn fn_new_local(func_ptr voidptr, typ wasm.ValType) int {
	mut f := unsafe { &wasm.Function(func_ptr) }
	l := f.new_local(typ)
	return l
}

// 获取局部变量
@[export: 'fn_local_get']
fn fn_local_get(func_ptr voidptr, index int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.local_get(index)
}

// 创建带名称的局部变量
@[export: 'fn_new_local_named']
fn fn_new_local_named(func_ptr voidptr, typ wasm.ValType, name &char) int {
	mut f := unsafe { &wasm.Function(func_ptr) }
	name_v := unsafe { cstring_to_vstring(name) }
	l := f.new_local_named(typ, name_v)
	return l
}

// 设置局部变量
@[export: 'fn_local_set']
fn fn_local_set(func_ptr voidptr, index int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.local_set(index)
}

// 设置并返回局部变量值
@[export: 'fn_local_tee']
fn fn_local_tee(func_ptr voidptr, index int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.local_tee(index)
}

// -------------------------- 全局变量 ---------------------------

// 获取全局变量值
@[export: 'fn_global_get']
fn fn_global_get(func_ptr voidptr, global int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.global_get(wasm.GlobalIndex(global))
}

// 设置全局变量值
@[export: 'fn_global_set']
fn fn_global_set(func_ptr voidptr, global int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.global_set(wasm.GlobalIndex(global))
}

// -------------------------- 算术运算 ---------------------------

// 加法
@[export: 'fn_add']
fn fn_add(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.add(typ)
}

// 减法
@[export: 'fn_sub']
fn fn_sub(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.sub(typ)
}

// 乘法
@[export: 'fn_mul']
fn fn_mul(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.mul(typ)
}

// 除法
@[export: 'fn_div']
fn fn_div(func_ptr voidptr, typ wasm.NumType, is_signed bool) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.div(typ, is_signed)
}

// 取余
@[export: 'fn_rem']
fn fn_rem(func_ptr voidptr, typ wasm.NumType, is_signed bool) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.rem(typ, is_signed)
}

// 取绝对值
@[export: 'fn_abs']
fn fn_abs(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.abs(typ)
}

// 取反
@[export: 'fn_neg']
fn fn_neg(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.neg(typ)
}

// 向上取整
@[export: 'fn_ceil']
fn fn_ceil(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.ceil(typ)
}

// 向下取整
@[export: 'fn_floor']
fn fn_floor(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.floor(typ)
}

// 截断小数
@[export: 'fn_trunc']
fn fn_trunc(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.trunc(typ)
}

// 四舍五入
@[export: 'fn_nearest']
fn fn_nearest(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.nearest(typ)
}

// 平方根
@[export: 'fn_sqrt']
fn fn_sqrt(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.sqrt(typ)
}

// 最小值
@[export: 'fn_min']
fn fn_min(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.min(typ)
}

// 最大值
@[export: 'fn_max']
fn fn_max(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.max(typ)
}

// 复制符号
@[export: 'fn_copysign']
fn fn_copysign(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.copysign(typ)
}

// -------------------------- 位运算 ---------------------------

// 按位与
@[export: 'fn_b_and']
fn fn_b_and(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.b_and(typ)
}

// 按位或
@[export: 'fn_b_or']
fn fn_b_or(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.b_or(typ)
}

// 按位异或
@[export: 'fn_b_xor']
fn fn_b_xor(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.b_xor(typ)
}

// 左移
@[export: 'fn_b_shl']
fn fn_b_shl(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.b_shl(typ)
}

// 右移
@[export: 'fn_b_shr']
fn fn_b_shr(func_ptr voidptr, typ wasm.NumType, is_signed bool) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.b_shr(typ, is_signed)
}

// 前导零计数
@[export: 'fn_clz']
fn fn_clz(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.clz(typ)
}

// 尾随零计数
@[export: 'fn_ctz']
fn fn_ctz(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.ctz(typ)
}

// 置位计数
@[export: 'fn_popcnt']
fn fn_popcnt(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.popcnt(typ)
}

// 循环左移
@[export: 'fn_rotl']
fn fn_rotl(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.rotl(typ)
}

// 循环右移
@[export: 'fn_rotr']
fn fn_rotr(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.rotr(typ)
}

// -------------------------- 比较运算 ---------------------------

// 零值检测
@[export: 'fn_eqz']
fn fn_eqz(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.eqz(typ)
}

// 相等比较
@[export: 'fn_eq']
fn fn_eq(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.eq(typ)
}

// 不等比较
@[export: 'fn_ne']
fn fn_ne(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.ne(typ)
}

// 小于比较
@[export: 'fn_lt']
fn fn_lt(func_ptr voidptr, typ wasm.NumType, is_signed bool) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.lt(typ, is_signed)
}

// 大于比较
@[export: 'fn_gt']
fn fn_gt(func_ptr voidptr, typ wasm.NumType, is_signed bool) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.gt(typ, is_signed)
}

// 小于等于比较
@[export: 'fn_le']
fn fn_le(func_ptr voidptr, typ wasm.NumType, is_signed bool) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.le(typ, is_signed)
}

// 大于等于比较
@[export: 'fn_ge']
fn fn_ge(func_ptr voidptr, typ wasm.NumType, is_signed bool) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.ge(typ, is_signed)
}

// -------------------------- 类型转换 ---------------------------

// 安全类型转换（非trap）
@[export: 'fn_cast']
fn fn_cast(func_ptr voidptr, from_type wasm.NumType, is_signed bool, to_type wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.cast(from_type, is_signed, to_type)
}

// 陷阱类型转换（可能trap）
@[export: 'fn_cast_trapping']
fn fn_cast_trapping(func_ptr voidptr, from_type wasm.NumType, is_signed bool, to_type wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.cast_trapping(from_type, is_signed, to_type)
}

// 位模式重解释
@[export: 'fn_reinterpret']
fn fn_reinterpret(func_ptr voidptr, typ wasm.NumType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.reinterpret(typ)
}

// 符号扩展8位
@[export: 'fn_sign_extend8']
fn fn_sign_extend8(func_ptr voidptr, typ wasm.ValType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.sign_extend8(typ)
}

// 符号扩展16位
@[export: 'fn_sign_extend16']
fn fn_sign_extend16(func_ptr voidptr, typ wasm.ValType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.sign_extend16(typ)
}

// 符号扩展32位（仅i64）
@[export: 'fn_sign_extend32']
fn fn_sign_extend32(func_ptr voidptr) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.sign_extend32()
}

// -------------------------- 控制流 ---------------------------

// 创建块
@[export: 'fn_c_block']
fn fn_c_block(func_ptr voidptr, params []wasm.ValType, results []wasm.ValType) int {
	mut f := unsafe { &wasm.Function(func_ptr) }
	return f.c_block(params, results)
}

// 创建循环
@[export: 'fn_c_loop']
fn fn_c_loop(func_ptr voidptr, params []wasm.ValType, results []wasm.ValType) int {
	mut f := unsafe { &wasm.Function(func_ptr) }
	return f.c_loop(params, results)
}

// 创建条件分支
@[export: 'fn_c_if']
fn fn_c_if(func_ptr voidptr, params []wasm.ValType, results []wasm.ValType) int {
	mut f := unsafe { &wasm.Function(func_ptr) }
	return f.c_if(params, results)
}

// else分支
@[export: 'fn_c_else']
fn fn_c_else(func_ptr voidptr, label int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.c_else(label)
}

// 结束块/if/loop
@[export: 'fn_c_end']
fn fn_c_end(func_ptr voidptr, label int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.c_end(label)
}

// 无条件跳转
@[export: 'fn_c_br']
fn fn_c_br(func_ptr voidptr, label int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.c_br(label)
}

// 条件跳转
@[export: 'fn_c_br_if']
fn fn_c_br_if(func_ptr voidptr, label int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.c_br_if(label)
}

// 函数返回
@[export: 'fn_c_return']
fn fn_c_return(func_ptr voidptr) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.c_return()
}

// 基于条件选择
@[export: 'fn_c_select']
fn fn_c_select(func_ptr voidptr) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.c_select()
}

// 丢弃栈顶值
@[export: 'fn_drop']
fn fn_drop(func_ptr voidptr) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.drop()
}

// 不可达（trap）
@[export: 'fn_unreachable']
fn fn_unreachable(func_ptr voidptr) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.unreachable()
}

// 空操作
@[export: 'fn_nop']
fn fn_nop(func_ptr voidptr) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.nop()
}

// 获取补丁位置
@[export: 'fn_patch_pos']
fn fn_patch_pos(func_ptr voidptr) int {
	mut f := unsafe { &wasm.Function(func_ptr) }
	return f.patch_pos()
}

// 代码补丁
@[export: 'fn_patch']
fn fn_patch(func_ptr voidptr, loc int, begin int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.patch(loc, begin)
}

// 设置导出名称
@[export: 'fn_export_name']
fn fn_export_name(func_ptr voidptr, name &char) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	name_v := unsafe { cstring_to_vstring(name) }
	f.export_name(name_v)
}

// -------------------------- 函数调用 ---------------------------

// 调用本地函数
@[export: 'fn_call']
fn fn_call(func_ptr voidptr, name &char) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	name_v := unsafe { cstring_to_vstring(name) }
	f.call(name_v)
}

// 调用导入函数
@[export: 'fn_call_import']
fn fn_call_import(func_ptr voidptr, mod_name &char, name &char) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	mod_name_v := unsafe { cstring_to_vstring(mod_name) }
	name_v := unsafe { cstring_to_vstring(name) }
	f.call_import(mod_name_v, name_v)
}

// -------------------------- 内存操作 ---------------------------

// 加载值
@[export: 'fn_load']
fn fn_load(func_ptr voidptr, typ wasm.NumType, align int, offset int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.load(typ, align, offset)
}

// 加载8位
@[export: 'fn_load8']
fn fn_load8(func_ptr voidptr, typ wasm.NumType, is_signed bool, align int, offset int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.load8(typ, is_signed, align, offset)
}

// 加载16位
@[export: 'fn_load16']
fn fn_load16(func_ptr voidptr, typ wasm.NumType, is_signed bool, align int, offset int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.load16(typ, is_signed, align, offset)
}

// 加载32位到i64
@[export: 'fn_load32_i64']
fn fn_load32_i64(func_ptr voidptr, is_signed bool, align int, offset int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.load32_i64(is_signed, align, offset)
}

// 存储值
@[export: 'fn_store']
fn fn_store(func_ptr voidptr, typ wasm.NumType, align int, offset int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.store(typ, align, offset)
}

// 存储8位
@[export: 'fn_store8']
fn fn_store8(func_ptr voidptr, typ wasm.NumType, align int, offset int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.store8(typ, align, offset)
}

// 存储16位
@[export: 'fn_store16']
fn fn_store16(func_ptr voidptr, typ wasm.NumType, align int, offset int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.store16(typ, align, offset)
}

// 存储32位
@[export: 'fn_store32_i64']
fn fn_store32_i64(func_ptr voidptr, align int, offset int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.store32_i64(align, offset)
}

// 获取内存大小
@[export: 'fn_memory_size']
fn fn_memory_size(func_ptr voidptr) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.memory_size()
}

// 扩展内存
@[export: 'fn_memory_grow']
fn fn_memory_grow(func_ptr voidptr) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.memory_grow()
}

// 初始化内存（从被动段）
@[export: 'fn_memory_init']
fn fn_memory_init(func_ptr voidptr, idx int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.memory_init(idx)
}

// 丢弃被动数据段
@[export: 'fn_data_drop']
fn fn_data_drop(func_ptr voidptr, idx int) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.data_drop(idx)
}

// 内存复制
@[export: 'fn_memory_copy']
fn fn_memory_copy(func_ptr voidptr) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.memory_copy()
}

// 内存填充
@[export: 'fn_memory_fill']
fn fn_memory_fill(func_ptr voidptr) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.memory_fill()
}

// -------------------------- 引用操作 ---------------------------

// 空引用
@[export: 'fn_ref_null']
fn fn_ref_null(func_ptr voidptr, rt wasm.RefType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.ref_null(rt)
}

// 函数引用
@[export: 'fn_ref_func']
fn fn_ref_func(func_ptr voidptr, name &char) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	name_v := unsafe { cstring_to_vstring(name) }
	f.ref_func(name_v)
}

// 导入函数引用
@[export: 'fn_ref_func_import']
fn fn_ref_func_import(func_ptr voidptr, mod_name &char, name &char) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	mod_name_v := unsafe { cstring_to_vstring(mod_name) }
	name_v := unsafe { cstring_to_vstring(name) }
	f.ref_func_import(mod_name_v, name_v)
}

// 引用检测
@[export: 'fn_ref_is_null']
fn fn_ref_is_null(func_ptr voidptr, rt wasm.RefType) {
	mut f := unsafe { &wasm.Function(func_ptr) }
	f.ref_is_null(rt)
}

// -------------------------- ConstExpression 工具函数 ---------------------------

// 从值创建常量表达式
@[export: 'constexpr_value_i32']
fn constexpr_value_i32(val i32) wasm.ConstExpression {
	return wasm.constexpr_value(val)
}

@[export: 'constexpr_value_i64']
fn constexpr_value_i64(val i64) wasm.ConstExpression {
	return wasm.constexpr_value(val)
}

@[export: 'constexpr_value_f32']
fn constexpr_value_f32(val f32) wasm.ConstExpression {
	return wasm.constexpr_value(val)
}

@[export: 'constexpr_value_f64']
fn constexpr_value_f64(val f64) wasm.ConstExpression {
	return wasm.constexpr_value(val)
}

// 从 ValType 创建零值常量表达式
@[export: 'constexpr_value_zero']
fn constexpr_value_zero(v wasm.ValType) wasm.ConstExpression {
	return wasm.constexpr_value_zero(v)
}

// 创建空引用常量表达式
@[export: 'constexpr_ref_null']
fn constexpr_ref_null(rt wasm.RefType) wasm.ConstExpression {
	return wasm.constexpr_ref_null(rt)
}

// --------------------------------------------------------------
// ---------------------------- 帮助 ----------------------------
// --------------------------------------------------------------

// -------------------------- 字符串数组 -------------------------

// 创建字符串数组
@[export: 'help_new_arr_str']
fn help_new_arr_str() []string {
	mut arr := []string{}
	return arr
}

// 追加字符串数组
@[export: 'help_add_arr_str']
fn help_add_arr_str(arr []string, str &char) []string {
	mut new_arr := arr.clone()
	new_arr << unsafe { cstring_to_vstring(str) }
	return new_arr
}

// -------------------------- ValType数组 -------------------------

// 创建 wasm值 数组
@[export: 'help_new_arr_val_type']
fn help_new_arr_val_type() []wasm.ValType {
	mut arr := []wasm.ValType{}
	return arr
}

// 追加 wasm值 数组
@[export: 'help_add_arr_val_type']
fn help_add_arr_val_type(arr []wasm.ValType, vtyp wasm.ValType) []wasm.ValType {
	mut new_arr := arr.clone()
	new_arr << vtyp
	return new_arr
}

// -------------------------- NumType数组 -------------------------

// 创建 wasm值 数组
@[export: 'help_new_arr_num_type']
fn help_new_arr_num_type() []wasm.NumType {
	mut arr := []wasm.NumType{}
	return arr
}

// 追加 wasm值 数组
@[export: 'help_add_arr_num_type']
fn help_add_arr_num_type(arr []wasm.NumType, vtyp wasm.NumType) []wasm.NumType {
	mut new_arr := arr.clone()
	new_arr << vtyp
	return new_arr
}

// -------------------------- 创建Type值 -------------------------

// ValType字符串转 ValType
@[export: 'help_val_type']
fn help_val_type(val &char) wasm.ValType {
	v_val := unsafe { cstring_to_vstring(val) }
	return match v_val {
		'i32' { .i32_t }
		'i64' { .i64_t }
		'f32' { .f32_t }
		'f64' { .f64_t }
		'v128' { .v128_t }
		'funcref' { .funcref_t }
		else { .externref_t }
	}
}

// NumType字符串转 NumType
@[export: 'help_num_type']
fn help_num_type(val &char) wasm.NumType {
	v_val := unsafe { cstring_to_vstring(val) }
	return match v_val {
		'i32' { .i32_t }
		'i64' { .i64_t }
		'f32' { .f32_t }
		else { .f64_t }
	}
}

// RefType字符串转 RefType
@[export: 'help_ref_type']
fn help_ref_type(val &char) wasm.RefType {
	v_val := unsafe { cstring_to_vstring(val) }
	return match v_val {
		'funcref' { .funcref_t }
		else { .externref_t }
	}
}

// 字符串转u8
@[export: 'help_str_to_u8']
fn help_str_to_u8(str &char) u8 {
	v_str := unsafe { cstring_to_vstring(str) }
	return v_str.u8()
}

// 字符串转u8数组
@[export: 'help_str_to_u8s']
fn help_str_to_u8s(str &char) []u8 {
	v_str := unsafe { cstring_to_vstring(str) }
	return v_str.u8_array()
}
