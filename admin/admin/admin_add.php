<?php
require_once '../config.php';
check_login();

// 检查是否为超级管理员
if ($_SESSION['admin']['role'] != 'super') {
    header('Location: admin_list.php');
    exit();
}

$message = '';
$message_type = 'success';

// 处理添加管理员
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    
    // 验证表单
    if (empty($username)) {
        $message = '用户名不能为空';
        $message_type = 'danger';
    } elseif (strlen($username) < 4 || strlen($username) > 20) {
        $message = '用户名长度必须在4-20个字符之间';
        $message_type = 'danger';
    } elseif (empty($password)) {
        $message = '密码不能为空';
        $message_type = 'danger';
    } elseif (strlen($password) < 6) {
        $message = '密码长度不能少于6个字符';
        $message_type = 'danger';
    } elseif ($password != $confirm_password) {
        $message = '两次输入的密码不一致';
        $message_type = 'danger';
    } elseif (!in_array($role, ['admin', 'super'])) {
        $message = '无效的角色类型';
        $message_type = 'danger';
    } else {
        try {
            // 检查用户名是否已存在
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                throw new Exception('该用户名已被使用');
            }
            
            // 密码加密
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // 添加管理员
            $stmt = $pdo->prepare("INSERT INTO admins (username, password, role, created_at) 
                                 VALUES (:username, :password, :role, NOW())");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':role', $role);
            $stmt->execute();
            
            $message = '管理员添加成功';
            // 清空表单
            $username = '';
            $role = 'admin';
        } catch(PDOException $e) {
            $message = '数据库错误: ' . $e->getMessage();
            $message_type = 'danger';
        } catch(Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
} else {
    $username = '';
    $role = 'admin';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>添加管理员 - 新闻案例管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            min-height: calc(100vh - 56px);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            color: white;
            background-color: #495057;
        }
        .content {
            flex: 1;
            padding: 20px;
        }
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <!-- 头部导航 -->
    <?php include '../includes/header.php'; ?>
    
    <div class="d-flex">
        <!-- 侧边栏 -->
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- 主要内容 -->
        <div class="content bg-light">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">添加管理员</h1>
                    <a href="admin_list.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> 返回列表
                    </a>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 添加管理员表单 -->
                <div class="card form-container">
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">用户名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($username); ?>" 
                                       minlength="4" maxlength="20" required>
                                <div class="form-text">4-20个字符，用于登录系统</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">密码 <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="6" required>
                                <div class="form-text">至少6个字符，建议包含字母和数字</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">确认密码 <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="6" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">角色 <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="admin" <?php echo $role == 'admin' ? 'selected' : ''; ?>>普通管理员</option>
                                    <option value="super" <?php echo $role == 'super' ? 'selected' : ''; ?>>超级管理员</option>
                                </select>
                                <div class="form-text">超级管理员拥有所有权限，包括管理其他管理员</div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> 保存
                                </button>
                                <a href="admin_list.php" class="btn btn-secondary ms-2">取消</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 页脚 -->
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>