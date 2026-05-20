<?php
require_once 'config.php';

$error = '';

// 如果用户已登录，重定向到首页
if (is_logged_in()) {
    header('Location: index.php');
    exit();
}

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        try {
            // 查询管理员信息
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $admin = $stmt->fetch();
            
            // 验证密码
            if ($admin && password_verify($password, $admin['password'])) {
                // 登录成功，设置会话
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                
                // 重定向到首页
                header('Location: index.php');
                exit();
            } else {
                $error = '用户名或密码不正确';
            }
        } catch(PDOException $e) {
            $error = '登录失败: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 新闻案例管理系统</title>
    <link href="../csjs/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-title {
            text-align: center;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">管理员登录</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label">用户名</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">密码</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">登录</button>
        </form>
    </div>

    <script src="../csjs/bootstrap.bundle.min.js"></script>
</body>
</html>
