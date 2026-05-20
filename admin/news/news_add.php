<?php
require_once '../config.php';
check_login();

$message = '';
$message_type = 'success';

// 获取所有分类
try {
    $stmt = $pdo->query("SELECT * FROM news_categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $message = '获取分类失败: ' . $e->getMessage();
    $message_type = 'danger';
}

// 处理表单提交（保持不变）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $content = $_POST['content'] ?? '';
    $status = isset($_POST['status']) ? 1 : 0;
    $image = '';
    
    // 验证表单（保持不变）
    if (empty($title)) {
        $message = '请输入新闻标题';
        $message_type = 'danger';
    } elseif (empty($content)) {
        $message = '请输入新闻内容';
        $message_type = 'danger';
    } else {
        try {
            // 处理图片上传（保持不变）
            if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                $file_info = @getimagesize($_FILES['image']['tmp_name']);
                
                if ($file_info === false) {
                    throw new Exception('上传的文件不是有效的图片');
                }
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array(strtolower($ext), $allowed_ext)) {
                    throw new Exception('只允许上传 JPG, JPEG, PNG, GIF 格式的图片');
                }
                
                $filename = uniqid() . '.' . $ext;
                $destination = NEWS_UPLOAD_DIR . $filename;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    throw new Exception('文件上传失败');
                }
                
                $image = $filename;
            }
            
            // 插入新闻记录（保持不变）
            $stmt = $pdo->prepare("INSERT INTO news (title, category_id, content, image, status, created_at, updated_at)
                                 VALUES (:title, :category_id, :content, :image, :status, NOW(), NOW())");
            
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':image', $image);
            $stmt->bindParam(':status', $status);
            
            $stmt->execute();
            
            $message = '新闻添加成功';
            
            $title = '';
            $category_id = 0;
            $content = '';
            $status = 0;
        } catch(PDOException $e) {
            $message = '添加失败: ' . $e->getMessage();
            $message_type = 'danger';
        } catch(Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
} else {
    $title = '';
    $category_id = 0;
    $content = '';
    $status = 1;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>添加新闻 - 新闻案例管理系统</title>
    <link href="../../csjs/bootstrap.min.css" rel="stylesheet">

    
        <!-- 引入外部资源 -->
    <script src="../../csjs/tailwindcss.js"></script>
    <link href="../../csjs/awesome/css/font-awesome.min.css" rel="stylesheet">
    
    <!-- 替换：移除 TinyMCE，引入 CKEditor 4 CDN -->
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>

    
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
                    <h1 class="h3">添加新闻</h1>
                    <a href="news_list.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> 返回列表
                    </a>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 添加新闻表单 -->
                <div class="card">
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <!-- 表单字段保持不变 -->
                            <div class="mb-3">
                                <label for="title" class="form-label">新闻标题 <span class="text-danger">*</span></label>
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
                                <label for="image" class="form-label">新闻图片</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">支持 JPG, JPEG, PNG, GIF 格式，建议尺寸：800x500px</div>
                                <img id="image-preview" src="" alt="图片预览" class="image-preview img-thumbnail">
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">新闻内容 <span class="text-danger">*</span></label>
                                <!-- 文本域保持不变，CKEditor 会自动替换它 -->
                                <textarea class="form-control" id="content" name="content" rows="15" required><?php echo htmlspecialchars($content); ?></textarea>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="status" name="status" 
                                       <?php echo $status ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status">立即发布</label>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> 保存新闻
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

    <script src="../../csjs/bootstrap.bundle.min.js"></script>
    <script>
        // 替换：初始化 CKEditor 4（替代 TinyMCE）
        CKEDITOR.replace('content', {
            height: 500,  // 编辑器高度
            // 配置工具栏（按需调整）
            toolbar: [
                { name: 'document', items: [ 'Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates' ] },
                { name: 'clipboard', items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
                { name: 'editing', items: [ 'Find', 'Replace', '-', 'SelectAll', '-', 'Scayt' ] },
                { name: 'forms', items: [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField' ] },
                '/',
                { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat' ] },
                { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language' ] },
                { name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
                { name: 'insert', items: [ 'Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe' ] },
                '/',
                { name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
                { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
                { name: 'tools', items: [ 'Maximize', 'ShowBlocks' ] },
                { name: 'about', items: [ 'About' ] }
            ],
            // 支持本地图片上传（需配合后端处理，此处保持与原逻辑兼容）
            filebrowserImageUploadUrl: 'news_add.php?action=upload_image',
            // 禁用外部资源加载提示
            removePlugins: 'elementspath',
            resize_enabled: true
        });
        
        // 图片预览功能（保持不变）
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