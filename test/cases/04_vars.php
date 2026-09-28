<?php

use Kingbes\Wasm\ConstExpression;
use Kingbes\Wasm\Module;
use Kingbes\Wasm\NumType;
use Kingbes\Wasm\ValType;

return [
    'name' => '04_vars',
    'build' => function (): string {
        $mod = new Module();

        $mod->newGlobal('counter', true, ValType::I32, true, new ConstExpression(0));
        $mod->newGlobal('limit', true, ValType::I32, false, new ConstExpression(100));

        // bump() : counter += 1 ; return counter
        $bump = $mod->newFn(['name' => 'bump', 'params' => [], 'results' => [ValType::I32]]);
        $bump->getGlobal(0)->const(1)->add(NumType::I32)->setGlobal(0)->getGlobal(0);
        $mod->commit($bump);

        // local_roundtrip(x) : tmp = x * 2 ; return tmp
        $rt = $mod->newFn(['name' => 'local_roundtrip', 'params' => [ValType::I32], 'results' => [ValType::I32]]);
        $tmp = $rt->newLocal(ValType::I32);
        $rt->getLocal(0)->const(2)->mul(NumType::I32)->setLocal($tmp)->getLocal($tmp);
        $mod->commit($rt);

        return $mod->toBytes();
    },
    'checks' => [
        ['export' => 'bump', 'args' => [], 'sig' => ['params' => [], 'results' => ['i32']], 'expect' => 1],
        ['export' => 'bump', 'args' => [], 'sig' => ['params' => [], 'results' => ['i32']], 'expect' => 2],
        ['export' => 'local_roundtrip', 'args' => [21], 'sig' => ['params' => ['i32'], 'results' => ['i32']], 'expect' => 42],
        ['kind' => 'global', 'global' => 'limit', 'expect' => 100],
    ],
];