<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Kingbes\Wasm\NumType;
use Kingbes\Wasm\RefType;
use Kingbes\Wasm\ValType;
use Kingbes\Wasm\Wasm\ByteBuffer;
use Kingbes\Wasm\Wasm\Leb128;
use Kingbes\Wasm\Wasm\Opcodes;

$node = getenv('NODE_BIN') ?: 'node';
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-wasm-tests';
if (!is_dir($tmp)) {
    mkdir($tmp, 0777, true);
}

$passed = 0;
$failed = 0;
$failures = [];

function check(bool $cond, string $message): void
{
    if (!$cond) {
        throw new RuntimeException($message);
    }
}

// ==================== 单元测试 ====================

$unitTests = [
    'Leb128::encodeU32' => static function (): void {
        check(Leb128::encodeU32(0) === "\x00", 'u32(0)');
        check(Leb128::encodeU32(127) === "\x7F", 'u32(127)');
        check(Leb128::encodeU32(128) === "\x80\x01", 'u32(128)');
        check(Leb128::encodeU32(624485) === "\xE5\x8E\x26", 'u32(624485)');
        check(Leb128::encodeU32(0xFFFFFFFF) === "\xFF\xFF\xFF\xFF\x0F", 'u32(max)');
    },
    'Leb128::encodeI32' => static function (): void {
        check(Leb128::encodeI32(0) === "\x00", 'i32(0)');
        check(Leb128::encodeI32(-1) === "\x7F", 'i32(-1)');
        check(Leb128::encodeI32(63) === "\x3F", 'i32(63)');
        check(Leb128::encodeI32(64) === "\xC0\x00", 'i32(64)');
        check(Leb128::encodeI32(-64) === "\x40", 'i32(-64)');
        check(Leb128::encodeI32(-65) === "\xBF\x7F", 'i32(-65)');
        check(Leb128::encodeI32(-2147483648) === "\x80\x80\x80\x80\x78", 'i32(min)');
    },
    'Leb128::encodeI64' => static function (): void {
        check(Leb128::encodeI64(0) === "\x00", 'i64(0)');
        check(Leb128::encodeI64(-1) === "\x7F", 'i64(-1)');
        check(Leb128::encodeI64(PHP_INT_MAX) === "\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\x00", 'i64(max)');
        check(Leb128::encodeI64(PHP_INT_MIN) === "\x80\x80\x80\x80\x80\x80\x80\x80\x80\x7F", 'i64(min)');
    },
    'ByteBuffer' => static function (): void {
        $b = new ByteBuffer();
        $b->append("ab");
        $pos = $b->patchStart();
        $b->append("cde");
        $b->patchLen($pos);
        check($b->bytes() === "ab\x03cde", 'patchLen');
        $b2 = new ByteBuffer();
        $b2->append("xy");
        $p = $b2->patchStart();
        $b2->append("z");
        $b2->patchU32($p, 624485);
        check($b2->bytes() === "xy\xE5\x8E\x26z", 'patchU32');
        $b3 = new ByteBuffer();
        $b3->append("ad");
        $b3->insert(1, "bc");
        check($b3->bytes() === "abcd", 'insert');
    },
    'Opcodes' => static function (): void {
        check(Opcodes::INT['rotr'][Opcodes::T_I32] === 0x78, 'i32.rotr');
        check(Opcodes::INT['rotr'][Opcodes::T_I64] === 0x8A, 'i64.rotr 修正为 0x8A');
        check(Opcodes::BY_NUM['add'][Opcodes::T_F64] === 0xA0, 'f64.add');
        check(Opcodes::BY_SIGN['div']['s'][Opcodes::T_I64] === 0x7F, 'i64.div_s');
        check(Opcodes::CAST_TRAPPING[Opcodes::T_F64][Opcodes::T_I32]['u'] === 0xAB, 'i32.trunc_f64_u');
    },
    '枚举 data()' => static function (): void {
        check(ValType::I32->data() === 0x7F, 'ValType::I32');
        check(ValType::I64->data() === 0x7E, 'ValType::I64');
        check(ValType::F32->data() === 0x7D, 'ValType::F32');
        check(ValType::F64->data() === 0x7C, 'ValType::F64');
        check(ValType::V128->data() === 0x7B, 'ValType::V128');
        check(ValType::FuncRef->data() === 0x70, 'ValType::FuncRef');
        check(ValType::ExternRef->data() === 0x6F, 'ValType::ExternRef');
        check(NumType::I32->data() === 0x7F, 'NumType::I32');
        check(NumType::F64->data() === 0x7C, 'NumType::F64');
        check(RefType::FuncRef->data() === 0x70, 'RefType::FuncRef');
        check(RefType::ExternRef->data() === 0x6F, 'RefType::ExternRef');
    },
];

foreach ($unitTests as $label => $test) {
    try {
        $test();
        $passed++;
        echo "PASS unit: {$label}\n";
    } catch (Throwable $e) {
        $failed++;
        $failures[] = "unit: {$label}";
        echo "FAIL unit: {$label} — {$e->getMessage()}\n";
    }
}

// ==================== 用例 ====================

$caseFiles = glob(__DIR__ . '/cases/*.php') ?: [];
sort($caseFiles);

foreach ($caseFiles as $file) {
    $case = require $file;
    $name = $case['name'] ?? basename($file, '.php');
    try {
        $bytes = ($case['build'])();
        check(str_starts_with($bytes, "\x00asm\x01\x00\x00\x00"), '缺少 Wasm magic/version');

        $expectHex = $case['expect_hex'] ?? null;
        if ($expectHex === null) {
            $golden = __DIR__ . '/fixtures/golden/' . $name . '.hex';
            if (is_file($golden)) {
                $expectHex = (string) file_get_contents($golden);
            }
        }
        if ($expectHex !== null) {
            $expected = strtolower((string) preg_replace('/\s+/', '', $expectHex));
            $actual = bin2hex($bytes);
            check($actual === $expected, "字节不匹配\n    期望: {$expected}\n    实际: {$actual}");
        }

        if (isset($case['assert'])) {
            ($case['assert'])($bytes);
        }

        if (!empty($case['checks'])) {
            $wasmPath = $tmp . DIRECTORY_SEPARATOR . "{$name}.wasm";
            $specPath = $tmp . DIRECTORY_SEPARATOR . "{$name}.json";
            file_put_contents($wasmPath, $bytes);
            file_put_contents($specPath, json_encode([
                'imports' => empty($case['imports']) ? new stdClass() : $case['imports'],
                'checks' => $case['checks'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $cmd = escapeshellarg($node)
                . ' ' . escapeshellarg(__DIR__ . '/node/verify.js')
                . ' ' . escapeshellarg($wasmPath)
                . ' ' . escapeshellarg($specPath);
            $output = shell_exec($cmd . ' 2>&1');
            $result = json_decode((string) $output, true);
            check(is_array($result), 'Node 校验输出非 JSON: ' . trim((string) $output));
            check(!empty($result['ok']), 'Node 语义校验失败: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        $passed++;
        echo "PASS {$name}\n";
    } catch (Throwable $e) {
        $failed++;
        $failures[] = $name;
        echo "FAIL {$name} — {$e->getMessage()}\n";
    }
}

echo "\n" . str_repeat('-', 48) . "\n";
echo "通过 {$passed} 项，失败 {$failed} 项\n";
if ($failed > 0) {
    echo '失败用例: ' . implode(', ', $failures) . "\n";
}
exit($failed === 0 ? 0 : 1);