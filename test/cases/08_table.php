<?php

use Kingbes\Wasm\Module;
use Kingbes\Wasm\NumType;
use Kingbes\Wasm\RefType;
use Kingbes\Wasm\ValType;

return [
    'name' => '08_table',
    'build' => function (): string {
        $mod = new Module();

        $mod->assignTable('tbl', true, RefType::FuncRef, 2, 0);

        $add = $mod->newFn(['name' => 'add', 'params' => [ValType::I32, ValType::I32], 'results' => [ValType::I32]]);
        $add->getLocal(0)->getLocal(1)->add(NumType::I32);
        $mod->commit($add);

        $mul = $mod->newFn(['name' => 'mul', 'params' => [ValType::I32, ValType::I32], 'results' => [ValType::I32]]);
        $mul->getLocal(0)->getLocal(1)->mul(NumType::I32);
        $mod->commit($mul);

        $tidx = $mod->newFnType([ValType::I32, ValType::I32], [ValType::I32]);
        $mod->newActiveElement(0, 0, ['add', 'mul']);

        $call = $mod->newFn([
            'name' => 'call_via_table',
            'params' => [ValType::I32, ValType::I32, ValType::I32],
            'results' => [ValType::I32],
        ]);
        $call->getLocal(1)->getLocal(2)->getLocal(0)->callIndirect($tidx, 0);
        $mod->commit($call);

        $size = $mod->newFn(['name' => 'table_len', 'params' => [], 'results' => [ValType::I32]]);
        $size->tableSize(0);
        $mod->commit($size);

        return $mod->toBytes();
    },
    'checks' => [
        ['export' => 'call_via_table', 'args' => [0, 2, 3], 'sig' => ['params' => ['i32', 'i32', 'i32'], 'results' => ['i32']], 'expect' => 5],
        ['export' => 'call_via_table', 'args' => [1, 2, 3], 'sig' => ['params' => ['i32', 'i32', 'i32'], 'results' => ['i32']], 'expect' => 6],
        ['export' => 'table_len', 'args' => [], 'sig' => ['params' => [], 'results' => ['i32']], 'expect' => 2],
        ['kind' => 'tableSize', 'table' => 'tbl', 'expect' => 2],
    ],
];