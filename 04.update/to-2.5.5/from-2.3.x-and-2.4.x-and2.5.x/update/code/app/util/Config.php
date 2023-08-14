<?php
/*
 * @Author: witersen
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

class Config
{
    /**
     * 配置文件目录
     *
     * @var string
     */
    public static $_configPath = '';

    /**
     * 自动include
     *
     * @param string $configPath
     * @return void
     */
    public static function load($configPath)
    {
        self::$_configPath = $configPath;
    }

    /**
     * 获取配置信息value
     *
     * @param string $section
     * @param array $default
     * @return array
     */
    public static function get($section = null, $default = [])
    {
        if (is_file(self::$_configPath . $section . '.php')) {
            $config = include self::$_configPath . $section . '.php';
            return $config;
        }
        return $default;
    }
}
