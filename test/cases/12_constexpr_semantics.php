<?php

use Kingbes\Wasm\ConstExpression;
use Kingbes\Wasm\Module;
use Kingbes\Wasm\ValType;

// 与 09_constexpr 相同的全局变量语义校验，但**不含** extended-const 算术表达式，
// 因此可在 Node 20 等未启用 extended-const 的运行时上实例化校验。
return [
    'name' => '12_constexpr_semantics',
    'build' => function (): string {
        $mod = new Module();

        $mod->newGlobaImp('env', 'base', ValType::I32, false);

        $mod->newGlobal('g_i32', true, ValType::I32, false, new ConstExpression(42));
        $mod->newGlobal('g_i64', true, ValType::I64, false, ConstExpression::i64(1099511627776));
        $mod->newGlobal('g_f32', true, ValType::F32, false, new ConstExpression(1.5));
        $mod->newGlobal('g_f64', true, ValType::F64, false, ConstExpression::f64(2.5));
        $mod->newGlobal('g_zero', true, ValType::I32, false, new ConstExpression(ValType::I32));
        $mod->newGlobal('g_from_import', true, ValType::I32, false, ConstExpression::globalGet(0));

        return $mod->toBytes();
    },
    'imports' => [
        'env' => [
            'base' => ['global' => true, 'type' => 'i32', 'mutable' => false, 'value' => 100],
        ],
    ],
    'checks' => [
        ['kind' => 'global', 'global' => 'g_i32', 'expect' => 42],
        ['kind' => 'global', 'global' => 'g_i64', 'expect' => 1099511627776],
        ['kind' => 'global', 'global' => 'g_f32', 'expect' => 1.5],
        ['kind' => 'global', 'global' => 'g_f64', 'expect' => 2.5],
        ['kind' => 'global', 'global' => 'g_zero', 'expect' => 0],
        ['kind' => 'global', 'global' => 'g_from_import', 'expect' => 100],
    ],
];