<?php

declare(strict_types=1);

namespace Kingbes\Wasm\Wasm;

/**
 * 指令编码入口（对齐 V `wasm/instructions.v`）。
 *
 * 所有方法把字节写入 `FunctionState::$code`。
 */
final class Encoder
{
    // ==================== 常量 ====================

    public static function i32Const(FunctionState $f, int $v): void
    {
        $f->code .= chr(0x41) . Leb128::encodeI32($v);
    }

    public static function i64Const(FunctionState $f, int $v): void
    {
        $f->code .= chr(0x42) . Leb128::encodeI64($v);
    }

    public static function f32Const(FunctionState $f, float $v): void
    {
        $f->code .= chr(0x43) . pack('g', $v);
    }

    public static function f64Const(FunctionState $f, float $v): void
    {
        $f->code .= chr(0x44) . pack('e', $v);
    }

    // ==================== 局部 / 全局变量 ====================

    public static function localGet(FunctionState $f, int $index): void
    {
        $f->code .= chr(0x20) . Leb128::encodeU32($index);
    }

    public static function localSet(FunctionState $f, int $index): void
    {
        $f->code .= chr(0x21) . Leb128::encodeU32($index);
    }

    public static function localTee(FunctionState $f, int $index): void
    {
        $f->code .= chr(0x22) . Leb128::encodeU32($index);
    }

    /**
     * 本地全局：写占位补丁，compile 时解析为 `globalImports.len + idx`。
     */
    public static function globalGet(FunctionState $f, int $index): void
    {
        $f->code .= chr(0x23);
        $f->patches[] = Patch::global($index, strlen($f->code));
    }

    public static function globalSet(FunctionState $f, int $index): void
    {
        $f->code .= chr(0x24);
        $f->patches[] = Patch::global($index, strlen($f->code));
    }

    /**
     * 导入全局：直接写索引。
     */
    public static function globalGetImport(FunctionState $f, int $index): void
    {
        $f->code .= chr(0x23) . Leb128::encodeU32($index);
    }

    public static function globalSetImport(FunctionState $f, int $index): void
    {
        $f->code .= chr(0x24) . Leb128::encodeU32($index);
    }

    // ==================== 无参指令 ====================

    public static function simple(FunctionState $f, string $name): void
    {
        $f->code .= Opcodes::bytes(Opcodes::SIMPLE[$name]);
    }

    // ==================== 数值指令 ====================

    /**
     * 四种数值类型均适用的指令（add/sub/mul/eq/ne）。
     */
    public static function byNum(FunctionState $f, string $name, int $typ): void
    {
        $map = Opcodes::BY_NUM[$name];
        if (!isset($map[$typ])) {
            throw new \InvalidArgumentException("{$name}: unsupported numeric type 0x" . dechex($typ));
        }
        $f->code .= chr($map[$typ]);
    }

    /**
     * 有符号/无符号分派、四种类型均适用（div/lt/gt/le/ge）。
     */
    public static function bySign(FunctionState $f, string $name, int $typ, bool $signed): void
    {
        $map = Opcodes::BY_SIGN[$name][$signed ? 's' : 'u'];
        if (!isset($map[$typ])) {
            throw new \InvalidArgumentException("{$name}: unsupported numeric type 0x" . dechex($typ));
        }
        $f->code .= chr($map[$typ]);
    }

    /**
     * 仅 i32/i64（band/bor/bxor/shl/clz/ctz/popcnt/rotl/rotr/eqz/store8/store16）。
     */
    public static function intOnly(FunctionState $f, string $name, int $typ): void
    {
        self::assertInt($name, $typ);
        $f->code .= chr(Opcodes::INT[$name][$typ]);
    }

    /**
     * 仅 i32/i64 且按有符号分派（rem/shr/load8/load16）。
     */
    public static function intOnlySign(FunctionState $f, string $name, int $typ, bool $signed): void
    {
        self::assertInt($name, $typ);
        $f->code .= chr(Opcodes::INT_SIGN[$name][$signed ? 's' : 'u'][$typ]);
    }

    /**
     * 仅 f32/f64（abs/neg/ceil/floor/trunc/nearest/sqrt/min/max/copysign）。
     */
    public static function floatOnly(FunctionState $f, string $name, int $typ): void
    {
        if ($typ !== Opcodes::T_F32 && $typ !== Opcodes::T_F64) {
            throw new \InvalidArgumentException("{$name}: only f32/f64 are permitted");
        }
        $f->code .= chr(Opcodes::FLOAT[$name][$typ]);
    }

