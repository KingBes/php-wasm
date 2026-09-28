<?php

use Kingbes\Wasm\Module;
use Kingbes\Wasm\NumType;
use Kingbes\Wasm\ValType;

return [
    'name' => '01_arith',
    'build' => function (): string {
        $mod = new Module();

        $binary = static function (Module $mod, string $name, NumType $typ, string $op): void {
            $fn = $mod->newFn([
                'name' => $name,
                'params' => [ValType::I32, ValType::I32],
                'results' => [ValType::I32],
            ]);
            $fn->getLocal(0)->getLocal(1)->{$op}($typ);
            $mod->commit($fn);
        };

        $add = $mod->newFn(['name' => 'add', 'params' => [ValType::I32, ValType::I32], 'results' => [ValType::I32]]);
        $add->getLocal(0)->getLocal(1)->add(NumType::I32);
        $mod->commit($add);

        $sub = $mod->newFn(['name' => 'sub', 'params' => [ValType::I32, ValType::I32], 'results' => [ValType::I32]]);
        $sub->getLocal(0)->getLocal(1)->sub(NumType::I32);
        $mod->commit($sub);

        $mul = $mod->newFn(['name' => 'mul', 'params' => [ValType::I32, ValType::I32], 'results' => [ValType::I32]]);
        $mul->getLocal(0)->getLocal(1)->mul(NumType::I32);
        $mod->commit($mul);

        $div = $mod->newFn(['name' => 'div_s', 'params' => [ValType::I32, ValType::I32], 'results' => [ValType::I32]]);
        $div->getLocal(0)->getLocal(1)->div(NumType::I32, true);
        $mod->commit($div);

        $rem = $mod->newFn(['name' => 'rem_s', 'params' => [ValType::I32, ValType::I32], 'results' => [ValType::I32]]);
        $rem->getLocal(0)->getLocal(1)->rem(NumType::I32, true);
        $mod->commit($rem);

        $addI64 = $mod->newFn(['name' => 'add_i64', 'params' => [ValType::I64, ValType::I64], 'results' => [ValType::I64]]);
        $addI64->getLocal(0)->getLocal(1)->add(NumType::I64);
        $mod->commit($addI64);

        $addF32 = $mod->newFn(['name' => 'add_f32', 'params' => [ValType::F32, ValType::F32], 'results' => [ValType::F32]]);
        $addF32->getLocal(0)->getLocal(1)->add(NumType::F32);
        $mod->commit($addF32);

        $addF64 = $mod->newFn(['name' => 'add_f64', 'params' => [ValType::F64, ValType::F64], 'results' => [ValType::F64]]);
        $addF64->getLocal(0)->getLocal(1)->add(NumType::F64);
        $mod->commit($addF64);

        $sqrt = $mod->newFn(['name' => 'sqrt_f64', 'params' => [ValType::F64], 'results' => [ValType::F64]]);
        $sqrt->getLocal(0)->sqrt(NumType::F64);
        $mod->commit($sqrt);

        return $mod->toBytes();
    },
    'checks' => [
        ['export' => 'add', 'args' => [2, 3], 'sig' => ['params' => ['i32', 'i32'], 'results' => ['i32']], 'expect' => 5],
        ['export' => 'sub', 'args' => [9, 4], 'sig' => ['params' => ['i32', 'i32'], 'results' => ['i32']], 'expect' => 5],
        ['export' => 'mul', 'args' => [3, 4], 'sig' => ['params' => ['i32', 'i32'], 'results' => ['i32']], 'expect' => 12],
        ['export' => 'div_s', 'args' => [7, 2], 'sig' => ['params' => ['i32', 'i32'], 'results' => ['i32']], 'expect' => 3],
        ['export' => 'rem_s', 'args' => [7, 2], 'sig' => ['params' => ['i32', 'i32'], 'results' => ['i32']], 'expect' => 1],
        ['export' => 'add_i64', 'args' => [2, 3], 'sig' => ['params' => ['i64', 'i64'], 'results' => ['i64']], 'expect' => 5],
        ['export' => 'add_f32', 'args' => [1.5, 0.25], 'sig' => ['params' => ['f32', 'f32'], 'results' => ['f32']], 'expect' => 1.75],
        ['export' => 'add_f64', 'args' => [1.5, 2.25], 'sig' => ['params' => ['f64', 'f64'], 'results' => ['f64']], 'expect' => 3.75],
        ['export' => 'sqrt_f64', 'args' => [9], 'sig' => ['params' => ['f64'], 'results' => ['f64']], 'expect' => 3],
    ],
];