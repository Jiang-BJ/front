<?php
require_once '../config.php';
check_login();

// 检查是否为超级管理员
if ($_SESSION['admin']['role'] != 'super') {
    $message = '权限不足，无法访问此页面';
    $message_type = 'danger';
} else {
    $message = '';
    $message_type = 'success';

    // 处理删除管理员
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        try {
            // 不能删除自己
            if ($id == $_SESSION['admin']['id']) {
                throw new Exception('不能删除当前登录的管理员');
            }
            
            // 不能删除最后一个超级管理员
            if ($_SESSION['admin']['role'] == 'super') {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admins WHERE role = 'super'");
                $stmt->execute();
                $super_count = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("SELECT role FROM admins WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $admin = $stmt->fetch();
                
                if ($admin && $admin['role'] == 'super' && $super_count <= 1) {
                    throw new Exception('不能删除最后一个超级管理员');
                }
            }
            
            // 执行删除
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $message = '管理员删除成功';
            } else {
                throw new Exception('未找到该管理员');
            }
        } catch(PDOException $e) {
            $message = '数据库错误: ' . $e->getMessage();
            $message_type = 'danger';
        } catch(Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }

    // 获取所有管理员
    try {
        $stmt = $pdo->query("SELECT * FROM admins ORDER BY id DESC");
        $admins = $stmt->fetchAll();
    } catch(PDOException $e) {
        $message = '获取管理员列表失败: ' . $e->getMessage();
        $message_type = 'danger';
        $admins = [];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员管理 - 新闻案例管理系统</title>
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
                    <h1 class="h3">管理员管理</h1>
                    <?php if ($_SESSION['admin']['role'] == 'super'): ?>
                        <a href="admin_add.php" class="btn btn-primary">
                            <i class="fa fa-plus"></i> 添加管理员
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($_SESSION['admin']['role'] != 'super'): ?>
                    <div class="alert alert-danger">
                        您没有权限访问此页面，只有超级管理员可以管理管理员账户。
                    </div>
                <?php else: ?>
                    <!-- 管理员列表 -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($admins)): ?>
                                <div class="alert alert-info">
                                    暂无管理员，请添加管理员
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>用户名</th>
                                                <th>角色</th>
                                                <th>创建时间</th>
                                                <th>最后登录</th>
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($admins as $admin): ?>
                                                <tr>
                                                    <td><?php echo $admin['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                                    <td>
                                                        <?php if ($admin['role'] == 'super'): ?>
                                                            <span class="badge bg-danger">超级管理员</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-primary">普通管理员</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $admin['created_at']; ?></td>
                                                    <td><?php echo $admin['last_login'] ? $admin['last_login'] : '从未登录'; ?></td>
                                                    <td>
                                                        <a href="admin_edit.php?id=<?php echo $admin['id']; ?>" class="btn btn-sm btn-warning">
                                                            <i class="fa fa-edit"></i> 编辑
                                                        </a>
                                                        <?php if ($admin['id'] != $_SESSION['admin']['id']): ?>
                                                            <a href="?action=delete&id=<?php echo $admin['id']; ?>" 
                                                               class="btn btn-sm btn-danger"
                                                               onclick="return confirm('确定要删除该管理员吗？删除后不可恢复！')">
                                                                <i class="fa fa-trash"></i> 删除
                                                            </a>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-secondary" disabled>
                                                                <i class="fa fa-trash"></i> 自己
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 页脚 -->
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>