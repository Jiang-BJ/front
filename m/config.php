<?php
// 数据库配置
$db_config = [
    'host' => 'localhost',
    'dbname' => 'jas_expo_news',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// 网站配置
$site_config = [
    'name' => '福州佳势展览有限公司',
    'news_per_page' => 10, // 每页显示新闻数量
    'cases_per_page' => 8, // 每页显示案例数量
    'admin_path' => '/admin', // 后台路径
    'upload_path' => '/uploads/', // 上传根路径
    'upload_url' => '/uploads/', // 上传根URL
    'news_upload_path' => '/uploads/news/', // 新闻上传路径
    'cases_upload_path' => '/uploads/cases/' // 案例上传路径
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
$upload_dirs = [
    $_SERVER['DOCUMENT_ROOT'] . $site_config['upload_path'],
    $_SERVER['DOCUMENT_ROOT'] . $site_config['news_upload_path'],
    $_SERVER['DOCUMENT_ROOT'] . $site_config['cases_upload_path']
];

foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
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

// 上传文件函数
function upload_file($file, $upload_dir) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'message' => '文件上传失败'];
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['status' => false, 'message' => '不支持的文件类型'];
    }
    
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        return ['status' => false, 'message' => '文件大小不能超过5MB'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $destination = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['status' => true, 'filename' => $filename];
    } else {
        return ['status' => false, 'message' => '文件移动失败'];
    }
}
?>
    