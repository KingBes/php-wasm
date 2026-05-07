<?php

// 严格模式
declare(strict_types=1);

namespace Kingbes\Wasm;

use \FFI;
use \FFI\CData;

abstract class Base
{
    protected static FFI $ffi;

    public static function ffi(): FFI
    {
        if (!isset(self::$ffi)) {
            $head = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "Wasm.h");
            self::$ffi = FFI::cdef($head, self::getLibFile());
        }
        return self::$ffi;
    }

    public static function getLibFile(): string
    {
        $uname = php_uname('m');
        if (in_array($uname, ['aarch64', 'arm64'])) {
            $arch = 'arm64';
        } elseif ($uname === 'x86_64') {
            $arch = 'x86_64';
        } else {
            throw new \RuntimeException("Unsupported architecture: $uname");
        }
        switch (PHP_OS_FAMILY) {
            case "Windows":
                $os = "windows";
                $suffix = "dll";
                break;
            case "Linux":
                $os = "linux";
                $suffix = "so";
                break;
            case "Darwin":
                $os = "macos";
                $suffix = "dylib";
                break;
            default:
                throw new \RuntimeException("Only supports Windows, Linux and Mac OS systems.");
        }
        $file = dirname(__DIR__)
            . DIRECTORY_SEPARATOR . "lib"
            . DIRECTORY_SEPARATOR . $os
            . DIRECTORY_SEPARATOR . $arch
            . DIRECTORY_SEPARATOR . "wasm." . $suffix;
        if (!file_exists($file)) {
            throw new \RuntimeException("Library file not found: $file, Please compile it by yourself.");

        }
        return $file;
    }

    /**
     * 创建c字符串数组
     *
     * @return CData
     */
    public function creatStrArr(): CData
    {
        return self::ffi()->help_new_arr_str($php_str);
    }

    /**
     * 追加字符串数组
     *
     * @param CData $arr c字符串数组
     * @param string $php_str 字符串
     * @return CData
     */
    public function addStrArr(CData $arr, string $php_str): CData
    {
        return self::ffi()->help_add_arr_str($arr, $php_str);
    }

    /**
     * 创建数值类型数组
     *
     * @return CData
     */
    public function creatValTypeArr(): CData
    {
        return self::ffi()->help_new_arr_val_type();
    }

    /**
     * 追加 值 类型数组
     *
     * @param CData $arr 数值类型数组
     * @param ValType $val_type 数值类型
     * @return CData
     */
    public function addValTypeArr(CData $arr, ValType $val_type): CData
    {
        return self::ffi()->help_add_arr_val_type($arr, $val_type->data());
    }

    /**
     * 创建 数值 类型数组
     *
     * @return CData
     */
    public function creatNumTypeArr(): CData
    {
        return self::ffi()->help_new_arr_num_type();
    }

    /**
     * 追加 数值 类型数组
     *
     * @param CData $arr 数值类型数组
     * @param NumType $num_type 数值类型
     * @return CData
     */
    public function addNumTypeArr(CData $arr, NumType $num_type): CData
    {
        return self::ffi()->help_add_arr_num_type($arr, $num_type->data());
    }
}
