# 使用说明
- 输入网址会做正则校验，目前仅支持 http:// 或 https:// 开头
- 数据库操作已做预处理，防止SQL注入
- 数据库添加id索引，大量数据时查询更快
- 添加了Memcached缓存支持，可按需开启，减少数据库压力
- 短网址规则更改为36进制自动增加，避免md5重复的问题

# 环境要求
- Web环境：Apache或Nginx
- PHP (建议7.0+)
- 数据库：MySQL (MariaDB等分支版均可)
- 缓存：Memcached (可选项)

**如对性能要求较高可使用Nginx-Tengine和MariaDB并开启Memcached缓存**

# 安装方法

1. 修改`config.php`中的配置文件中的数据库信息和Memcached缓存信息(缓存按需开启)

2. 上传`index.php`和`config.php`到网站目录，并开启网址重写

3. 使用phpmyadmin或Navicat Premium导入执行源码中的dwz_url.sql，成功后数据库结构如下，其中id为索引

|id|url|ip|time|
|--|--|--|--|
|1|https://www.baidu.com|111.123.1.321|2020-2-18 12：00：00|


## 关于网址重写 

重写示例：
http://demo.com/123 (访问地址) -> http://demo.com/index.php?m=123 (实际地址)

**Apache**：直接上传.htaccess到根目录可开启

.htaccess内容如下

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule  ^([a-zA-Z0-9]*)(/?)$ index.php?m=$1 [L]
```

**Nginx：** 将.htaccess转换后填写到Nginx配置文件中

参考规则

```
if (!-d $request_filename){
 set $rule_0 1$rule_0;
 }
 if (!-f $request_filename){
 set $rule_0 2$rule_0;
 }
 if ($rule_0 = "21"){
 rewrite ^/(.*)$ /index.php/?m=$1 last;
 }
```

或者使用在线转换工具，如： [http://winginx.com/en/htaccess/](http://winginx.com/en/htaccess/)


# 完成！GLHF!
