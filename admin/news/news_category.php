<?php
require_once '../config.php';
check_login();

$message = '';
$message_type = 'success';

// 处理添加分类
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    
    if (empty($name)) {
        $message = '请输入分类名称';
        $message_type = 'danger';
    } else {
        try {
            // 检查分类是否已存在
            $stmt = $pdo->prepare("SELECT id FROM news_categories WHERE name = :name");
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                throw new Exception('该分类已存在');
            }
            
            // 添加新分类
            $stmt = $pdo->prepare("INSERT INTO news_categories (name, created_at) VALUES (:name, NOW())");
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            
            $message = '分类添加成功';
        } catch(PDOException $e) {
            $message = '添加失败: ' . $e->getMessage();
            $message_type = 'danger';
        } catch(Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// 处理编辑分类
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    
    if ($id == 0 || empty($name)) {
        $message = '请输入分类名称';
        $message_type = 'danger';
    } else {
        try {
            // 检查分类是否已存在
            $stmt = $pdo->prepare("SELECT id FROM news_categories WHERE name = :name AND id != :id");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                throw new Exception('该分类已存在');
            }
            
            // 更新分类
            $stmt = $pdo->prepare("UPDATE news_categories SET name = :name WHERE id = :id");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                throw new Exception('未找到该分类或未做任何修改');
            }
            
            $message = '分类更新成功';
        } catch(PDOException $e) {
            $message = '更新失败: ' . $e->getMessage();
            $message_type = 'danger';
        } catch(Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// 处理删除分类
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    try {
        // 检查该分类下是否有新闻
        $stmt = $pdo->prepare("SELECT id FROM news WHERE category_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            throw new Exception('该分类下有新闻，无法删除。请先修改相关新闻的分类。');
        }
        
        // 删除分类
        $stmt = $pdo->prepare("DELETE FROM news_categories WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $message = '分类删除成功';
    } catch(PDOException $e) {
        $message = '删除失败: ' . $e->getMessage();
        $message_type = 'danger';
    } catch(Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// 获取所有分类
try {
    $stmt = $pdo->query("SELECT * FROM news_categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $message = '获取分类失败: ' . $e->getMessage();
    $message_type = 'danger';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新闻分类管理 - 新闻案例管理系统</title>
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
        .modal-backdrop {
            z-index: 1040;
        }
        .modal {
            z-index: 1050;
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
                    <h1 class="h3">新闻分类管理</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fa fa-plus"></i> 添加分类
                    </button>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 分类列表 -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>分类名称</th>
                                        <th>新闻数量</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">没有分类数据</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo $category['id']; ?></td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td>
                                                <?php
                                                // 获取该分类下的新闻数量
                                                try {
                                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM news WHERE category_id = :id");
                                                    $stmt->bindParam(':id', $category['id']);
                                                    $stmt->execute();
                                                    echo $stmt->fetch()['total'];
                                                } catch(PDOException $e) {
                                                    echo '?';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($category['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-category" 
                                                        data-id="<?php echo $category['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                        data-bs-toggle="modal" data-bs-target="#editCategoryModal">
                                                    <i class="fa fa-edit"></i> 编辑
                                                </button>
                                                <a href="?delete=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('确定要删除这个分类吗？')">
                                                    <i class="fa fa-trash"></i> 删除
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 添加分类模态框 -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">添加新闻分类</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">分类名称</label>
                            <input type="text" class="form-control" id="category_name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary" name="add_category">添加</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 编辑分类模态框 -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">编辑新闻分类</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <input type="hidden" id="edit_category_id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">分类名称</label>
                            <input type="text" class="form-control" id="edit_category_name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary" name="edit_category">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 页脚 -->
    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 编辑分类按钮点击事件
        document.querySelectorAll('.edit-category').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                
                document.getElementById('edit_category_id').value = id;
                document.getElementById('edit_category_name').value = name;
            });
        });
    </script>
</body>
</html>
