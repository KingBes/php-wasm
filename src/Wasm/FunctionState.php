<?php

declare(strict_types=1);

namespace Kingbes\Wasm\Wasm;

/**
 * 单个函数的状态：代码字节、局部变量、索引补丁与标签栈。
 */
final class FunctionState
{
    public string $code = '';

    /** @var array<int,array{type:int,name:?string}> */
    public array $locals = [];

    /** @var Patch[] 始终保持按 pos 升序 */
    public array $patches = [];

    public int $label = 0;

    public bool $export = false;

    public ?string $exportName = null;

    public ?ModuleState $mod = null;

    public function __construct(
        public string $name,
        public int $tidx,
        public int $idx,
        public int $paramCount = 0,
    ) {
    }

    /**
     * 当前补丁位置。
     */
    public function patchPos(): int
    {
        return strlen($this->code);
    }

    /**
     * 把 `begin` 之后的代码搬到 `loc`，并同步调整既有补丁位置。
     *
     * 严格复刻 V `wasm/instructions.v` 的 `Function.patch`。
     */
    public function patch(int $loc, int $begin): void
    {
        if ($loc === $begin) {
            return;
        }
        if ($loc > $begin) {
            throw new \InvalidArgumentException("patch: loc {$loc} must be less than begin {$begin}");
        }

        $tail = substr($this->code, $begin);
        $this->code = substr($this->code, 0, $begin);
        $this->code = substr($this->code, 0, $loc) . $tail . substr($this->code, $loc);

        $tailLen = strlen($tail);
        foreach ($this->patches as $patch) {
            if ($patch->pos >= $begin) {
                $patch->pos -= $begin - $loc;
            } elseif ($patch->pos >= $loc) {
                $patch->pos += $tailLen;
            }
        }

        usort($this->patches, static fn (Patch $a, Patch $b): int => $a->pos <=> $b->pos);
    }
}