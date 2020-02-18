<?php
error_reporting(0); //关闭所有报错

$Web_Host = 'http://demo.com/'; //主域名（需要包含http(s)://，以及域名后的斜杠/）

//数据库相关配置
$Mysql_Servername = '127.0.0.1'; //连接地址
$Mysql_Username = 'demo'; //用户名
$Mysql_Password = 'demo'; //密码
$Mysql_DBname = 'demo'; //数据库名
$Mysql_Tablename = 'dwz_url'; //表名

//Memcached相关配置
$Memcached_Switch = false; //是否开启Memcached缓存（true或false）
$Memcached_Host = '127.0.0.1'; //地址
$Memcached_Port = 11211; //端口
$Memcached_Timeout = 43200; //过期时间，单位秒，默认12小时，增大时间可减少数据库压力，但会增大内存消耗
$Memcached_AutoAdd = false; //添加短网址后立即进行缓存（否则只在有用户访问短网址才缓存）

function Get_IP() //获取用户来源IP

{
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } else if (getenv('HTTP_CDN_SRC_IP')) //判断是否有CDN前置
    {
        $ip = getenv('HTTP_CDN_SRC_IP');
    } else if (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } else if (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    } else {
        $ip = 'Unknown';
    }

    $arr = explode(',', $ip); //如果有多个IP只取第一个
    if (count($arr) >= 2) {
        $ip = $arr[0];
    }
    return $ip;

}

function OpMemcached($op, $key, $value = '') //Memcached操作函数

{
    global $Memcached_Host, $Memcached_Port, $Memcached_Timeout;
    $Memcached_obj = memcache_connect($Memcached_Host, $Memcached_Port); //初始化Memcached对象
    switch ($op) {
        case 'set': //设置K-V
            memcache_set($Memcached_obj, $key, $value, 0, $Memcached_Timeout);
            $flag = 'ok';
            break;
        case 'get': //获取V
            $flag = memcache_get($Memcached_obj, $key);
            break;
        default:
            $flag = 'error_op';
            break;
    }
    return $flag;
}
