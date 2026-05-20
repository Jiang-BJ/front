<?php
// 数据库配置信息
$host = 'localhost';         // 数据库主机地址
$dbname = 'jas_expo_news';    // 数据库名称
$username = 'jasexpo';          // 数据库用户名
$password = 'auqf1009';              // 数据库密码

try {
    // 创建PDO连接
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // 设置错误模式为异常
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // 设置默认获取模式为关联数组
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // 连接失败时输出错误信息
    die("数据库连接失败: " . $e->getMessage());
}

// 网站根目录
define('BASE_URL', 'https://s.jas-expo.com/');

// 上传目录配置
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
define('NEWS_UPLOAD_DIR', UPLOAD_DIR . 'news/');
define('CASES_UPLOAD_DIR', UPLOAD_DIR . 'cases/');

// 确保上传目录存在
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!is_dir(NEWS_UPLOAD_DIR)) {
    mkdir(NEWS_UPLOAD_DIR, 0755, true);
}
if (!is_dir(CASES_UPLOAD_DIR)) {
    mkdir(CASES_UPLOAD_DIR, 0755, true);
}

// 启动会话
session_start();

// 检查用户是否登录
function is_logged_in() {
    return isset($_SESSION['admin_id']);
}

// 检查用户是否登录，如果未登录则重定向到登录页
function check_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit();
    }
}

// 显示消息提示
function show_message($message, $type = 'success') {
    if ($message) {
        echo '<div class="alert alert-' . $type . '">';
        echo $message;
        echo '</div>';
    }
}
?>
