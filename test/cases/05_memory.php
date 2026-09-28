<?php

use Kingbes\Wasm\Module;
use Kingbes\Wasm\NumType;
use Kingbes\Wasm\ValType;

return [
    'name' => '05_memory',
    'build' => function (): string {
        $mod = new Module();

        $mod->assignMemory('mem', true, 1, 0);
        $mod->newDataSegment('', 0, 'hello');

        $store = $mod->newFn(['name' => 'store_value', 'params' => [ValType::I32, ValType::I32], 'results' => []]);
        $store->getLocal(0)->getLocal(1)->store(NumType::I32, 2, 0);
        $mod->commit($store);

        $load = $mod->newFn(['name' => 'load_value', 'params' => [ValType::I32], 'results' => [ValType::I32]]);
        $load->getLocal(0)->load(NumType::I32, 2, 0);
        $mod->commit($load);

        return $mod->toBytes();
    },
    'checks' => [
        ['kind' => 'memory', 'memory' => 'mem', 'offset' => 0, 'length' => 5, 'expectHex' => '68656c6c6f'],
        ['export' => 'store_value', 'args' => [0, 1234], 'sig' => ['params' => ['i32', 'i32'], 'results' => []], 'expect' => null],
        ['export' => 'load_value', 'args' => [0], 'sig' => ['params' => ['i32'], 'results' => ['i32']], 'expect' => 1234],
    ],
];