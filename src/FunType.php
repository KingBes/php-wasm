<?php

namespace Kingbes\Wasm;

use \FFI\CData;
use Kingbes\Wasm\Base;

/**
 * 函数类型对象
 * @example ```php
 * use Kingbes\Wasm\FunType;
 * ```
 */
class FunType extends Base
{
    /**
     * 数据指针
     *
     * @var CData
     */
    public CData $data;

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
        $c_param = $this->creatValTypeArr();
        foreach ($param as $p) {
            $c_param = $this->addValTypeArr($c_param, $p);
        }
        $c_results = $this->creatValTypeArr();
        foreach ($results as $res) {
            $c_results = $this->addValTypeArr($c_results, $res);
        }
        $this->data = self::ffi()->new_fn_type($c_param, $c_results, $type_name);
    }
}
