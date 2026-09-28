// 用 V 的 vlib/wasm 生成与 test/cases/* 等价的模块，输出十六进制黄金样本。
//
// 用法（在仓库根目录）：
//     v run test/fixtures/gen.v
//
// 生成 test/fixtures/golden/*.hex；`11_rotr_i64` 不在此生成（PHP 有意修正上游缺陷）。
module main

import encoding.hex
import os
import wasm

const out_dir = 'test/fixtures/golden'

fn emit(name string, bytes []u8) {
	os.write_file('${out_dir}/${name}.hex', hex.encode(bytes)) or { panic(err) }
	println('${name}: ${bytes.len} bytes')
}

// 01 算术
fn build_arith() []u8 {
	mut mod := wasm.Module{}

	mut add := mod.new_function('add', [wasm.ValType.i32_t, wasm.ValType.i32_t], [wasm.ValType.i32_t])
	add.local_get(0)
	add.local_get(1)
	add.add(wasm.NumType.i32_t)
	mod.commit(add, true)

	mut sub := mod.new_function('sub', [wasm.ValType.i32_t, wasm.ValType.i32_t], [wasm.ValType.i32_t])
	sub.local_get(0)
	sub.local_get(1)
	sub.sub(wasm.NumType.i32_t)
	mod.commit(sub, true)

	mut mul := mod.new_function('mul', [wasm.ValType.i32_t, wasm.ValType.i32_t], [wasm.ValType.i32_t])
	mul.local_get(0)
	mul.local_get(1)
	mul.mul(wasm.NumType.i32_t)
	mod.commit(mul, true)

	mut div := mod.new_function('div_s', [wasm.ValType.i32_t, wasm.ValType.i32_t], [wasm.ValType.i32_t])
	div.local_get(0)
	div.local_get(1)
	div.div(wasm.NumType.i32_t, true)
	mod.commit(div, true)

	mut rem := mod.new_function('rem_s', [wasm.ValType.i32_t, wasm.ValType.i32_t], [wasm.ValType.i32_t])
	rem.local_get(0)
	rem.local_get(1)
	rem.rem(wasm.NumType.i32_t, true)
	mod.commit(rem, true)

	mut add_i64 := mod.new_function('add_i64', [wasm.ValType.i64_t, wasm.ValType.i64_t], [wasm.ValType.i64_t])
	add_i64.local_get(0)
	add_i64.local_get(1)
	add_i64.add(wasm.NumType.i64_t)
	mod.commit(add_i64, true)

	mut add_f32 := mod.new_function('add_f32', [wasm.ValType.f32_t, wasm.ValType.f32_t], [wasm.ValType.f32_t])
	add_f32.local_get(0)
	add_f32.local_get(1)
	add_f32.add(wasm.NumType.f32_t)
	mod.commit(add_f32, true)

	mut add_f64 := mod.new_function('add_f64', [wasm.ValType.f64_t, wasm.ValType.f64_t], [wasm.ValType.f64_t])
	add_f64.local_get(0)
	add_f64.local_get(1)
	add_f64.add(wasm.NumType.f64_t)
	mod.commit(add_f64, true)

	mut sqrt := mod.new_function('sqrt_f64', [wasm.ValType.f64_t], [wasm.ValType.f64_t])
	sqrt.local_get(0)
	sqrt.sqrt(wasm.NumType.f64_t)
	mod.commit(sqrt, true)

	return mod.compile()
}

// 02 控制流：block + loop + if/else
fn build_block() []u8 {
	mut mod := wasm.Module{}

	// sum(n) = 1 + 2 + ... + n
	mut sum := mod.new_function('sum', [wasm.ValType.i32_t], [wasm.ValType.i32_t])
	i := sum.new_local(wasm.ValType.i32_t)
	acc := sum.new_local(wasm.ValType.i32_t)
	sum.i32_const(0)
	sum.local_set(i)
	sum.i32_const(0)
	sum.local_set(acc)
	blk := sum.c_block([], [])
	lp := sum.c_loop([], [])
	sum.local_get(i)
	sum.local_get(0)
	sum.ge(wasm.NumType.i32_t, true)
	sum.c_br_if(blk)
	sum.local_get(i)
	sum.i32_const(1)
	sum.add(wasm.NumType.i32_t)
	sum.local_set(i)
	sum.local_get(acc)
	sum.local_get(i)
	sum.add(wasm.NumType.i32_t)
	sum.local_set(acc)
	sum.c_br(lp)
	sum.c_end(lp)
	sum.c_end(blk)
	sum.local_get(acc)
	mod.commit(sum, true)

	// abs_diff(a, b) = if a > b { a - b } else { b - a }
	mut abs_diff := mod.new_function('abs_diff', [wasm.ValType.i32_t, wasm.ValType.i32_t], [wasm.ValType.i32_t])
	abs_diff.local_get(0)
	abs_diff.local_get(1)
	abs_diff.gt(wasm.NumType.i32_t, true)
	ifl := abs_diff.c_if([], [wasm.ValType.i32_t])
	abs_diff.local_get(0)
	abs_diff.local_get(1)
	abs_diff.sub(wasm.NumType.i32_t)
	abs_diff.c_else(ifl)
	abs_diff.local_get(1)
	abs_diff.local_get(0)
	abs_diff.sub(wasm.NumType.i32_t)
	abs_diff.c_end(ifl)
	mod.commit(abs_diff, true)

	return mod.compile()
}

