<?php

declare(strict_types=1);

namespace Kingbes\Wasm\Wasm;

/**
 * 字节缓冲：PHP 字符串即二进制安全的字节序列。
 */
final class ByteBuffer
{
    private string $buf = '';

    public function reset(): void
    {
        $this->buf = '';
    }

    public function append(string $bytes): void
    {
        $this->buf .= $bytes;
    }

    public function appendByte(int $byte): void
    {
        $this->buf .= chr($byte & 0xFF);
    }

    public function length(): int
    {
        return strlen($this->buf);
    }

    /**
     * 返回当前位置，供后续 patchLen / patchU32 回填。
     */
    public function patchStart(): int
    {
        return strlen($this->buf);
    }

    /**
     * 用「从 pos 到末尾」的长度回填到 pos 之前。
     */
    public function patchLen(int $pos): void
    {
        $this->insert($pos, Leb128::encodeU32(strlen($this->buf) - $pos));
    }

    /**
     * 用无符号 LEB128 编码的值回填到 pos 之前。
     */
    public function patchU32(int $pos, int $val): void
    {
        $this->insert($pos, Leb128::encodeU32($val));
    }

    public function insert(int $pos, string $bytes): void
    {
        $this->buf = substr($this->buf, 0, $pos) . $bytes . substr($this->buf, $pos);
    }

    public function bytes(): string
    {
        return $this->buf;
    }
}