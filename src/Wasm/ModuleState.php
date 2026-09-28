<?php

declare(strict_types=1);

namespace Kingbes\Wasm\Wasm;

/**
 * 模块状态与 `compile()` 序列化（对齐 V `wasm/module.v` + `wasm/encoding.v`）。
 */
final class ModuleState
{
    private const SECTION_CUSTOM = 0;
    private const SECTION_TYPE = 1;
    private const SECTION_IMPORT = 2;
    private const SECTION_FUNCTION = 3;
    private const SECTION_TABLE = 4;
    private const SECTION_MEMORY = 5;
    private const SECTION_GLOBAL = 6;
    private const SECTION_EXPORT = 7;
    private const SECTION_START = 8;
    private const SECTION_ELEMENT = 9;
    private const SECTION_CODE = 10;
    private const SECTION_DATA = 11;
    private const SECTION_DATA_COUNT = 12;

    private const SUB_MODULE = 0;
    private const SUB_FUNCTION = 1;
    private const SUB_LOCAL = 2;
    private const SUB_TYPE = 4;
    private const SUB_MEMORY = 6;
    private const SUB_GLOBAL = 7;
    private const SUB_DATA = 9;

    public const MODE_ACTIVE = 'active';
    public const MODE_DECLARATIVE = 'declarative';
    public const MODE_PASSIVE = 'passive';

    private ByteBuffer $buf;

    /** @var FuncTypeState[] */
    public array $funcTypes = [];

    /** @var array<string,FunctionState> 按注册顺序插入 */
    public array $functions = [];

    /** @var array<int,array{typ:int,is_mut:bool,name:string,export:bool,init:ConstExprState}> */
    public array $globals = [];

    /** @var array{name:string,export:bool,min:int,max:?int}|null */
    public ?array $memory = null;

    public ?string $start = null;

    /** @var array<int,array{mod:string,name:string,tidx:int}> */
    public array $fnImports = [];

    /** @var array<int,array{mod:string,name:string,typ:int,is_mut:bool}> */
    public array $globalImports = [];

    /** @var array<int,array{idx:?int,data:string,name:string}> */
    public array $segments = [];

    /** @var array<int,array{name:string,export:bool,reftype:int,min:int,max:?int}> */
    public array $tables = [];

    /** @var array<int,array{mode:string,tableidx:int,offset:int,funcs:string[]}> */
    public array $elements = [];

    public bool $debug = false;

    public ?string $modName = null;

    public function __construct()
    {
        $this->buf = new ByteBuffer();
    }

    // ==================== 类型 ====================

    /**
     * 类型驻留：命中已有类型返回其索引，否则追加。
     *
     * @param int[] $params ValType 规范字节
     * @param int[] $results ValType 规范字节
     */
    public function newFnType(array $params, array $results, ?string $name = null): int
    {
        $candidate = new FuncTypeState($params, $results, $name);
        foreach ($this->funcTypes as $idx => $existing) {
            if ($existing->equals($candidate)) {
                return $idx;
            }
        }
        $this->funcTypes[] = $candidate;

        return count($this->funcTypes) - 1;
    }

    // ==================== 函数 ====================

    /**
     * @param int[] $params
     * @param int[] $results
     */
    public function newFunction(string $name, array $params, array $results): FunctionState
    {
        if (isset($this->functions[$name])) {
            throw new \InvalidArgumentException("function {$name} already exists");
        }

        $idx = count($this->functions);
        $tidx = $this->newFnType($params, $results);

        $ft = new FunctionState($name, $tidx, $idx, count($params));
        $ft->mod = $this;
        foreach ($params as $_) {
            $ft->locals[] = ['type' => 0, 'name' => null];
        }

        $this->functions[$name] = $ft;

        return $ft;
    }