    public static function reinterpret(FunctionState $f, int $typ): void
    {
        $f->code .= chr(Opcodes::REINTERPRET[$typ]);
    }

    public static function cast(FunctionState $f, int $from, bool $signed, int $to): void
    {
        if ($from === Opcodes::T_F32 || $from === Opcodes::T_F64) {
            $map = $from === Opcodes::T_F32 ? Opcodes::CAST_F32 : Opcodes::CAST_F64;
            if (isset($map[$to])) {
                self::emit($f, $map[$to]);
            }

            return;
        }

        if ($from === Opcodes::T_I64 && $to === Opcodes::T_I32) {
            self::emit($f, Opcodes::CAST_I64_I32);

            return;
        }

        $table = $signed ? Opcodes::CAST_SIGNED : Opcodes::CAST_UNSIGNED;
        if (isset($table[$from][$to])) {
            self::emit($f, $table[$from][$to]);
        }
    }

    public static function castTrapping(FunctionState $f, int $from, bool $signed, int $to): void
    {
        if (isset(Opcodes::CAST_TRAPPING[$from][$to])) {
            self::emit($f, Opcodes::CAST_TRAPPING[$from][$to][$signed ? 's' : 'u']);

            return;
        }

        self::cast($f, $from, $signed, $to);
    }

    // ==================== 控制流 ====================

    public static function block(FunctionState $f, array $params, array $results): int
    {
        $f->label++;
        $f->code .= chr(0x02);
        self::blockType($f, $params, $results);

        return $f->label;
    }

    public static function loop(FunctionState $f, array $params, array $results): int
    {
        $f->label++;
        $f->code .= chr(0x03);
        self::blockType($f, $params, $results);

        return $f->label;
    }

    public static function cIf(FunctionState $f, array $params, array $results): int
    {
        $f->label++;
        $f->code .= chr(0x04);
        self::blockType($f, $params, $results);

        return $f->label;
    }

    public static function cElse(FunctionState $f, int $label): void
    {
        if ($f->label !== $label) {
            throw new \InvalidArgumentException("c_else: called with an invalid label {$label}");
        }
        $f->code .= chr(0x05);
    }

    public static function cEnd(FunctionState $f, int $label): void
    {
        if ($f->label !== $label) {
            throw new \InvalidArgumentException("c_end: called with an invalid label {$label}");
        }
        $f->label--;
        if ($f->label < 0) {
            throw new \InvalidArgumentException('c_end: negative label index, unbalanced calls');
        }
        $f->code .= chr(0x0B);
    }

    public static function cBr(FunctionState $f, int $label): void
    {
        $v = $f->label - $label;
        if ($v < 0) {
            throw new \InvalidArgumentException('c_br: malformed label index');
        }
        $f->code .= chr(0x0C) . Leb128::encodeU32($v);
    }

    public static function cBrIf(FunctionState $f, int $label): void
    {
        $v = $f->label - $label;
        if ($v < 0) {
            throw new \InvalidArgumentException('c_br_if: malformed label index');
        }
        $f->code .= chr(0x0D) . Leb128::encodeU32($v);
    }

    // ==================== 函数调用 ====================

    public static function call(FunctionState $f, string $name): void
    {
        $f->code .= chr(0x10);
        $f->patches[] = Patch::function($name, strlen($f->code));
    }

    public static function callImport(FunctionState $f, string $mod, string $name): void
    {
        $f->code .= chr(0x10);
        $f->patches[] = Patch::import($mod, $name, strlen($f->code));
    }

    public static function refFunc(FunctionState $f, string $name): void
    {
        $f->code .= chr(0xD2);
        $f->patches[] = Patch::function($name, strlen($f->code));
    }

    public static function refFuncImport(FunctionState $f, string $mod, string $name): void
    {
        $f->code .= chr(0xD2);
        $f->patches[] = Patch::import($mod, $name, strlen($f->code));
    }

    public static function callIndirect(FunctionState $f, int $typeIdx, int $tableIdx): void
    {
        $f->code .= chr(0x11) . Leb128::encodeU32($typeIdx) . Leb128::encodeU32($tableIdx);
    }

    // ==================== 内存操作 ====================

    public static function load(FunctionState $f, int $typ, int $align, int $offset): void
    {
        $f->code .= chr(Opcodes::BY_NUM['load'][$typ]);
        $f->code .= Leb128::encodeU32($align) . Leb128::encodeU32($offset);
    }

