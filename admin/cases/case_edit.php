<?php
require_once '../config.php';
check_login();

$message = '';
$message_type = 'success';

// 验证ID参数
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: case_list.php');
    exit();
}

$id = intval($_GET['id']);

// 获取案例信息
try {
    $stmt = $pdo->prepare("SELECT * FROM cases WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $case = $stmt->fetch();
    
    if (!$case) {
        throw new Exception('未找到该案例');
    }
    
    // 获取所有分类
    $stmt = $pdo->query("SELECT * FROM case_categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $message = '数据库错误: ' . $e->getMessage();
    $message_type = 'danger';
} catch(Exception $e) {
    $message = $e->getMessage();
    $message_type = 'danger';
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $case) {
    $title = trim($_POST['title'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $description = $_POST['description'] ?? '';
    $content = $_POST['content'] ?? '';
    $client = trim($_POST['client'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    $current_image = $case['image'];
    $image = $current_image;
    
    // 处理移除图片请求
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == 'on') {
        if (!empty($current_image) && file_exists(CASES_UPLOAD_DIR . $current_image)) {
            unlink(CASES_UPLOAD_DIR . $current_image);
            $image = '';
        }
    }
    
    // 验证表单
    if (empty($title)) {
        $message = '请输入案例标题';
        $message_type = 'danger';
    } elseif (empty($content)) {
        $message = '请输入案例内容';
        $message_type = 'danger';
    } elseif (empty($image) && (!isset($_FILES['image']) || $_FILES['image']['error'] != UPLOAD_ERR_OK)) {
        $message = '请上传案例图片';
        $message_type = 'danger';
    } else {
        try {
            // 处理图片上传
            if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                $file_info = @getimagesize($_FILES['image']['tmp_name']);
                
                // 验证文件是否为图片
                if ($file_info === false) {
                    throw new Exception('上传的文件不是有效的图片');
                }
                
                // 获取文件扩展名
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array(strtolower($ext), $allowed_ext)) {
                    throw new Exception('只允许上传 JPG, JPEG, PNG, GIF 格式的图片');
                }
                
                // 生成唯一文件名
                $filename = uniqid() . '.' . $ext;
                $destination = CASES_UPLOAD_DIR . $filename;
                
                // 移动上传文件
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    throw new Exception('文件上传失败');
                }
                
                // 删除旧图片
                if (!empty($current_image) && file_exists(CASES_UPLOAD_DIR . $current_image)) {
                    unlink(CASES_UPLOAD_DIR . $current_image);
                }
                
                $image = $filename;
            }
            
            // 更新案例记录
            $stmt = $pdo->prepare("UPDATE cases SET 
                                 title = :title,
                                 category_id = :category_id,
                                 description = :description,
                                 content = :content,
                                 client = :client,
                                 image = :image,
                                 status = :status,
                                 updated_at = NOW()
                                 WHERE id = :id");
            
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':client', $client);
            $stmt->bindParam(':image', $image);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);
            
            $stmt->execute();
            
            $message = '案例更新成功';
            
            // 更新当前案例信息
            $case['title'] = $title;
            $case['category_id'] = $category_id;
            $case['description'] = $description;
            $case['content'] = $content;
            $case['client'] = $client;
            $case['image'] = $image;
            $case['status'] = $status;
        } catch(PDOException $e) {
            $message = '更新失败: ' . $e->getMessage();
            $message_type = 'danger';
        } catch(Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑案例 - 新闻案例管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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
        .image-preview {
            max-width: 300px;
            margin-top: 10px;
        }
        .current-image {
            max-width: 300px;
            margin-bottom: 10px;
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
                    <h1 class="h3">编辑案例</h1>
                    <a href="case_list.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> 返回列表
                    </a>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!$case): ?>
                    <div class="alert alert-danger">
                        未找到指定的案例
                    </div>
                <?php else: ?>
                    <!-- 编辑案例表单 -->
                    <div class="card">
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="title" class="form-label">案例标题 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($case['title']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">所属分类</label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="0">-- 请选择分类 --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $case['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="client" class="form-label">客户名称</label>
                                    <input type="text" class="form-control" id="client" name="client" 
                                           value="<?php echo htmlspecialchars($case['client']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">案例简介</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($case['description']); ?></textarea>
                                    <div class="form-text">简短描述案例的核心内容，将用于列表展示</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="image" class="form-label">案例图片 <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    <div class="form-text">支持 JPG, JPEG, PNG, GIF 格式，建议尺寸：800x500px</div>
                                    
                                    <?php if (!empty($case['image']) && file_exists(CASES_UPLOAD_DIR . $case['image'])): ?>
                                        <div class="mt-2">
                                            <p>当前图片：</p>
                                            <img src="<?php echo BASE_URL; ?>uploads/cases/<?php echo $case['image']; ?>" 
                                                 alt="当前案例图片" class="current-image img-thumbnail">
                                            <div class="form-check mt-2">
                                                <input type="checkbox" class="form-check-input" id="remove_image" name="remove_image">
                                                <label class="form-check-label" for="remove_image">移除当前图片</label>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="content" class="form-label">案例内容 <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="content" name="content" rows="10"><?php echo htmlspecialchars($case['content']); ?></textarea>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="status" name="status" 
                                        <?php echo $case['status'] == 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status">发布状态</label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> 保存修改
                                </button>
                                <a href="case_list.php" class="btn btn-secondary ms-2">取消</a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // 初始化富文本编辑器
        tinymce.init({
            selector: '#content',
            plugins: 'advlist autolink lists link image charmap preview anchor pagebreak',
            toolbar_mode: 'floating',
            height: 400,
            relative_urls: false,
            remove_script_host: false,
            document_base_url: '<?php echo BASE_URL; ?>'
        });
        
        // 图片预览功能
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    let preview = document.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.className = 'image-preview img-thumbnail';
                        e.target.parentNode.appendChild(preview);
                    }
                    preview.src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>