    /**
     * @param string[] $argumentNames
     */
    public function newDebugFunction(string $name, FuncTypeState $typ, array $argumentNames): FunctionState
    {
        if (isset($this->functions[$name])) {
            throw new \InvalidArgumentException("function {$name} already exists");
        }
        if (count($typ->params) !== count($argumentNames)) {
            throw new \InvalidArgumentException(
                "new_debug_function: argument_names length must match the function parameters"
            );
        }

        $idx = count($this->functions);
        $tidx = $this->newFnType($typ->params, $typ->results, $typ->name);

        $ft = new FunctionState($name, $tidx, $idx, count($typ->params));
        $ft->mod = $this;
        foreach ($argumentNames as $argumentName) {
            $ft->locals[] = ['type' => 0, 'name' => $argumentName];
        }

        $this->functions[$name] = $ft;

        return $ft;
    }

    /**
     * @param int[] $params
     * @param int[] $results
     */
    public function newFunctionImport(string $mod, string $name, array $params, array $results): void
    {
        foreach ($this->fnImports as $imp) {
            if ($imp['mod'] === $mod && $imp['name'] === $name) {
                throw new \InvalidArgumentException("import {$mod}.{$name} already exists");
            }
        }

        $tidx = $this->newFnType($params, $results);
        $this->fnImports[] = ['mod' => $mod, 'name' => $name, 'tidx' => $tidx];
    }

    public function newFunctionImportDebug(string $mod, string $name, FuncTypeState $typ): void
    {
        foreach ($this->fnImports as $imp) {
            if ($imp['mod'] === $mod && $imp['name'] === $name) {
                throw new \InvalidArgumentException("import {$mod}.{$name} already exists");
            }
        }

        $tidx = $this->newFnType($typ->params, $typ->results, $typ->name);
        $this->fnImports[] = ['mod' => $mod, 'name' => $name, 'tidx' => $tidx];
    }

    /**
     * 提交函数：仅设置是否导出（函数在创建时已按顺序登记）。
     */
    public function commit(FunctionState $ft, bool $export): void
    {
        $ft->export = $export;
    }

    // ==================== 全局变量 ====================

    public function newGlobal(string $name, bool $export, int $typ, bool $isMut, ConstExprState $init): int
    {
        $idx = count($this->globals);
        $this->globals[] = [
            'typ' => $typ,
            'is_mut' => $isMut,
            'name' => $name,
            'export' => $export,
            'init' => $init,
        ];

        return $idx;
    }

    public function newGlobalImport(string $mod, string $name, int $typ, bool $isMut): int
    {
        foreach ($this->globalImports as $imp) {
            if ($imp['mod'] === $mod && $imp['name'] === $name) {
                throw new \InvalidArgumentException("global import {$mod}.{$name} already exists");
            }
        }

        $idx = count($this->globalImports);
        $this->globalImports[] = ['mod' => $mod, 'name' => $name, 'typ' => $typ, 'is_mut' => $isMut];

        return $idx;
    }

    public function assignGlobalInit(int $index, ConstExprState $init): void
    {
        $this->globals[$index]['init'] = $init;
    }

    // ==================== 内存 / 表 / 元素 / 数据段 ====================

    public function assignMemory(string $name, bool $export, int $min, ?int $max): void
    {
        $this->memory = ['name' => $name, 'export' => $export, 'min' => $min, 'max' => $max];
    }

    public function assignStart(string $name): void
    {
        $this->start = $name;
    }

    public function assignTable(string $name, bool $export, int $reftype, int $min, ?int $max): int
    {
        $idx = count($this->tables);
        $this->tables[] = [
            'name' => $name,
            'export' => $export,
            'reftype' => $reftype,
            'min' => $min,
            'max' => $max,
        ];

        return $idx;
    }

    /**
     * @param string[] $funcs
     */
    public function newActiveElement(int $tableIdx, int $offset, array $funcs): int
    {
        $idx = count($this->elements);
        $this->elements[] = [
            'mode' => self::MODE_ACTIVE,
            'tableidx' => $tableIdx,
            'offset' => $offset,
            'funcs' => $funcs,
        ];

        return $idx;
    }

