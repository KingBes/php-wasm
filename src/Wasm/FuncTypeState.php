<?php

declare(strict_types=1);

namespace Kingbes\Wasm\Wasm;

/**
 * 函数类型（用于类型段驻留）。
 */
final class FuncTypeState
{
    /**
     * @param int[] $params ValType 规范字节
     * @param int[] $results ValType 规范字节
     */
    public function __construct(
        public array $params,
        public array $results,
        public ?string $name = null,
    ) {
    }

    /**
     * 值比较（含 name），对齐 V `Module.new_functype` 的数组 index 语义。
     */
    public function equals(self $other): bool
    {
        return $this->params === $other->params
            && $this->results === $other->results
            && $this->name === $other->name;
    }
}