// 03 函数调用：本地 call + 导入 call
fn build_call() []u8 {
	mut mod := wasm.Module{}

	mut add := mod.new_function('add', [wasm.ValType.i32_t, wasm.ValType.i32_t], [wasm.ValType.i32_t])
	add.local_get(0)
	add.local_get(1)
	add.add(wasm.NumType.i32_t)
	mod.commit(add, true)

	mut add_ten := mod.new_function('add_ten', [wasm.ValType.i32_t], [wasm.ValType.i32_t])
	add_ten.local_get(0)
	add_ten.i32_const(10)
	add_ten.call('add')
	mod.commit(add_ten, true)

	mod.new_function_import('env', 'print_i32', [wasm.ValType.i32_t], [])

	mut report := mod.new_function('report', [wasm.ValType.i32_t], [])
	report.local_get(0)
	report.call_import('env', 'print_i32')
	mod.commit(report, true)

	return mod.compile()
}

// 04 局部变量与全局变量
fn build_vars() []u8 {
	mut mod := wasm.Module{}

	mod.new_global('counter', true, wasm.ValType.i32_t, true, wasm.constexpr_value(i32(0)))
	mod.new_global('limit', true, wasm.ValType.i32_t, false, wasm.constexpr_value(i32(100)))

	// bump() : counter += 1 ; return counter
	mut bump := mod.new_function('bump', [], [wasm.ValType.i32_t])
	bump.global_get(wasm.GlobalIndex(0))
	bump.i32_const(1)
	bump.add(wasm.NumType.i32_t)
	bump.global_set(wasm.GlobalIndex(0))
	bump.global_get(wasm.GlobalIndex(0))
	mod.commit(bump, true)

	// local_roundtrip(x): local tmp = x * 2; return tmp
	mut rt := mod.new_function('local_roundtrip', [wasm.ValType.i32_t], [wasm.ValType.i32_t])
	tmp := rt.new_local(wasm.ValType.i32_t)
	rt.local_get(0)
	rt.i32_const(2)
	rt.mul(wasm.NumType.i32_t)
	rt.local_set(tmp)
	rt.local_get(tmp)
	mod.commit(rt, true)

	return mod.compile()
}

// 05 内存 load/store + 主动数据段
fn build_memory() []u8 {
	mut mod := wasm.Module{}

	mod.assign_memory('mem', true, 1, none)
	mod.new_data_segment(none, 0, 'hello'.bytes())

	mut store := mod.new_function('store_value', [wasm.ValType.i32_t, wasm.ValType.i32_t], [])
	store.local_get(0)
	store.local_get(1)
	store.store(wasm.NumType.i32_t, 2, 0)
	mod.commit(store, true)

	mut load := mod.new_function('load_value', [wasm.ValType.i32_t], [wasm.ValType.i32_t])
	load.local_get(0)
	load.load(wasm.NumType.i32_t, 2, 0)
	mod.commit(load, true)

	return mod.compile()
}

// 06 debug name 段
fn build_debug() []u8 {
	mut mod := wasm.Module{}

	mod.enable_debug('wasm_mod')

	mut args := []?string{}
	args << 'a'
	args << 'b'

	mut add := mod.new_debug_function('add', wasm.FuncType{[wasm.ValType.i32_t, wasm.ValType.i32_t], [
		wasm.ValType.i32_t,
	], 'add_type'}, args)
	add.local_get(0)
	add.local_get(1)
	add.add(wasm.NumType.i32_t)
	mod.commit(add, true)

	mut xargs := []?string{}
	xargs << 'x'

	mut calc := mod.new_debug_function('calc', wasm.FuncType{[wasm.ValType.i32_t], [wasm.ValType.i32_t],
		'calc_type'}, xargs)
	tmp := calc.new_local_named(wasm.ValType.i32_t, 'tmp')
	calc.local_get(0)
	calc.local_set(tmp)
	calc.local_get(tmp)
	mod.commit(calc, true)

	return mod.compile()
}