    /**
     * @param string[] $funcs
     */
    public function newDeclarativeElement(array $funcs): int
    {
        $idx = count($this->elements);
        $this->elements[] = [
            'mode' => self::MODE_DECLARATIVE,
            'tableidx' => 0,
            'offset' => 0,
            'funcs' => $funcs,
        ];

        return $idx;
    }

    public function newDataSegment(string $name, int $pos, string $data): int
    {
        $idx = count($this->segments);
        $this->segments[] = ['idx' => $pos, 'data' => $data, 'name' => $name];

        return $idx;
    }

    public function newPassiveDataSegment(string $name, string $data): void
    {
        $this->segments[] = ['idx' => null, 'data' => $data, 'name' => $name];
    }

    public function enableDebug(string $name): void
    {
        $this->debug = true;
        $this->modName = $name;
    }

    // ==================== 序列化 ====================

    /**
     * 把模块序列化为 Wasm 字节。
     */
    public function compile(): string
    {
        $b = $this->buf;
        $b->reset();

        // magic + version
        $b->append("\x00asm\x01\x00\x00\x00");

        // type(1)
        if (count($this->funcTypes) > 0) {
            $tpatch = $this->startSection(self::SECTION_TYPE);
            $b->append(Leb128::encodeU32(count($this->funcTypes)));
            foreach ($this->funcTypes as $ft) {
                $this->functionType($ft);
            }
            $this->endSection($tpatch);
        }

        // import(2)
        if (count($this->fnImports) > 0 || count($this->globalImports) > 0) {
            $tpatch = $this->startSection(self::SECTION_IMPORT);
            $b->append(Leb128::encodeU32(count($this->fnImports) + count($this->globalImports)));
            foreach ($this->fnImports as $imp) {
                $this->name($imp['mod']);
                $this->name($imp['name']);
                $b->appendByte(0x00);
                $b->append(Leb128::encodeU32($imp['tidx']));
            }
            foreach ($this->globalImports as $imp) {
                $this->name($imp['mod']);
                $this->name($imp['name']);
                $b->appendByte(0x03);
                $this->globalType($imp['typ'], $imp['is_mut']);
            }
            $this->endSection($tpatch);
        }

        // function(3)
        if (count($this->functions) > 0) {
            $tpatch = $this->startSection(self::SECTION_FUNCTION);
            $b->append(Leb128::encodeU32(count($this->functions)));
            foreach ($this->functions as $ft) {
                $b->append(Leb128::encodeU32($ft->tidx));
            }
            $this->endSection($tpatch);
        }

        // table(4)
        if (count($this->tables) > 0) {
            $tpatch = $this->startSection(self::SECTION_TABLE);
            $b->append(Leb128::encodeU32(count($this->tables)));
            foreach ($this->tables as $tbl) {
                $b->appendByte($tbl['reftype']);
                $this->limits($tbl['min'], $tbl['max']);
            }
            $this->endSection($tpatch);
        }

        // memory(5)
        if ($this->memory !== null) {
            $tpatch = $this->startSection(self::SECTION_MEMORY);
            $b->append(Leb128::encodeU32(1));
            $this->limits($this->memory['min'], $this->memory['max']);
            $this->endSection($tpatch);
        }

        // global(6)
        if (count($this->globals) > 0) {
            $tpatch = $this->startSection(self::SECTION_GLOBAL);
            $b->append(Leb128::encodeU32(count($this->globals)));
            foreach ($this->globals as $gbl) {
                $this->globalType($gbl['typ'], $gbl['is_mut']);
                $this->constExprBody($gbl['init']);
                $b->appendByte(0x0B);
            }
            $this->endSection($tpatch);
        }

        // export(7) — 无条件输出
        {
            $tpatch = $this->startSection(self::SECTION_EXPORT);
            $lpatch = $b->patchStart();
            $lsz = 0;
            foreach ($this->functions as $ft) {
                if (!$ft->export) {
                    continue;
                }
                $lsz++;
                $this->name($ft->exportName ?? $ft->name);
                $b->appendByte(0x00);
                $b->append(Leb128::encodeU32($ft->idx + count($this->fnImports)));
            }
            if ($this->memory !== null && $this->memory['export']) {
                $lsz++;
                $this->name($this->memory['name']);
                $b->appendByte(0x02);
                $b->append(Leb128::encodeU32(0));
            }
            foreach ($this->tables as $tblIdx => $tbl) {
                if (!$tbl['export']) {
                    continue;
                }
                $lsz++;
                $this->name($tbl['name']);
                $b->appendByte(0x01);
                $b->append(Leb128::encodeU32($tblIdx));
            }
            foreach ($this->globals as $idx => $gbl) {
                if (!$gbl['export']) {
                    continue;
                }
                $lsz++;
                $this->name($gbl['name']);
                $b->appendByte(0x03);
                $b->append(Leb128::encodeU32($idx + count($this->globalImports)));
            }
            $b->patchU32($lpatch, $lsz);
            $this->endSection($tpatch);
        }

        // start(8)
        if ($this->start !== null) {
            $ft = $this->getLocalFunction($this->start);
            $tpatch = $this->startSection(self::SECTION_START);
            $b->append(Leb128::encodeU32($ft->idx + count($this->fnImports)));
            $this->endSection($tpatch);
        }

        // element(9)
        if (count($this->elements) > 0) {
            $tpatch = $this->startSection(self::SECTION_ELEMENT);
            $b->append(Leb128::encodeU32(count($this->elements)));
            foreach ($this->elements as $el) {
                if ($el['mode'] === self::MODE_ACTIVE) {
                    if ($el['tableidx'] === 0) {
                        $b->appendByte(0x00);
                        $b->appendByte(0x41);
                        $b->append(Leb128::encodeI32($el['offset']));
                        $b->appendByte(0x0B);
                    } else {
                        $b->appendByte(0x02);
                        $b->append(Leb128::encodeU32($el['tableidx']));
                        $b->appendByte(0x41);
                        $b->append(Leb128::encodeI32($el['offset']));
                        $b->appendByte(0x0B);
                        $b->appendByte(0x00);
                    }
                } elseif ($el['mode'] === self::MODE_DECLARATIVE) {
                    $b->appendByte(0x03);
                    $b->appendByte(0x00);
                } else {
                    $b->appendByte(0x01);
                    $b->appendByte(0x00);
                }

                $b->append(Leb128::encodeU32(count($el['funcs'])));
                foreach ($el['funcs'] as $fnName) {
                    $b->append(Leb128::encodeU32($this->getLocalFuncIdx($fnName)));
                }
            }
            $this->endSection($tpatch);
        }

        // data count(12) — 必须在 code 段之前
        if (count($this->segments) > 0) {
            $tpatch = $this->startSection(self::SECTION_DATA_COUNT);
            $b->append(Leb128::encodeU32(count($this->segments)));
            $this->endSection($tpatch);
        }

        // code(10)
        if (count($this->functions) > 0) {
            $tpatch = $this->startSection(self::SECTION_CODE);
            $b->append(Leb128::encodeU32(count($this->functions)));
            foreach ($this->functions as $ft) {
                $fpatch = $b->patchStart();
                $paramCount = count($this->funcTypes[$ft->tidx]->params);
                $rloc = array_slice($ft->locals, $paramCount);
                $b->append(Leb128::encodeU32(count($rloc)));
                foreach ($rloc as $local) {
                    $b->append(Leb128::encodeU32(1));
                    $b->appendByte($local['type']);
                }
                $this->patchFunction($ft);
                $b->appendByte(0x0B);
                $b->patchLen($fpatch);
            }
            $this->endSection($tpatch);
        }

        // data(11)
        if (count($this->segments) > 0) {
            $tpatch = $this->startSection(self::SECTION_DATA);
            $b->append(Leb128::encodeU32(count($this->segments)));
            foreach ($this->segments as $seg) {
                if ($seg['idx'] !== null) {
                    $b->appendByte(0x00);
                    $b->appendByte(0x41);
                    $b->append(Leb128::encodeI32($seg['idx']));
                    $b->appendByte(0x0B);
                } else {
                    $b->appendByte(0x01);
                }
                $b->append(Leb128::encodeU32(strlen($seg['data'])));
                $b->append($seg['data']);
            }
            $this->endSection($tpatch);
        }

        // custom name section(0)
        if ($this->debug) {
            $this->compileNameSection();
        }

        return $b->bytes();
    }

