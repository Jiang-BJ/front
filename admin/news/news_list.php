<?php
require_once '../config.php';
check_login();

$message = '';
$message_type = 'success';

// 处理删除请求
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    try {
        // 先获取新闻信息，以便删除图片
        $stmt = $pdo->prepare("SELECT image FROM news WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $news = $stmt->fetch();
        
        // 删除图片文件
        if ($news && !empty($news['image']) && file_exists(NEWS_UPLOAD_DIR . $news['image'])) {
            unlink(NEWS_UPLOAD_DIR . $news['image']);
        }
        
        // 删除新闻记录
        $stmt = $pdo->prepare("DELETE FROM news WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $message = '新闻已成功删除';
    } catch(PDOException $e) {
        $message = '删除失败: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// 处理批量操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $ids = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
    
    if (!empty($ids)) {
        try {
            if ($action === 'delete') {
                // 获取要删除的图片
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("SELECT image FROM news WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $news_list = $stmt->fetchAll();
                
                // 删除图片文件
                foreach ($news_list as $item) {
                    if (!empty($item['image']) && file_exists(NEWS_UPLOAD_DIR . $item['image'])) {
                        unlink(NEWS_UPLOAD_DIR . $item['image']);
                    }
                }
                
                // 删除记录
                $stmt = $pdo->prepare("DELETE FROM news WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                
                $message = '选中的新闻已成功删除';
            }
        } catch(PDOException $e) {
            $message = '操作失败: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = '请先选择要操作的新闻';
        $message_type = 'warning';
    }
}

// 获取分页参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 获取分类筛选
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

// 搜索关键词
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 构建查询条件
$where = [];
$params = [];

if ($category_id > 0) {
    $where[] = "category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if (!empty($search)) {
    $where[] = "(title LIKE :search OR content LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// 获取新闻列表
try {
    // 获取总数（用于分页）
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM news {$where_clause}");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    $total_pages = ceil($total / $limit);
    
    // 获取新闻数据
    $stmt = $pdo->prepare("SELECT n.*, c.name as category_name 
                          FROM news n 
                          LEFT JOIN news_categories c ON n.category_id = c.id 
                          {$where_clause} 
                          ORDER BY n.created_at DESC 
                          LIMIT :limit OFFSET :offset");
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    // 绑定其他参数
    foreach ($params as $key => $value) {
        if ($key != ':limit' && $key != ':offset') {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $news_list = $stmt->fetchAll();
    
    // 获取所有分类（用于筛选）
    $stmt = $pdo->query("SELECT * FROM news_categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $message = '获取数据失败: ' . $e->getMessage();
    $message_type = 'danger';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新闻列表 - 新闻案例管理系统</title>
    <link href="../../csjs/bootstrap.min.css" rel="stylesheet">

        <!-- 引入外部资源 -->
    <script src="../../csjs/tailwindcss.js"></script>
    <link href="../../csjs/awesome/css/font-awesome.min.css" rel="stylesheet">
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
        .news-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
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
                    <h1 class="h3">新闻管理</h1>
                    <a href="news_add.php" class="btn btn-primary">
                        <i class="fa fa-plus"></i> 添加新闻
                    </a>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 筛选和搜索 -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-3">
                                <label for="category" class="form-label">分类</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="0">全部分类</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo $category['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="search" class="form-label">搜索</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="搜索标题或内容..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">筛选</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- 新闻列表 -->
                <div class="card">
                    <div class="card-body">
                        <form method="post" id="news-form">
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <button type="button" class="btn btn-danger" id="delete-selected" 
                                            onclick="if(confirm('确定要删除选中的新闻吗？')) document.getElementById('news-form').submit();">
                                        <i class="fa fa-trash"></i> 批量删除
                                    </button>
                                    <input type="hidden" name="bulk_action" value="delete">
                                </div>
                                <div class="text-muted">共 <?php echo $total; ?> 条记录</div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" id="check-all">
                                            </th>
                                            <th>标题</th>
                                            <th>分类</th>
                                            <th>图片</th>
                                            <th>发布日期</th>
                                            <th>状态</th>
                                            <th width="150">操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($news_list)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">没有找到新闻数据</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($news_list as $news): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="ids[]" value="<?php echo $news['id']; ?>">
                                                </td>
                                                <td><?php echo htmlspecialchars($news['title']); ?></td>
                                                <td><?php echo htmlspecialchars($news['category_name'] ?? '未分类'); ?></td>
                                                <td>
                                                    <?php if (!empty($news['image']) && file_exists(NEWS_UPLOAD_DIR . $news['image'])): ?>
                                                        <img src="<?php echo BASE_URL; ?>uploads/news/<?php echo $news['image']; ?>" 
                                                             alt="新闻图片" class="news-image rounded">
                                                    <?php else: ?>
                                                        <span class="text-muted">无图片</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($news['created_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $news['status'] == 1 ? 'success' : 'secondary'; ?>">
                                                        <?php echo $news['status'] == 1 ? '已发布' : '草稿'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="news_edit.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fa fa-edit"></i> 编辑
                                                    </a>
                                                    <a href="?delete=<?php echo $news['id']; ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('确定要删除这条新闻吗？')">
                                                        <i class="fa fa-trash"></i> 删除
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                        
                        <!-- 分页 -->
                        <?php if ($total_pages > 1): ?>
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page == 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($_GET, '', '&'); ?>">
                                            上一页
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET, '', '&'); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page == $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($_GET, '', '&'); ?>">
                                            下一页
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 页脚 -->
    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 全选/取消全选
        document.getElementById('check-all').addEventListener('change', function(e) {
            let checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
        });
    </script>
</body>
</html>
