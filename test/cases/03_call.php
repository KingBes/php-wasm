<?php

use Kingbes\Wasm\Module;
use Kingbes\Wasm\NumType;
use Kingbes\Wasm\ValType;

return [
    'name' => '03_call',
    'build' => function (): string {
        $mod = new Module();

        $add = $mod->newFn(['name' => 'add', 'params' => [ValType::I32, ValType::I32], 'results' => [ValType::I32]]);
        $add->getLocal(0)->getLocal(1)->add(NumType::I32);
        $mod->commit($add);

        // 本地调用：add_ten(x) = add(x, 10)
        $addTen = $mod->newFn(['name' => 'add_ten', 'params' => [ValType::I32], 'results' => [ValType::I32]]);
        $addTen->getLocal(0)->const(10)->call('add');
        $mod->commit($addTen);

        // 导入调用
        $mod->impFn('env', 'print_i32', ['params' => [ValType::I32], 'results' => []]);

        $report = $mod->newFn(['name' => 'report', 'params' => [ValType::I32], 'results' => []]);
        $report->getLocal(0)->callImport('env', 'print_i32');
        $mod->commit($report);

        return $mod->toBytes();
    },
    'imports' => [
        'env' => [
            'print_i32' => ['results' => []],
        ],
    ],
    'checks' => [
        ['export' => 'add', 'args' => [2, 3], 'sig' => ['params' => ['i32', 'i32'], 'results' => ['i32']], 'expect' => 5],
        ['export' => 'add_ten', 'args' => [5], 'sig' => ['params' => ['i32'], 'results' => ['i32']], 'expect' => 15],
        ['export' => 'report', 'args' => [7], 'sig' => ['params' => ['i32'], 'results' => []], 'expect' => null],
    ],
];