    private function compileNameSection(): void
    {
        $b = $this->buf;
        $tpatch = $this->startSection(self::SECTION_CUSTOM);
        $this->name('name');

        // module(0)
        if ($this->modName !== null) {
            $mpatch = $this->startSubsection(self::SUB_MODULE);
            $this->name($this->modName);
            $this->endSection($mpatch);
        }

        // function(1)
        {
            $mpatch = $this->startSubsection(self::SUB_FUNCTION);
            $b->append(Leb128::encodeU32(count($this->functions) + count($this->fnImports)));
            $idx = 0;
            foreach ($this->fnImports as $imp) {
                $b->append(Leb128::encodeU32($idx));
                $this->name($imp['mod'] . '.' . $imp['name']);
                $idx++;
            }
            foreach ($this->functions as $fnName => $_) {
                $b->append(Leb128::encodeU32($idx));
                $this->name($fnName);
                $idx++;
            }
            $this->endSection($mpatch);
        }

        // local(2)
        {
            $mpatch = $this->startSubsection(self::SUB_LOCAL);
            $fpatch = $b->patchStart();
            $fcount = 0;
            $idx = count($this->fnImports);
            foreach ($this->functions as $ft) {
                if ($this->hasNamedLocal($ft)) {
                    $b->append(Leb128::encodeU32($idx));
                    $lcpatch = $b->patchStart();
                    $lcount = 0;
                    foreach ($ft->locals as $lidx => $local) {
                        if ($local['name'] !== null) {
                            $b->append(Leb128::encodeU32($lidx));
                            $this->name($local['name']);
                            $lcount++;
                        }
                    }
                    $b->patchU32($lcpatch, $lcount);
                    $fcount++;
                }
                $idx++;
            }
            $b->patchU32($fpatch, $fcount);
            $this->endSection($mpatch);
        }

        // type(4)
        {
            $mpatch = $this->startSubsection(self::SUB_TYPE);
            $fpatch = $b->patchStart();
            $fcount = 0;
            foreach ($this->funcTypes as $idx => $ft) {
                if ($ft->name !== null) {
                    $b->append(Leb128::encodeU32($idx));
                    $this->name($ft->name);
                    $fcount++;
                }
            }
            $b->patchU32($fpatch, $fcount);
            $this->endSection($mpatch);
        }

        // memory(6)
        if ($this->memory !== null) {
            $mpatch = $this->startSubsection(self::SUB_MEMORY);
            $b->append(Leb128::encodeU32(1));
            $b->append(Leb128::encodeU32(0));
            $this->name($this->memory['name']);
            $this->endSection($mpatch);
        }

        // global(7)
        if (count($this->globals) !== 0 || count($this->globalImports) !== 0) {
            $mpatch = $this->startSubsection(self::SUB_GLOBAL);
            $fpatch = $b->patchStart();
            $fcount = 0;
            foreach ($this->globalImports as $imp) {
                $b->append(Leb128::encodeU32($fcount));
                $this->name($imp['mod'] . '.' . $imp['name']);
                $fcount++;
            }
            foreach ($this->globals as $gbl) {
                $b->append(Leb128::encodeU32($fcount));
                $this->name($gbl['name']);
                $fcount++;
            }
            $b->patchU32($fpatch, $fcount);
            $this->endSection($mpatch);
        }

        // data(9)
        if ($this->hasNamedSegment()) {
            $mpatch = $this->startSubsection(self::SUB_DATA);
            $fpatch = $b->patchStart();
            $fcount = 0;
            foreach ($this->segments as $idx => $seg) {
                if ($seg['name'] !== '') {
                    $b->append(Leb128::encodeU32($idx));
                    $this->name($seg['name']);
                    $fcount++;
                }
            }
            $b->patchU32($fpatch, $fcount);
            $this->endSection($mpatch);
        }

        $this->endSection($tpatch);
    }

