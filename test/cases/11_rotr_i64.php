<?php

use Kingbes\Wasm\Module;
use Kingbes\Wasm\NumType;
use Kingbes\Wasm\ValType;

// 唯一有意偏离 V 实现的用例：V 的 i64.rotr 误用 0xA8，Wasm 规范为 0x8A。
return [
    'name' => '11_rotr_i64',
    'build' => function (): string {
        $mod = new Module();

        $fn = $mod->newFn(['name' => 'rotr64', 'params' => [ValType::I64, ValType::I64], 'results' => [ValType::I64]]);
        $fn->getLocal(0)->getLocal(1)->rotr(NumType::I64);
        $mod->commit($fn);

        return $mod->toBytes();
    },
    'assert' => function (string $bytes): void {
        // 函数体：local.get 0 / local.get 1 / i64.rotr
        if (!str_contains($bytes, "\x20\x00\x20\x01\x8A")) {
            throw new RuntimeException('i64.rotr 应为 0x8A（当前未命中修正后的 opcode）');
        }
        if (str_contains($bytes, "\x20\x01\xA8")) {
            throw new RuntimeException('检测到 V 的错误 opcode 0xA8');
        }
    },
    'checks' => [
        ['export' => 'rotr64', 'args' => [2, 1], 'sig' => ['params' => ['i64', 'i64'], 'results' => ['i64']], 'expect' => 1],
        ['export' => 'rotr64', 'args' => [1, 63], 'sig' => ['params' => ['i64', 'i64'], 'results' => ['i64']], 'expect' => 2],
    ],
];