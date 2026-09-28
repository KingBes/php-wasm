<?php

// 严格模式
declare(strict_types=1);

namespace Kingbes\Wasm;

abstract class Base
{
    /**
     * 创建字符串数组
     *
     * @return array<int,string>
     */
    public function creatStrArr(): array
    {
        return [];
    }

    /**
     * 追加字符串数组
     *
     * @param array<int,string> $arr 字符串数组
     * @param string $php_str 字符串
     * @return array<int,string>
     */
    public function addStrArr(array $arr, string $php_str): array
    {
        $arr[] = $php_str;
        return $arr;
    }

    /**
     * 创建值类型数组
     *
     * @return array<int,int>
     */
    public function creatValTypeArr(): array
    {
        return [];
    }

    /**
     * 追加值类型数组
     *
     * @param array<int,int> $arr 值类型数组
     * @param ValType $val_type 值类型
     * @return array<int,int>
     */
    public function addValTypeArr(array $arr, ValType $val_type): array
    {
        $arr[] = $val_type->data();
        return $arr;
    }

    /**
     * 创建数值类型数组
     *
     * @return array<int,int>
     */
    public function creatNumTypeArr(): array
    {
        return [];
    }

    /**
     * 追加数值类型数组
     *
     * @param array<int,int> $arr 数值类型数组
     * @param NumType $num_type 数值类型
     * @return array<int,int>
     */
    public function addNumTypeArr(array $arr, NumType $num_type): array
    {
        $arr[] = $num_type->data();
        return $arr;
    }
}