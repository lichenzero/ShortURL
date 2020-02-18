<?php
require_once 'config.php'; //引入配置文件

$dwz = ''; //置空变量

$ip = Get_IP(); //获得IP地址

if (!empty($_GET['m'])) //进行短网址跳转
{
    $mid = $_GET['m']; //获得跳转地址ID
    if ($Memcached_Switch) //判断是否开启缓存
    {
        $url = OpMemcached('get', 'dwz_url_' . $mid); //读取缓存
        if ($url == '') {
            //缓存未命中
            $id = base_convert($mid, 36, 10); //将36进制转换回十进制
            $conn = new mysqli($Mysql_Servername, $Mysql_Username, $Mysql_Password, $Mysql_DBname); // 创建连接
            if ($conn->connect_error) { // 检测连接
                die('数据库连接错误！');
            }
            $stmt = $conn->prepare("SELECT url FROM $Mysql_Tablename WHERE id=?"); //根据十进制id查Mysql
            $stmt->bind_param('s', $id);
            $stmt->execute(); //执行SQL
            $result = $stmt->get_result(); //返回结果
            $stmt->close(); //关闭预处理
            $conn->close(); //关闭连接
            if ($result->num_rows > 0) //找到网址，重定向
            {
                $row = $result->fetch_assoc();
                $url = $row["url"];
                OpMemcached('set', 'dwz_url_' . $mid, $url); //更新缓存
                header("location: $url");
                exit;
            } else {
                //数据库中未找到
                die('ERROR, Not Found!');
            }
        } else {
            //缓存命中，直接跳转
            header("location: $url");
            exit;
        }
    } else {
        //未开启缓存，查询MySQL后跳转（同缓存未命中）
        $id = base_convert($mid, 36, 10);
        $conn = new mysqli($Mysql_Servername, $Mysql_Username, $Mysql_Password, $Mysql_DBname);
        if ($conn->connect_error) {
            die('数据库连接错误！');
        }
        $stmt = $conn->prepare("SELECT url FROM $Mysql_Tablename WHERE id=?"); //根据十进制id查Mysql
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $conn->close();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $url = $row["url"];
            header("location: $url");
            exit;
        } else {
            die('ERROR, Not Found!');
        }
    }
}

