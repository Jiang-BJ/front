<?php
require_once 'config.php';
check_login();

// 获取统计数据
try {
    // 新闻总数
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM news");
    $news_count = $stmt->fetch()['total'];
    
    // 案例总数
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cases");
    $cases_count = $stmt->fetch()['total'];
    
    // 新闻分类总数
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM news_categories");
    $news_cat_count = $stmt->fetch()['total'];
    
    // 案例分类总数
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM case_categories");
    $case_cat_count = $stmt->fetch()['total'];
    
    // 管理员总数
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM admins");
    $admin_count = $stmt->fetch()['total'];
    
    // 最新新闻
    $stmt = $pdo->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 5");javascript:;
    $latest_news = $stmt->fetchAll();
    
    // 最新案例
    $stmt = $pdo->query("SELECT * FROM cases ORDER BY created_at DESC LIMIT 5");
    $latest_cases = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = '获取数据失败: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台首页 - 新闻案例管理系统</title>
    <link href="../csjs/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../csjs/font-awesome.min.css">
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
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- 头部导航 -->
    <?php include 'includes/header.php'; ?>
    
    <div class="d-flex">
        <!-- 侧边栏 -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- 主要内容 -->
        <div class="content bg-light">
            <div class="container-fluid">
                <h1 class="h3 mb-4">控制台</h1>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 统计卡片 -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">新闻总数</h5>
                                <h2 class="display-4"><?php echo $news_count; ?></h2>
                                <a href="news/news_list.php" class="text-white">
                                    <i class="fa fa-arrow-right"></i> 查看
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">案例总数</h5>
                                <h2 class="display-4"><?php echo $cases_count; ?></h2>
                                <a href="cases/case_list.php" class="text-white">
                                    <i class="fa fa-arrow-right"></i> 查看
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">新闻分类</h5>
                                <h2 class="display-4"><?php echo $news_cat_count; ?></h2>
                                <a href="news/news_category.php" class="text-white">
                                    <i class="fa fa-arrow-right"></i> 查看
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">案例分类</h5>
                                <h2 class="display-4"><?php echo $case_cat_count; ?></h2>
                                <a href="cases/case_category.php" class="text-white">
                                    <i class="fa fa-arrow-right"></i> 查看
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">管理员</h5>
                                <h2 class="display-4"><?php echo $admin_count; ?></h2>
                                <a href="admin/admin_list.php" class="text-white">
                                    <i class="fa fa-arrow-right"></i> 查看
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 最新内容 -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">最新新闻</h5>
                                <a href="news/news_list.php">查看全部</a>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>标题</th>
                                            <th>日期</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($latest_news as $news): ?>
                                        <tr>
                                            <td><?php echo mb_substr($news['title'], 0, 20) . (mb_strlen($news['title']) > 20 ? '...' : ''); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($news['created_at'])); ?></td>
                                            <td>
                                                <a href="news/news_edit.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">最新案例</h5>
                                <a href="cases/case_list.php">查看全部</a>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>标题</th>
                                            <th>日期</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($latest_cases as $case): ?>
                                        <tr>
                                            <td><?php echo mb_substr($case['title'], 0, 20) . (mb_strlen($case['title']) > 20 ? '...' : ''); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($case['created_at'])); ?></td>
                                            <td>
                                                <a href="cases/case_edit.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 页脚 -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
