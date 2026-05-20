<?php
// 数据库配置
$db_config = [
    'host' => 'localhost',
    'dbname' => 'jas_expo_news',
    'username' => 'jasexpo',
    'password' => 'auqf1009',
    'charset' => 'utf8mb4'
];

// 网站配置
$site_config = [
    'name' => '福州佳势展览有限公司',
    'news_per_page' => 10, // 每页显示新闻数量
    'admin_path' => '/admin', // 后台路径
    'upload_path' => '/uploads/news/', // 上传路径
    'upload_url' => '/uploads/news/' // 上传URL
];

// 连接数据库
try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 检查并创建上传目录
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . $site_config['upload_path'];
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 登录状态检查函数
function check_login() {
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

// 格式化日期函数
function format_date($date, $format = 'Y-m-d H:i') {
    return date($format, strtotime($date));
}

// 截取字符串函数
function cut_str($str, $length = 100, $suffix = '...') {
    if (mb_strlen($str, 'utf-8') <= $length) {
        return $str;
    }
    return mb_substr($str, 0, $length, 'utf-8') . $suffix;
}
?>
    