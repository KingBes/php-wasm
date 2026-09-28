<?php

use Kingbes\Wasm\Module;
use Kingbes\Wasm\NumType;
use Kingbes\Wasm\ValType;

return [
    'name' => '02_block',
    'build' => function (): string {
        $mod = new Module();

        // sum(n) = 1 + 2 + ... + n（block + loop + br_if + br）
        $sum = $mod->newFn(['name' => 'sum', 'params' => [ValType::I32], 'results' => [ValType::I32]]);
        $i = $sum->newLocal(ValType::I32);
        $acc = $sum->newLocal(ValType::I32);
        $sum->const(0)->setLocal($i);
        $sum->const(0)->setLocal($acc);
        $blk = $sum->block([], []);
        $lp = $sum->loop([], []);
        $sum->getLocal($i)->getLocal(0)->ge(NumType::I32, true)->brIf($blk);
        $sum->getLocal($i)->const(1)->add(NumType::I32)->setLocal($i);
        $sum->getLocal($acc)->getLocal($i)->add(NumType::I32)->setLocal($acc);
        $sum->br($lp);
        $sum->end($lp);
        $sum->end($blk);
        $sum->getLocal($acc);
        $mod->commit($sum);

        // abs_diff(a, b) = if a > b { a - b } else { b - a }（if/else）
        $abs = $mod->newFn(['name' => 'abs_diff', 'params' => [ValType::I32, ValType::I32], 'results' => [ValType::I32]]);
        $abs->getLocal(0)->getLocal(1)->gt(NumType::I32, true);
        $ifl = $abs->if_([], [ValType::I32]);
        $abs->getLocal(0)->getLocal(1)->sub(NumType::I32);
        $abs->else_($ifl);
        $abs->getLocal(1)->getLocal(0)->sub(NumType::I32);
        $abs->end($ifl);
        $mod->commit($abs);

        return $mod->toBytes();
    },
    'checks' => [
        ['export' => 'sum', 'args' => [5], 'sig' => ['params' => ['i32'], 'results' => ['i32']], 'expect' => 15],
        ['export' => 'sum', 'args' => [0], 'sig' => ['params' => ['i32'], 'results' => ['i32']], 'expect' => 0],
        ['export' => 'abs_diff', 'args' => [9, 4], 'sig' => ['params' => ['i32', 'i32'], 'results' => ['i32']], 'expect' => 5],
        ['export' => 'abs_diff', 'args' => [4, 9], 'sig' => ['params' => ['i32', 'i32'], 'results' => ['i32']], 'expect' => 5],
    ],
];