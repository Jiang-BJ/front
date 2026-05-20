<?php
require_once '../config.php';
check_login();

$message = '';
$message_type = 'success';

// 获取所有分类
try {
    $stmt = $pdo->query("SELECT * FROM case_categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $message = '获取分类失败: ' . $e->getMessage();
    $message_type = 'danger';
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $description = $_POST['description'] ?? '';
    $content = $_POST['content'] ?? '';
    $client = trim($_POST['client'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    $image = '';
    
    // 验证表单
    if (empty($title)) {
        $message = '请输入案例标题';
        $message_type = 'danger';
    } elseif (empty($content)) {
        $message = '请输入案例内容';
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
                
                $image = $filename;
            }
            
            // 插入案例记录
            $stmt = $pdo->prepare("INSERT INTO cases (title, category_id, description, content, client, image, status, created_at, updated_at)
                                 VALUES (:title, :category_id, :description, :content, :client, :image, :status, NOW(), NOW())");
            
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':client', $client);
            $stmt->bindParam(':image', $image);
            $stmt->bindParam(':status', $status);
            
            $stmt->execute();
            
            $message = '案例添加成功';
            
            // 重置表单
            $title = '';
            $category_id = 0;
            $description = '';
            $content = '';
            $client = '';
            $status = 1;
        } catch(PDOException $e) {
            $message = '添加失败: ' . $e->getMessage();
            $message_type = 'danger';
        } catch(Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
} else {
    // 初始化表单值
    $title = '';
    $category_id = 0;
    $description = '';
    $content = '';
    $client = '';
    $status = 1;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>添加案例 - 新闻案例管理系统</title>
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
            display: none;
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
                    <h1 class="h3">添加案例</h1>
                    <a href="case_list.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> 返回列表
                    </a>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 添加案例表单 -->
                <div class="card">
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">案例标题 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($title); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">所属分类</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="0">-- 请选择分类 --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="client" class="form-label">客户名称</label>
                                <input type="text" class="form-control" id="client" name="client" 
                                       value="<?php echo htmlspecialchars($client); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">案例简介</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                                <div class="form-text">简短描述案例的核心内容，将用于列表展示</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">案例图片 <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                <div class="form-text">支持 JPG, JPEG, PNG, GIF 格式，建议尺寸：800x500px</div>
                                <img id="image-preview" src="" alt="图片预览" class="image-preview img-thumbnail">
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">案例详情 <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="content" name="content" rows="15" required><?php echo htmlspecialchars($content); ?></textarea>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="status" name="status" 
                                       <?php echo $status ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status">立即发布</label>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> 保存案例
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fa fa-refresh"></i> 重置
                                </button>
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
    <script>
        // 初始化富文本编辑器
        tinymce.init({
            selector: '#content',
            plugins: 'advlist autolink lists link image charmap preview anchor pagebreak',
            toolbar_mode: 'floating',
            toolbar: 'undo redo | bold italic underline strikethrough | fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap | fullscreen  preview save print | insertfile image media template link anchor codesample | a11ycheck ltr rtl | showcomments addcomment',
            height: 500,
            relative_urls: false,
            remove_script_host: false,
            document_base_url: "<?php echo BASE_URL; ?>"
        });
        
        // 图片预览
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image-preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