    // ==================== 内部辅助 ====================

    private function startSection(int $section): int
    {
        $this->buf->appendByte($section);

        return $this->buf->patchStart();
    }

    private function startSubsection(int $subsection): int
    {
        $this->buf->appendByte($subsection);

        return $this->buf->patchStart();
    }

    private function endSection(int $patch): void
    {
        $this->buf->patchLen($patch);
    }

    private function name(string $name): void
    {
        $this->buf->append(Leb128::encodeU32(strlen($name)));
        $this->buf->append($name);
    }

    private function limits(int $min, ?int $max): void
    {
        if ($max !== null) {
            $this->buf->appendByte(0x01);
            $this->buf->append(Leb128::encodeU32($min));
            $this->buf->append(Leb128::encodeU32($max));
        } else {
            $this->buf->appendByte(0x00);
            $this->buf->append(Leb128::encodeU32($min));
        }
    }

    private function functionType(FuncTypeState $ft): void
    {
        $this->buf->appendByte(0x60);
        $this->resultType($ft->params);
        $this->resultType($ft->results);
    }

    /**
     * @param int[] $types
     */
    private function resultType(array $types): void
    {
        $this->buf->append(Leb128::encodeU32(count($types)));
        foreach ($types as $type) {
            $this->buf->appendByte($type);
        }
    }

