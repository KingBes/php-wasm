<?php

declare(strict_types=1);

namespace Kingbes\Wasm\Wasm;

/**
 * 常量表达式状态（对齐 V `wasm/constant.v`）。
 */
final class ConstExprState
{
    public string $code = '';

    /** @var Patch[] */
    public array $callPatches = [];

    public static function valueI32(int $v): self
    {
        $expr = new self();
        $expr->i32Const($v);

        return $expr;
    }

    public static function valueI64(int $v): self
    {
        $expr = new self();
        $expr->i64Const($v);

        return $expr;
    }

    public static function valueF32(float $v): self
    {
        $expr = new self();
        $expr->f32Const($v);

        return $expr;
    }

    public static function valueF64(float $v): self
    {
        $expr = new self();
        $expr->f64Const($v);

        return $expr;
    }

    /**
     * 零值常量表达式。
     */
    public static function zero(int $valType): self
    {
        $expr = new self();
        match ($valType) {
            Opcodes::T_I32 => $expr->i32Const(0),
            Opcodes::T_I64 => $expr->i64Const(0),
            Opcodes::T_F32 => $expr->f32Const(0.0),
            Opcodes::T_F64 => $expr->f64Const(0.0),
            Opcodes::FUNC_REF, Opcodes::EXTERN_REF => $expr->refNull($valType),
            Opcodes::V128 => throw new \InvalidArgumentException('type `v128` not permitted in a constant expression'),
            default => throw new \InvalidArgumentException("unknown value type: 0x{$valType}x"),
        };

        return $expr;
    }

    public static function refNullExpr(int $refType): self
    {
        $expr = new self();
        $expr->refNull($refType);

        return $expr;
    }

    public function i32Const(int $v): void
    {
        $this->code .= chr(0x41) . Leb128::encodeI32($v);
    }

    public function i64Const(int $v): void
    {
        $this->code .= chr(0x42) . Leb128::encodeI64($v);
    }

    public function f32Const(float $v): void
    {
        $this->code .= chr(0x43) . pack('g', $v);
    }

    public function f64Const(float $v): void
    {
        $this->code .= chr(0x44) . pack('e', $v);
    }

    public function add(int $numType): void
    {
        $this->intBinOp('add', $numType);
    }

    public function sub(int $numType): void
    {
        $this->intBinOp('sub', $numType);
    }

    public function mul(int $numType): void
    {
        $this->intBinOp('mul', $numType);
    }

    public function globalGet(int $importIdx): void
    {
        $this->code .= chr(0x23) . Leb128::encodeU32($importIdx);
    }

    public function refNull(int $refType): void
    {
        $this->code .= chr(0xD0) . chr($refType & 0xFF);
    }

    public function refFunc(string $name): void
    {
        $this->code .= chr(0xD2);
        $this->callPatches[] = Patch::function($name, strlen($this->code));
    }

    public function refFuncImport(string $mod, string $name): void
    {
        $this->code .= chr(0xD2);
        $this->callPatches[] = Patch::import($mod, $name, strlen($this->code));
    }

    private function intBinOp(string $op, int $numType): void
    {
        $map = match ($op) {
            'add' => [Opcodes::T_I32 => 0x6A, Opcodes::T_I64 => 0x7C],
            'sub' => [Opcodes::T_I32 => 0x6B, Opcodes::T_I64 => 0x7D],
            'mul' => [Opcodes::T_I32 => 0x6C, Opcodes::T_I64 => 0x7E],
        };
        if (!isset($map[$numType])) {
            throw new \InvalidArgumentException("{$op}: only i32/i64 are permitted in a constant expression");
        }
        $this->code .= chr($map[$numType]);
    }
}