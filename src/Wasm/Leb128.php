<?php

declare(strict_types=1);

namespace Kingbes\Wasm\Wasm;

/**
 * LEB128 变长整数编码（对齐 V `encoding.leb128`）。
 */
final class Leb128
{
    /**
     * 无符号 LEB128（u32）。
     */
    public static function encodeU32(int $v): string
    {
        $v &= 0xFFFFFFFF;
        $out = '';
        do {
            $byte = $v & 0x7F;
            $v >>= 7;
            if ($v !== 0) {
                $byte |= 0x80;
            }
            $out .= chr($byte);
        } while ($v !== 0);

        return $out;
    }

    /**
     * 有符号 LEB128（i32），入参先按 32 位有符号截断。
     */
    public static function encodeI32(int $v): string
    {
        $v &= 0xFFFFFFFF;
        if (($v & 0x80000000) !== 0) {
            $v -= 0x100000000;
        }

        return self::encodeSigned($v);
    }

    /**
     * 有符号 LEB128（i64）。
     */
    public static function encodeI64(int $v): string
    {
        return self::encodeSigned($v);
    }

    private static function encodeSigned(int $v): string
    {
        $out = '';
        while (true) {
            $byte = $v & 0x7F;
            $v >>= 7;
            $sign = $byte & 0x40;
            if (($v === 0 && $sign === 0) || ($v === -1 && $sign !== 0)) {
                $out .= chr($byte);
                break;
            }
            $out .= chr($byte | 0x80);
        }

        return $out;
    }
}