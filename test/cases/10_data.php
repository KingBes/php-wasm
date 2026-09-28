<?php

use Kingbes\Wasm\Module;
use Kingbes\Wasm\ValType;

return [
    'name' => '10_data',
    'build' => function (): string {
        $mod = new Module();

        $mod->assignMemory('mem', true, 1, 0);
        $mod->newDataSegment('', 8, 'abc');          // 主动数据段，索引 0
        $mod->newPassiveDataSegment('', 'xyz');      // 被动数据段，索引 1

        // 把被动数据段写入内存 0..3
        $init = $mod->newFn(['name' => 'init_passive', 'params' => [], 'results' => []]);
        $init->const(0)->const(0)->const(3)->memoryInit(1);
        $mod->commit($init);

        $drop = $mod->newFn(['name' => 'drop_passive', 'params' => [], 'results' => []]);
        $drop->dataDrop(1);
        $mod->commit($drop);

        return $mod->toBytes();
    },
    'checks' => [
        ['kind' => 'memory', 'memory' => 'mem', 'offset' => 8, 'length' => 3, 'expectHex' => '616263'],
        ['export' => 'init_passive', 'args' => [], 'sig' => ['params' => [], 'results' => []], 'expect' => null],
        ['kind' => 'memory', 'memory' => 'mem', 'offset' => 0, 'length' => 3, 'expectHex' => '78797a'],
        ['export' => 'drop_passive', 'args' => [], 'sig' => ['params' => [], 'results' => []], 'expect' => null],
    ],
];