    public static function load8(FunctionState $f, int $typ, bool $signed, int $align, int $offset): void
    {
        self::assertInt('load8', $typ);
        $f->code .= chr(Opcodes::INT_SIGN['load8'][$signed ? 's' : 'u'][$typ]);
        $f->code .= Leb128::encodeU32($align) . Leb128::encodeU32($offset);
    }

    public static function load16(FunctionState $f, int $typ, bool $signed, int $align, int $offset): void
    {
        self::assertInt('load16', $typ);
        $f->code .= chr(Opcodes::INT_SIGN['load16'][$signed ? 's' : 'u'][$typ]);
        $f->code .= Leb128::encodeU32($align) . Leb128::encodeU32($offset);
    }

    public static function load32I64(FunctionState $f, bool $signed, int $align, int $offset): void
    {
        $f->code .= chr($signed ? 0x34 : 0x35);
        $f->code .= Leb128::encodeU32($align) . Leb128::encodeU32($offset);
    }

    public static function store(FunctionState $f, int $typ, int $align, int $offset): void
    {
        $f->code .= chr(Opcodes::BY_NUM['store'][$typ]);
        $f->code .= Leb128::encodeU32($align) . Leb128::encodeU32($offset);
    }

    public static function store8(FunctionState $f, int $typ, int $align, int $offset): void
    {
        self::intOnly($f, 'store8', $typ);
        $f->code .= Leb128::encodeU32($align) . Leb128::encodeU32($offset);
    }

    public static function store16(FunctionState $f, int $typ, int $align, int $offset): void
    {
        self::intOnly($f, 'store16', $typ);
        $f->code .= Leb128::encodeU32($align) . Leb128::encodeU32($offset);
    }

    public static function store32I64(FunctionState $f, int $align, int $offset): void
    {
        $f->code .= chr(0x3E);
        $f->code .= Leb128::encodeU32($align) . Leb128::encodeU32($offset);
    }

    public static function memoryInit(FunctionState $f, int $idx): void
    {
        $f->code .= chr(0xFC) . chr(0x08) . Leb128::encodeU32($idx) . chr(0x00);
    }

    public static function dataDrop(FunctionState $f, int $idx): void
    {
        $f->code .= chr(0xFC) . chr(0x09) . Leb128::encodeU32($idx);
    }

    // ==================== 引用 / 表 ====================

    public static function refNull(FunctionState $f, int $refType): void
    {
        $f->code .= chr(0xD0) . chr($refType & 0xFF);
    }

    public static function refIsNull(FunctionState $f): void
    {
        $f->code .= chr(0xD1);
    }

    public static function tableGet(FunctionState $f, int $tableIdx): void
    {
        $f->code .= chr(0x25) . Leb128::encodeU32($tableIdx);
    }

    public static function tableSet(FunctionState $f, int $tableIdx): void
    {
        $f->code .= chr(0x26) . Leb128::encodeU32($tableIdx);
    }

    public static function tableSize(FunctionState $f, int $tableIdx): void
    {
        $f->code .= chr(0xFC) . chr(0x10) . Leb128::encodeU32($tableIdx);
    }

    public static function tableGrow(FunctionState $f, int $tableIdx): void
    {
        $f->code .= chr(0xFC) . chr(0x0F) . Leb128::encodeU32($tableIdx);
    }

    public static function tableFill(FunctionState $f, int $tableIdx): void
    {
        $f->code .= chr(0xFC) . chr(0x11) . Leb128::encodeU32($tableIdx);
    }

    // ==================== 内部 ====================

    private static function blockType(FunctionState $f, array $params, array $results): void
    {
        if (count($params) === 0) {
            if (count($results) === 0) {
                $f->code .= chr(0x40);

                return;
            }
            if (count($results) === 1) {
                $f->code .= chr($results[0] & 0xFF);

                return;
            }
        }

        $tidx = $f->mod->newFnType($params, $results);
        $f->code .= Leb128::encodeI32($tidx);
    }

    private static function assertInt(string $name, int $typ): void
    {
        if ($typ !== Opcodes::T_I32 && $typ !== Opcodes::T_I64) {
            throw new \InvalidArgumentException("{$name}: only i32/i64 are permitted");
        }
    }

    /**
     * @param int|array<int> $opcode
     */
    private static function emit(FunctionState $f, int|array $opcode): void
    {
        $f->code .= Opcodes::bytes($opcode);
    }
}