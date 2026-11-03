<?php
/**
 * 发卡安装程序
 *
 * 安装完成后建议删除此文件
 * @author Mr zhang
 */
// 定义目录分隔符
define('DS', DIRECTORY_SEPARATOR);

// 定义根目录
define('ROOT_PATH', __DIR__ . '/../');
// 定义应用目录
define('APP_PATH', ROOT_PATH . 'public' . DS);

// 安装包目录
define('INSTALL_PATH', ROOT_PATH . 'data' . DS );




// 判断文件或目录是否有写的权限
function is_really_writable($file)
{
    if (DIRECTORY_SEPARATOR == '/' AND @ ini_get("safe_mode") == FALSE)
    {
        return is_writable($file);
    }
    if (!is_file($file) OR ( $fp = @fopen($file, "r+")) === FALSE)
    {
        return FALSE;
    }

    fclose($fp);
    return TRUE;
}

$sitename = "分销版系统";

//错误信息
$errInfo = '';

// 数据库配置：写在项目根（Web 根 public/ 之外），避免 HTTP 直链泄露
$dbConfigFile = ROOT_PATH . 'conn.php';
// 锁定的文件
$lockFile = INSTALL_PATH . 'install.lock';
if (is_file($lockFile))
{
    $errInfo = "当前已经安装{$sitename}，如果需要重新安装，请手动移除/data/install.lock文件";
}
else if (version_compare(PHP_VERSION, '7.4.0', '<'))
{
    $errInfo = "当前版本(" . PHP_VERSION . ")过低，请使用PHP7.4或以上版本，官方建议php7.4";
}
else if (!extension_loaded("PDO"))
{
    $errInfo = "当前未开启PDO，无法进行安装";
}
else if (!is_really_writable($dbConfigFile))
{
    $errInfo = "当前权限不足，无法写入项目根目录/conn.php（须在 public 之外）";

}
// 当前是POST请求
if (!$errInfo && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST')
{
    $err = '';
    $mysqlHostname = isset($_POST['mysqlHost']) ? $_POST['mysqlHost'] : 'localhost';
    $hostArr = explode(':', $mysqlHostname);
    if (count($hostArr) > 1)
    {
        $mysqlHostname = $hostArr[0];
        $mysqlHostport = $hostArr[1];
    }else{
		$mysqlHostport = 3306;
	}
    $mysqlUsername = isset($_POST['mysqlUsername']) ? $_POST['mysqlUsername'] : 'root';
    $mysqlPassword = isset($_POST['mysqlPassword']) ? $_POST['mysqlPassword'] : '';
    $mysqlDatabase = isset($_POST['mysqlDatabase']) ? $_POST['mysqlDatabase'] : 'blfk';
    $adminUsername = isset($_POST['adminUsername']) ? $_POST['adminUsername'] : 'admin';
    $adminPassword = isset($_POST['adminPassword']) ? $_POST['adminPassword'] : '123456';
    $adminPasswordConfirmation = isset($_POST['adminPasswordConfirmation']) ? $_POST['adminPasswordConfirmation'] : '123456';
    $adminEmail = isset($_POST['adminEmail']) ? $_POST['adminEmail'] : 'admin@admin.com';

    if ($adminPassword !== $adminPasswordConfirmation)
    {
        echo "两次输入的密码不一致";
        exit;
    }
    else if (!preg_match("/^\w+$/", $adminUsername))
    {
        echo "用户名只能输入字母、数字、下划线";
        exit;
    }
    else if (!preg_match("/^[\S]+$/", $adminPassword))
    {
        echo "密码不能包含空格";
        exit;
    }
    else if (strlen($adminUsername) < 3 || strlen($adminUsername) > 12)
    {
        echo "用户名请输入3~12位字符";
        exit;
    }
    else if (strlen($adminPassword) < 6 || strlen($adminPassword) > 16)
    {

        echo "密码请输入6~16位字符";
        exit;
    }
    try
    {
        //检测能否读取安装文件
        $sql = @file_get_contents(INSTALL_PATH . 'fxfk.sql');
        if (!$sql)
        {
            throw new Exception("无法读取/data/fxfk.sql文件，请检查是否有读权限");
        }
        $pdo = new PDO("mysql:host={$mysqlHostname};port={$mysqlHostport}", $mysqlUsername, $mysqlPassword, array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ));

        $pdo->query("CREATE DATABASE IF NOT EXISTS `{$mysqlDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");

        $pdo->query("USE `{$mysqlDatabase}`");

        $pdo->exec($sql);

        $conphp = "<?php
					error_reporting(0);
					ini_set('display_errors','off');
					define('DECRYPT_KEY', '".md5(time() . mt_rand(0,1000))."');
					header('Content-Type: text/html;charset=utf-8');
					\$DB_HOSTNAME='".$mysqlHostname."';// 服务器地址
					\$DB_hostport='".$mysqlHostport."';// 数据库端口  					
					\$DB_DATABASE='".$mysqlDatabase."';// 数据库名
					\$DB_USERNAME='".$mysqlUsername."';// 用户名  
					\$DB_PASSWORD='".$mysqlPassword."';// 密码
					?>";
        //检测能否成功写入数据库配置
        $result = @file_put_contents($dbConfigFile, $conphp);
        if (!$result)
        {
            throw new Exception("无法写入数据库信息到项目根/conn.php，请检查是否有写权限");
        }

        //检测能否成功写入lock文件
        $result = @file_put_contents($lockFile, 1);
        if (!$result)
        {
            throw new Exception("无法写入安装锁定到/data/install.lock文件，请检查是否有写权限");
        }
        date_default_timezone_set ("Asia/Chongqing");
        $newPassword = md5(md5($adminPassword).'JUD6FCtZsqrmVXc2apev4TRn3O8gAhxbSlH9wfPN'); 
		$token = md5(mt_rand());		
        $ctime = date('Y-m-d',time());
        $siteurl = $_SERVER['SERVER_NAME'];
        $pdo->query("UPDATE think_admin SET username = '{$adminUsername}' ,password = '{$newPassword}' ,token = '{$token}' WHERE id = 1");      
        $pdo->query("UPDATE think_config SET value = '{$siteurl}' WHERE id = 35");   
        $pdo->query("UPDATE think_config SET value = '{$siteurl}' WHERE id = 97");
        $pdo->query("UPDATE think_config SET value = '//cdn.staticfile.net/' WHERE name = 'cdnpublic'");
		$pdo->query("UPDATE think_config SET value = '{$token}' WHERE name = 'token'");
        echo "success";
    }
    catch (Exception $e)
    {
        $err = $e->getMessage();
    }
    catch (PDOException $e)
    {
        $err = $e->getMessage();
    }
    echo $err;
    exit;
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>安装<?php echo $sitename; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1">
    <meta name="renderer" content="webkit">

    <style>
        body {
            background: #fff;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }
        body, input, button {
            font-family: 'Open Sans', sans-serif;
            font-size: 16px;
            color: #7E96B3;
        }
        .container {
            max-width: 515px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        a {
            color: #18bc9c;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }

        h1 {
            margin-top:0;
            margin-bottom: 10px;
        }
        h2 {
            font-size: 28px;
            font-weight: normal;
            color: #3C5675;
            margin-bottom: 0;
        }

        form {
            margin-top: 40px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group .form-field:first-child input {
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
        }
        .form-group .form-field:last-child input {
            border-bottom-left-radius: 4px;
            border-bottom-right-radius: 4px;
        }
        .form-field input {
            background: #EDF2F7;
            margin: 0 0 1px;
            border: 2px solid transparent;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
            width: 100%;
            padding: 15px 15px 15px 180px;
            box-sizing: border-box;
        }
        .form-field input:focus {
            border-color: #18bc9c;
            background: #fff;
            color: #444;
            outline: none;
        }
        .form-field label {
            float: left;
            width: 160px;
            text-align: right;
            margin-right: -160px;
            position: relative;
            margin-top: 18px;
            font-size: 14px;
            pointer-events: none;
            opacity: 0.7;
        }
        button,.btn {
            background: #3C5675;
            color: #fff;
            border: 0;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            padding: 15px 30px;
            -webkit-appearance: none;
        }
        button[disabled] {
            opacity: 0.5;
        }

        .noti {
            background: #000000;
            color: #5dff00;
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: initial;
        }
        #error,.error,#success,.success {
            background: #D83E3E;
            color: #fff;
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        #success {
            background:#3C5675;
        }

        #error a, .error a {
            color:white;
            text-decoration: underline;
        }
    </style>
</head>

<body>
<div class="container">
    <h2><?php echo $sitename; ?></h2>
    <div>
        <div class="noti"> 本程序禁止发布任何违法、违规内容。</br>
本程序为正规正版激活码、点卡、实物微商下单辅助系统。</br>
所有法律责任由架设与运营本网站(程序)的人承担。</br>
法网恢恢 疏而不漏。请自觉遵守法律法规。</div>
        <form method="post">
            <?php if ($errInfo): ?>
                <div class="error">
                    <?php echo $errInfo; ?>
                </div>
            <?php endif; ?>
            <div id="error" style="display:none"></div>
            <div id="success" style="display:none"></div>

            <div class="form-group">
                <div class="form-field">
                    <label>MySQL 数据库地址</label>
                    <input name="mysqlHost" value="localhost" required="">
                </div>

                <div class="form-field">
                    <label>MySQL 数据库名</label>
                    <input name="mysqlDatabase" value="blfaka" required="">
                </div>

                <div class="form-field">
                    <label>MySQL 用户名</label>
                    <input name="mysqlUsername" value="blfaka" required="">
                </div>

                <div class="form-field">
                    <label>MySQL 密码</label>
                    <input type="password" name="mysqlPassword">
                </div>
            </div>

            <div class="form-group">
                <div class="form-field">
                    <label>后台用户名</label>
                    <input name="adminUsername" value="admin" required="" />
                </div>


                <div class="form-field">
                    <label>后台密码</label>
                    <input type="password"  name="adminPassword" required="" >
                </div>

                <div class="form-field">
                    <label>重复密码</label>
                    <input type="password" name="adminPasswordConfirmation" required="">
                </div>
            </div>

            <div class="form-buttons">
                <button type="submit" <?php echo $errInfo ? 'disabled' : '' ?>>点击安装</button>
            </div>
        </form>

        <script src="https://cdn.bootcss.com/jquery/2.1.4/jquery.min.js"></script>
        <script>
            $(function () {
                $('form :input:first').select();

                $('form').on('submit', function (e) {
                    e.preventDefault();

                    var $button = $(this).find('button')
                        .text('安装中...')
                        .prop('disabled', true);

                    $.post('', $(this).serialize())
                        .done(function (ret) {
                            if (ret === 'success') {
                                $('#error').hide();
                                $("#success").text("安装成功！开始你的<?php echo $sitename; ?>之旅吧！").show();
                                $('<a class="btn" href="/">访问首页</a> <a class="btn" href="/houtai" style="background:#18bc9c">访问后台</a>').insertAfter($button);
                                $button.remove();
                            } else {
                                $('#error').show().text(ret);
                                $button.prop('disabled', false).text('点击安装');
                                $("html,body").animate({
                                    scrollTop: 0
                                }, 500);
                            }
                        })
                        .fail(function (data) {
                            $('#error').show().text('发生错误:\n\n' + data.responseText);
                            $button.prop('disabled', false).text('点击安装');
                            $("html,body").animate({
                                scrollTop: 0
                            }, 500);
                        });

                    return false;
                });
            });
        </script>
    </div>
</div>
</body>
</html>