    private function globalType(int $typ, bool $isMut): void
    {
        $this->buf->appendByte($typ);
        $this->buf->appendByte($isMut ? 1 : 0);
    }

    private function constExprBody(ConstExprState $expr): void
    {
        $ptr = 0;
        foreach ($expr->callPatches as $patch) {
            $idx = $this->getFunctionIdx($patch);
            $this->buf->append(substr($expr->code, $ptr, $patch->pos - $ptr));
            $this->buf->append(Leb128::encodeU32($idx));
            $ptr = $patch->pos;
        }
        $this->buf->append(substr($expr->code, $ptr));
    }

    private function patchFunction(FunctionState $ft): void
    {
        $ptr = 0;
        foreach ($ft->patches as $patch) {
            $idx = $patch->kind === Patch::GLOBAL
                ? count($this->globalImports) + $patch->idx
                : $this->getFunctionIdx($patch);

            $this->buf->append(substr($ft->code, $ptr, $patch->pos - $ptr));
            $this->buf->append(Leb128::encodeU32($idx));
            $ptr = $patch->pos;
        }
        $this->buf->append(substr($ft->code, $ptr));
    }

    private function getFunctionIdx(Patch $patch): int
    {
        if ($patch->kind === Patch::FUNCTION) {
            return $this->getLocalFuncIdx($patch->name);
        }

        foreach ($this->fnImports as $idx => $imp) {
            if ($imp['mod'] === $patch->mod && $imp['name'] === $patch->name) {
                return $idx;
            }
        }

        throw new \RuntimeException("called imported function {$patch->mod}.{$patch->name} does not exist");
    }

    private function getLocalFuncIdx(string $name): int
    {
        return $this->getLocalFunction($name)->idx + count($this->fnImports);
    }

    private function getLocalFunction(string $name): FunctionState
    {
        if (!isset($this->functions[$name])) {
            throw new \RuntimeException("function {$name} does not exist");
        }

        return $this->functions[$name];
    }

    private function hasNamedLocal(FunctionState $ft): bool
    {
        foreach ($ft->locals as $local) {
            if ($local['name'] !== null) {
                return true;
            }
        }

        return false;
    }

    private function hasNamedSegment(): bool
    {
        foreach ($this->segments as $seg) {
            if ($seg['name'] !== '') {
                return true;
            }
        }

        return false;
    }
}