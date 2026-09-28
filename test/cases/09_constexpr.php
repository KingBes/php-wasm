<?php

use Kingbes\Wasm\ConstExpression;
use Kingbes\Wasm\Module;
use Kingbes\Wasm\NumType;
use Kingbes\Wasm\ValType;

return [
    'name' => '09_constexpr',
    'build' => function (): string {
        $mod = new Module();

        $mod->newGlobaImp('env', 'base', ValType::I32, false);

        $mod->newGlobal('g_i32', true, ValType::I32, false, new ConstExpression(42));
        $mod->newGlobal('g_i64', true, ValType::I64, false, ConstExpression::i64(1099511627776));
        $mod->newGlobal('g_f32', true, ValType::F32, false, new ConstExpression(1.5));
        $mod->newGlobal('g_f64', true, ValType::F64, false, ConstExpression::f64(2.5));
        $mod->newGlobal('g_zero', true, ValType::I32, false, new ConstExpression(ValType::I32));

        // 表达式：2 + 3
        $mod->newGlobal('g_expr', true, ValType::I32, false, (new ConstExpression(2))->i32Const(3)->add(NumType::I32));

        // 引用导入的全局变量
        $mod->newGlobal('g_from_import', true, ValType::I32, false, ConstExpression::globalGet(0));

        return $mod->toBytes();
    },
    'imports' => [
        'env' => [
            'base' => ['global' => true, 'type' => 'i32', 'mutable' => false, 'value' => 100],
        ],
    ],
    // 本模块的 g_expr 使用 i32.add 常量表达式，属于 extended-const 提案；
    // Node 20 / 较旧 V8 默认未启用该提案，实例化整个模块会抛 CompileError，
    // 故此处不做 Node 语义校验（改由字节断言覆盖），语义校验见 12_constexpr_semantics。
    'assert' => function (string $bytes): void {
        // (2 + 3) 的 extended-const 序列：i32.const 2 / i32.const 3 / i32.add
        if (!str_contains($bytes, "\x41\x02\x41\x03\x6A")) {
            throw new RuntimeException('未找到 i32.add 常量表达式序列 (41 02 41 03 6A)');
        }
        // global.get 0（引用导入的不可变全局，MVP 合法）
        if (!str_contains($bytes, "\x23\x00")) {
            throw new RuntimeException('未找到 global.get 0 序列 (23 00)');
        }
    },
];