if (!empty($_POST['url'])) //进行短网址新增
{
    $url = $_POST['url'];
    if (!preg_match("/^((https|http|ftp)?:\/\/)[^\s]+$/", $url)) //网址格式正则校验
    {
        $dwz = '格式错误，网址需加http(s)://';
    } else {
        $conn = new mysqli($Mysql_Servername, $Mysql_Username, $Mysql_Password, $Mysql_DBname);
        if ($conn->connect_error) {
            die('数据库连接错误！');
        }
        $stmt = $conn->prepare("SELECT url FROM $Mysql_Tablename WHERE url=?"); //查询此网址是否存在
        $stmt->bind_param('s', $url);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            //存在直接输出
            $row = $result->fetch_assoc();
            $dwz = '短网址：' . $Web_Host . base_convert($row['id'], 10, 36);
        } else {
            //不存在创建短网址后输出
            $stmt = $conn->prepare("INSERT INTO $Mysql_Tablename (url, time, ip) VALUES (?, now(), ?)"); //创建
            $stmt->bind_param('ss', $url, $ip);
            $stmt->execute();
            $stmt->close();
            $stmt = $conn->prepare("SELECT id FROM $Mysql_Tablename WHERE url=?"); //查询创建结果（id）
            $stmt->bind_param('s', $url);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            $conn->close();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $mid = base_convert($row['id'], 10, 36); //id转换为36进制
                if ($Memcached_Switch && $Memcached_AutoAdd) //判断缓存是否开启，是否需要立即添加
                {
                    OpMemcached('set', 'dwz_url_' . $mid, $url); //添加缓存
                }
                $dwz = '短网址：' . $Web_Host . $mid;
            } else {
                $dwz = '建立短网址失败！';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Language" content="zh-CN">
<title>短网址</title>
<style>
        html {
            background: #105c9a url(/Public/Web/img/bg.png) repeat-x scroll 0 -450px;
            overflow-y: scroll
        }

        body,
        h1 {
            margin: 0;
            padding: 0
        }

        body {
            min-width: 900px;
            max-height: 358px;
            padding: 20px 0;
            font: 12px/1.62 Georgia, tahoma, arial, \5b8b\4f53;
            background: url(/Public/Web/img/bg.png) no-repeat center 0;
            _background: none
        }

        a {
            text-decoration: none
        }

        h1 {
            width: 204px;
            margin: 0 auto 10px;
            *margin-bottom: 0;
        }

        h1 a {
            color: white;
            font-size: 48px;
            font-weight: normal
        }

        h1 strong {
            position: relative;
            top: -2px;
            left: 5px;
            font-size: 40px;
            font-weight: normal;
            font-family: Microsoft YaHei, simhei
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: bold
        }

        input {
            width: 521px;
            height: 16px;
            padding: 7px 5px;
            font-size: 14px;
            font-weight: bold;
            font-family: Georgia, tahoma, arial, \5b8b\4f53;
            border: solid 1px #333;
            border-radius: 4px;
            background: #e8f4fc;
            outline: none;
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.27), 1px 1px 2px rgba(0, 0, 0, 0.443) inset
        }

        input:focus {
            background: #fff;
        }

        button {
            height: 44px;
            padding: 0 35px 4px;
            *padding: 0 17px;
            color: white;
            font-size: 23px;
            font-family: Microsoft YaHei, simhei;
            background: #ce1171 url(/Public/Web/img/bg.png) 0 -400px;
            border: solid 2px white;
            border-radius: 5px;
            cursor: pointer
        }

        textarea::-webkit-input-placeholder {
            text-align: center;
            padding-top: 70px
        }

        .section {
            width: 700px;
            margin: 0 auto;
            color: white;
        }

        .success {
            position: relative;
            font-size: 14px;
            word-break: break-all;
            word-wrap: break-word
        }

        .success a {
            color: white
        }

        .foot {
            color: #7EBFDC;
            text-align: center
        }

        .foot a {
            color: #7EBFDC
        }

        .ad {
            width: 728px;
            margin: 110px auto 0 -96px;
        }

        .text {
            width: 700px;
            display: block;
            margin: 0 auto 25px auto;
            height: 200px;
            resize: none;
            font-size: 20px;
            border-color: rgba(204, 204, 204, 1);
            border-radius: 5px;
        }

        .url {
            margin: 15px 0 12px
        }

        .url label {
            margin-bottom: 3px
        }

        .alias label {
            display: inline;
            margin-right: 4px
        }

        .alias input {
            width: 80px;
            margin: 0 3px;
            padding: 3px
        }

        .shorten {
            text-align: right;
            margin: 10px 5px 50px
        }

        .n {
            width: 200px;
            padding: 5px 3px;
            border: 0;
            border-radius: 0;
            box-shadow: 0 0 0 0;
        }

        .p {
            position: absolute;
            display: none;
            width: 122px;
            margin-top: -56px;
            *margin-top: -29px;
            left: 49px;
            padding: 2px 8px 3px;
            border: solid 1px #0f619d;
            background: #dbdbdb;
            color: #f00;
            text-shadow: 1px 1px 1px #fff;
            box-shadow: 0 1px 2px #0f619d;
            border-radius: 0
        }

        .p span {
            position: absolute;
            display: block;
            width: 0;
            height: 0;
            _overflow: hidden;
            top: 28px;
            left: 61px;
            border: solid 6px #0f619d;
            border-color: #0f619d transparent;
            border-width: 6px 6px 0
        }

        .f .p {
            display: block
        }

        .p1 {
            font-size: 16px;
            font-weight: 700;
        }

        .warp {
            display: block;
            width: 800px;
            margin: 0 auto;
            color: #000;
            margin-bottom: 50px;
            color: #fff;
        }

        .warp p {
            font-size: 14px;
            line-height: 16px;
        }

        .warp > div {
            margin-bottom: 30px;
        }
</style>
</head>
<body>
	<div class="section">
    <h1>
        <strong>
			<a href="./">网址缩短</a>
        </strong>
    </h1>
    <form action="./" method="post">
        <div>
            <p class="p1">输入将要缩短的长网址:</p>
            <textarea class="text" id="url" name="url"></textarea>
        </div>
        <div class="shorten">
            <button type="submit">生 成</button>
        </div>
	</form>
	<?php
if ($dwz != '') {
    echo "<h1>$dwz</h1>";
}
?>
    </div>
</body>
</html>