// 08 表 + 元素段 + call_indirect + table.size
fn build_table() []u8 {
	mut mod := wasm.Module{}

	mod.assign_table('tbl', true, wasm.RefType.funcref_t, 2, none)

	mut add := mod.new_function('add', [wasm.ValType.i32_t, wasm.ValType.i32_t], [wasm.ValType.i32_t])
	add.local_get(0)
	add.local_get(1)
	add.add(wasm.NumType.i32_t)
	mod.commit(add, true)

	mut mul := mod.new_function('mul', [wasm.ValType.i32_t, wasm.ValType.i32_t], [wasm.ValType.i32_t])
	mul.local_get(0)
	mul.local_get(1)
	mul.mul(wasm.NumType.i32_t)
	mod.commit(mul, true)

	tidx := mod.new_functype(wasm.FuncType{[wasm.ValType.i32_t, wasm.ValType.i32_t], [
		wasm.ValType.i32_t,
	], none})

	mod.new_active_element(0, 0, ['add', 'mul'])

	mut call := mod.new_function('call_via_table', [wasm.ValType.i32_t, wasm.ValType.i32_t,
		wasm.ValType.i32_t], [wasm.ValType.i32_t])
	call.local_get(1)
	call.local_get(2)
	call.local_get(0)
	call.call_indirect(tidx, 0)
	mod.commit(call, true)

	mut size := mod.new_function('table_len', [], [wasm.ValType.i32_t])
	size.table_size(0)
	mod.commit(size, true)

	return mod.compile()
}

// 09 常量表达式：i32/i64/f32/f64/zero/表达式/global_get
fn build_constexpr() []u8 {
	mut mod := wasm.Module{}

	mod.new_global_import('env', 'base', wasm.ValType.i32_t, false)

	mod.new_global('g_i32', true, wasm.ValType.i32_t, false, wasm.constexpr_value(i32(42)))
	mod.new_global('g_i64', true, wasm.ValType.i64_t, false, wasm.constexpr_value(i64(1099511627776)))
	mod.new_global('g_f32', true, wasm.ValType.f32_t, false, wasm.constexpr_value(f32(1.5)))
	mod.new_global('g_f64', true, wasm.ValType.f64_t, false, wasm.constexpr_value(f64(2.5)))
	mod.new_global('g_zero', true, wasm.ValType.i32_t, false, wasm.constexpr_value_zero(wasm.ValType.i32_t))

	mut expr := wasm.ConstExpression{}
	expr.i32_const(2)
	expr.i32_const(3)
	expr.add(wasm.NumType.i32_t)
	mod.new_global('g_expr', true, wasm.ValType.i32_t, false, expr)

	mut ge := wasm.ConstExpression{}
	ge.global_get(0)
	mod.new_global('g_from_import', true, wasm.ValType.i32_t, false, ge)

	return mod.compile()
}

// 10 数据段：主动 + 被动 + memory.init / data.drop
fn build_data() []u8 {
	mut mod := wasm.Module{}

	mod.assign_memory('mem', true, 1, none)
	mod.new_data_segment(none, 8, 'abc'.bytes())
	mod.new_passive_data_segment(none, 'xyz'.bytes())

	// init_passive() : memory.init 1 (dst=0 src=0 len=3)
	mut init := mod.new_function('init_passive', [], [])
	init.i32_const(0)
	init.i32_const(0)
	init.i32_const(3)
	init.memory_init(1)
	mod.commit(init, true)

	// drop_passive()
	mut drp := mod.new_function('drop_passive', [], [])
	drp.data_drop(1)
	mod.commit(drp, true)

	return mod.compile()
}

fn main() {
	os.mkdir_all(out_dir) or { panic(err) }
	emit('01_arith', build_arith())
	emit('02_block', build_block())
	emit('03_call', build_call())
	emit('04_vars', build_vars())
	emit('05_memory', build_memory())
	emit('06_debug', build_debug())
	emit('08_table', build_table())
	emit('09_constexpr', build_constexpr())
	emit('10_data', build_data())
}