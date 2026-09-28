<?php

namespace Kingbes\Wasm;

use Kingbes\Wasm\Base;
use Kingbes\Wasm\Wasm\FuncTypeState;

/**
 * 函数类型对象
 * @example ```php
 * use Kingbes\Wasm\FunType;
 * ```
 */
class FunType extends Base
{
    /**
     * 函数类型状态
     *
     * @var FuncTypeState
     */
    public FuncTypeState $data;

    /**
     * 构造函数
     *
     * @param array<ValType> $param 请求参数类型
     * @param array<ValType> $results 返回参数类型
     * @param string $type_name 类型名称
     * @example ```php
     * $fun_type = new FunType(
     *  [ValType::I32, ValType::I64], 
     *  [ValType::I32, ValType::I64], 
     *  "add");
     * ```
     */
    public function __construct(array $param, array $results, string $type_name = "")
    {
        $params = [];
        foreach ($param as $p) {
            $params[] = $p->data();
        }
        $rets = [];
        foreach ($results as $res) {
            $rets[] = $res->data();
        }
        $this->data = new FuncTypeState($params, $rets, $type_name);
    }
}