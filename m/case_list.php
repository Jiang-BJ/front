<?php
require_once '../config.php';
check_login();

// 处理删除操作
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // 先获取案例信息
    $stmt = $pdo->prepare("SELECT cover_image, images FROM cases WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $case = $stmt->fetch();
    
    // 删除图片文件
    if ($case) {
        // 删除封面图
        if (!empty($case['cover_image'])) {
            $cover_path = $_SERVER['DOCUMENT_ROOT'] . $site_config['cases_upload_path'] . $case['cover_image'];
            if (file_exists($cover_path)) {
                unlink($cover_path);
            }
        }
        
        // 删除图片集中的图片
        if (!empty($case['images'])) {
            $images = explode(',', $case['images']);
            foreach ($images as $image) {
                $image_path = $_SERVER['DOCUMENT_ROOT'] . $site_config['cases_upload_path'] . $image;
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
        }
        
        // 删除数据库记录
        $pdo->prepare("DELETE FROM cases WHERE id = :id")->execute([':id' => $id]);
        
        // 重定向回列表页
        header("Location: case_list.php?deleted=1");
        exit;
    }
}

// 构建查询条件
$where = '';
$params = [];

// 分类筛选
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
if ($category_id > 0) {
    $where .= " AND category_id = :category_id";
    $params[':category_id'] = $category_id;
}

// 状态筛选
$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
if ($status != -1) {
    $where .= " AND status = :status";
    $params[':status'] = $status;
}

// 搜索功能
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
if (!empty($keyword)) {
    $where .= " AND (title LIKE :keyword OR client LIKE :keyword OR description LIKE :keyword)";
    $params[':keyword'] = "%{$keyword}%";
}

// 分页处理
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page - 1) * $site_config['cases_per_page'];

// 获取总记录数
$total_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cases WHERE 1=1 {$where}");
$total_stmt->execute($params);
$total = $total_stmt->fetch()['total'];
$total_pages = ceil($total / $site_config['cases_per_page']);

// 获取案例列表
$stmt = $pdo->prepare("SELECT c.*, cc.name as category_name 
                       FROM cases c 
                       LEFT JOIN case_category cc ON c.category_id = cc.id 
                       WHERE 1=1 {$where} 
                       ORDER BY c.created_at DESC 
                       LIMIT :start, :per_page");
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $site_config['cases_per_page'], PDO::PARAM_INT);
$stmt->execute($params);
$cases_list = $stmt->fetchAll();

// 获取所有分类
$categories = $pdo->query("SELECT * FROM case_category ORDER BY sort_order ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>案例管理 - 后台管理</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style type="text/tailwindcss">
        @layer utilities {
            .sidebar-link {
                @apply flex items-center px-4 py-3 text-gray-700 hover:bg-primary/10 hover:text-primary transition-colors;
            }
            .sidebar-link.active {
                @apply bg-primary/10 text-primary border-l-4 border-primary;
            }
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#9CB93B',
                    }
                }
            }
        }
    </script>
