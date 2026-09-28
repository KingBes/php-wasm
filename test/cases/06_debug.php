<?php

use Kingbes\Wasm\Module;
use Kingbes\Wasm\NumType;
use Kingbes\Wasm\ValType;

return [
    'name' => '06_debug',
    'build' => function (): string {
        $mod = new Module();
        $mod->enableDebug('wasm_mod');

        $add = $mod->newFn([
            'name' => 'add',
            'params' => [ValType::I32, ValType::I32],
            'results' => [ValType::I32],
            'param_names' => ['a', 'b'],
            'type_name' => 'add_type',
        ], true);
        $add->getLocal(0)->getLocal(1)->add(NumType::I32);
        $mod->commit($add);

        $calc = $mod->newFn([
            'name' => 'calc',
            'params' => [ValType::I32],
            'results' => [ValType::I32],
            'param_names' => ['x'],
            'type_name' => 'calc_type',
        ], true);
        $tmp = $calc->newLocalNamed(ValType::I32, 'tmp');
        $calc->getLocal(0)->setLocal($tmp)->getLocal($tmp);
        $mod->commit($calc);

        return $mod->toBytes();
    },
    'checks' => [
        ['export' => 'add', 'args' => [2, 3], 'sig' => ['params' => ['i32', 'i32'], 'results' => ['i32']], 'expect' => 5],
        ['export' => 'calc', 'args' => [9], 'sig' => ['params' => ['i32'], 'results' => ['i32']], 'expect' => 9],
    ],
];