<?php
require_once '../config.php';
check_login();

$message = '';
$message_type = 'success';

// 处理添加分类
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    
    if (empty($name)) {
        $message = '分类名称不能为空';
        $message_type = 'danger';
    } else {
        try {
            // 检查分类是否已存在
            $stmt = $pdo->prepare("SELECT id FROM case_categories WHERE name = :name");
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                throw new Exception('该分类已存在');
            }
            
            // 添加新分类
            $stmt = $pdo->prepare("INSERT INTO case_categories (name, sort_order, created_at, updated_at) 
                                 VALUES (:name, :sort_order, NOW(), NOW())");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':sort_order', $sort_order);
            $stmt->execute();
            
            $message = '分类添加成功';
        } catch(PDOException $e) {
            $message = '数据库错误: ' . $e->getMessage();
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
    $sort_order = intval($_POST['sort_order'] ?? 0);
    
    if (empty($name)) {
        $message = '分类名称不能为空';
        $message_type = 'danger';
    } elseif ($id <= 0) {
        $message = '无效的分类ID';
        $message_type = 'danger';
    } else {
        try {
            // 检查分类是否存在
            $stmt = $pdo->prepare("SELECT id FROM case_categories WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                throw new Exception('未找到该分类');
            }
            
            // 检查分类名是否已被其他分类使用
            $stmt = $pdo->prepare("SELECT id FROM case_categories WHERE name = :name AND id != :id");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                throw new Exception('该分类名称已被使用');
            }
            
            // 更新分类
            $stmt = $pdo->prepare("UPDATE case_categories SET 
                                 name = :name,
                                 sort_order = :sort_order,
                                 updated_at = NOW()
                                 WHERE id = :id");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':sort_order', $sort_order);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $message = '分类更新成功';
        } catch(PDOException $e) {
            $message = '数据库错误: ' . $e->getMessage();
            $message_type = 'danger';
        } catch(Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// 处理删除分类
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        // 检查是否有关联的案例
        $stmt = $pdo->prepare("SELECT id FROM cases WHERE category_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('该分类下存在案例，无法删除');
        }
        
        // 删除分类
        $stmt = $pdo->prepare("DELETE FROM case_categories WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $message = '分类删除成功';
    } catch(PDOException $e) {
        $message = '数据库错误: ' . $e->getMessage();
        $message_type = 'danger';
    } catch(Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// 获取所有分类
try {
    $stmt = $pdo->query("SELECT * FROM case_categories ORDER BY sort_order ASC, id DESC");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $message = '获取分类失败: ' . $e->getMessage();
    $message_type = 'danger';
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>案例分类管理 - 新闻案例管理系统</title>
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
                    <h1 class="h3">案例分类管理</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
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
                        <?php if (empty($categories)): ?>
                            <div class="alert alert-info">
                                暂无案例分类，请添加分类
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>分类名称</th>
                                            <th>排序</th>
                                            <th>案例数量</th>
                                            <th>创建时间</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                            <?php
                                            // 获取该分类下的案例数量
                                            try {
                                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cases WHERE category_id = :id");
                                                $stmt->bindParam(':id', $category['id']);
                                                $stmt->execute();
                                                $count = $stmt->fetchColumn();
                                            } catch(PDOException $e) {
                                                $count = '?';
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo $category['id']; ?></td>
                                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                <td><?php echo $category['sort_order']; ?></td>
                                                <td><?php echo $count; ?></td>
                                                <td><?php echo $category['created_at']; ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning" 
                                                            data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                                            data-id="<?php echo $category['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                            data-sort_order="<?php echo $category['sort_order']; ?>">
                                                        <i class="fa fa-edit"></i> 编辑
                                                    </button>
                                                    <a href="?action=delete&id=<?php echo $category['id']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('确定要删除该分类吗？删除后不可恢复！')">
                                                        <i class="fa fa-trash"></i> 删除
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
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
                    <h5 class="modal-title" id="addCategoryModalLabel">添加案例分类</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">分类名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="sort_order" class="form-label">排序</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                            <div class="form-text">数字越小越靠前</div>
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
                    <h5 class="modal-title" id="editCategoryModalLabel">编辑案例分类</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">分类名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_sort_order" class="form-label">排序</label>
                            <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0">
                            <div class="form-text">数字越小越靠前</div>
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
        // 编辑分类模态框数据填充
        var editModal = document.getElementById('editCategoryModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var sortOrder = button.getAttribute('data-sort_order');
            
            var modalInputId = editModal.querySelector('#edit_id');
            var modalInputName = editModal.querySelector('#edit_name');
            var modalInputSortOrder = editModal.querySelector('#edit_sort_order');
            
            modalInputId.value = id;
            modalInputName.value = name;
            modalInputSortOrder.value = sortOrder;
        });
    </script>
</body>
</html>