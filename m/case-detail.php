<?php
require_once 'config.php';

// 检查案例ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("无效的案例ID");
}

$case_id = intval($_GET['id']);

// 获取案例详情
$stmt = $pdo->prepare("SELECT c.*, cc.name as category_name 
                       FROM cases c 
                       LEFT JOIN case_category cc ON c.category_id = cc.id 
                       WHERE c.id = :id AND c.status = 1");
$stmt->execute([':id' => $case_id]);
$case = $stmt->fetch();

if (!$case) {
    die("未找到该案例");
}

// 更新浏览次数
$pdo->prepare("UPDATE cases SET view_count = view_count + 1 WHERE id = :id")
    ->execute([':id' => $case_id]);

// 处理图片集
$case_images = [];
if (!empty($case['images'])) {
    $case_images = explode(',', $case['images']);
}

// 获取上一篇和下一篇
$prev_case = $pdo->prepare("SELECT id, title FROM cases 
                           WHERE id < :id AND status = 1 
                           ORDER BY id DESC LIMIT 1");
$prev_case->execute([':id' => $case_id]);
$prev_case = $prev_case->fetch();

$next_case = $pdo->prepare("SELECT id, title FROM cases 
                           WHERE id > :id AND status = 1 
                           ORDER BY id ASC LIMIT 1");
$next_case->execute([':id' => $case_id]);
$next_case = $next_case->fetch();

// 获取相关案例
$related_cases = $pdo->prepare("SELECT id, title, cover_image, client 
                              FROM cases 
                              WHERE category_id = :category_id AND id != :id AND status = 1 
                              ORDER BY created_at DESC 
                              LIMIT 4");
$related_cases->execute([
    ':category_id' => $case['category_id'],
    ':id' => $case_id
]);
$related_cases = $related_cases->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($case['title']); ?> - 成功案例 - <?php echo $site_config['name']; ?></title>
    <meta name="description" content="<?php echo !empty($case['description']) ? htmlspecialchars($case['description']) : cut_str(strip_tags($case['content']), 150); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($case['title']); ?>,<?php echo htmlspecialchars($case['category_name']); ?>,成功案例">
    <!-- 引入外部资源 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/dist/lightgallery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/dist/css/lightgallery.min.css" rel="stylesheet">
    
    <!-- Tailwind 配置 -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#9CB93B',
                        secondary: '#333333',
                        accent: '#F5F5F5',
                        light: '#FFFFFF',
                        dark: '#222222'
                    }
                }
            }
        }
    </script>
    
    <!-- 自定义样式 -->
    <style type="text/tailwindcss">
        @layer utilities {
            .content-auto {
                content-visibility: auto;
            }
            .hover-lift {
                @apply hover:-translate-y-1 transition-all duration-300;
            }
            .card-shadow {
                box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
            }
            .section-padding {
                @apply py-16;
            }
            .case-content p {
                @apply mb-6 leading-relaxed;
            }
            .case-content img {
                @apply max-w-full h-auto my-8 rounded-lg;
            }
            .case-content h3 {
                @apply text-xl font-bold mb-4 mt-8 text-secondary;
            }
            .case-content ul {
                @apply list-disc pl-6 mb-6 space-y-2;
            }
            .gallery-item {
                @apply cursor-pointer overflow-hidden rounded-lg relative;
            }
            .gallery-item img {
                @apply transition-transform duration-500 hover:scale-110;
            }
            .gallery-overlay {
                @apply absolute inset-0 bg-primary/60 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center;
            }
        }
    </style>
</head>
<body class="font-sans text-secondary bg-light">
    <!-- 导航栏 -->
    <header id="navbar" class="fixed w-full top-0 z-50 transition-all duration-300 bg-light/95 shadow-sm">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <a href="index.html" class="flex items-center">
                    <img src="https://www.jas-expo.com/images/logo.png" alt="福州佳势展览有限公司logo" class="h-12 md:h-14">
                </a>
                
                <!-- 桌面导航 -->
                <nav class="hidden md:flex space-x-8">
                    <a href="index.html" class="text-secondary font-medium hover:text-primary transition-colors">首页</a>
                    <a href="about.html" class="text-secondary font-medium hover:text-primary transition-colors">关于我们</a>
                    <a href="services.html" class="text-secondary font-medium hover:text-primary transition-colors">业务范围</a>
                    <a href="cases.php" class="text-primary font-medium hover:text-primary/80 transition-colors">成功案例</a>
                    <a href="news.php" class="text-secondary font-medium hover:text-primary transition-colors">行业资讯</a>
                    <a href="contact.php" class="text-secondary font-medium hover:text-primary transition-colors">联系我们</a>
                </nav>
                
                <!-- 联系电话 -->
                <div class="hidden md:flex items-center text-secondary">
                    <i class="fa fa-phone-square text-primary mr-2 text-xl"></i>
                    <span class="font-medium">400-888-9999</span>
                </div>
                
                <!-- 移动端菜单按钮 -->
                <button id="menu-toggle" class="md:hidden text-secondary text-2xl">
                    <i class="fa fa-bars"></i>
                </button>
            </div>
        </div>
        
        <!-- 移动端导航菜单 -->
        <div id="mobile-menu" class="md:hidden hidden bg-light border-t">
            <div class="container mx-auto px-4 py-3 space-y-3">
                <a href="index.html" class="block py-2 text-secondary hover:text-primary transition-colors">首页</a>
                <a href="about.html" class="block py-2 text-secondary hover:text-primary transition-colors">关于我们</a>
                <a href="services.html" class="block py-2 text-secondary hover:text-primary transition-colors">业务范围</a>
                <a href="cases.php" class="block py-2 text-primary font-medium">成功案例</a>
                <a href="news.php" class="block py-2 text-secondary hover:text-primary transition-colors">行业资讯</a>
                <a href="contact.php" class="block py-2 text-secondary hover:text-primary transition-colors">联系我们</a>
                <div class="flex items-center pt-2 text-secondary border-t">
                    <i class="fa fa-phone-square text-primary mr-2"></i>
                    <span class="font-medium">400-888-9999</span>
                </div>
            </div>
        </div>
    </header>

    <!-- 页面标题 -->
    <section class="bg-primary/10 py-24 md:py-32">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-[clamp(2rem,4vw,3rem)] font-bold text-secondary mb-4">成功案例详情</h1>
                <div class="flex justify-center mt-8">
                    <nav class="flex text-sm" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="index.html" class="inline-flex items-center text-gray-600 hover:text-primary">
                                    <i class="fa fa-home mr-2"></i>
                                    首页
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <i class="fa fa-angle-right text-gray-400 mx-2"></i>
                                    <a href="cases.php" class="text-gray-600 hover:text-primary">成功案例</a>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <i class="fa fa-angle-right text-gray-400 mx-2"></i>
                                    <span class="text-primary"><?php echo cut_str($case['title'], 15); ?></span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- 案例内容 -->
    <section class="section-padding bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- 主内容区 -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl overflow-hidden card-shadow">
                        <!-- 案例主图 -->
                        <div class="relative">
                            <img src="<?php echo $site_config['cases_upload_path'] . $case['cover_image']; ?>" 
                                alt="<?php echo htmlspecialchars($case['title']); ?>" 
                                class="w-full h-auto">
                            <div class="absolute top-4 left-4">
                                <span class="bg-primary text-white px-4 py-1 rounded-full text-sm font-medium">
                                    <?php echo $case['category_name']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="p-6 md:p-8">
                            <h2 class="text-2xl md:text-3xl font-bold mb-6 text-secondary">
                                <?php echo htmlspecialchars($case['title']); ?>
                            </h2>
                            
                            <!-- 案例信息 -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 p-4 bg-accent rounded-xl">
                                <div>
                                    <p class="text-gray-500 text-sm mb-1">客户名称</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($case['client']); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm mb-1">案例分类</p>
                                    <p class="font-medium"><?php echo $case['category_name']; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm mb-1">发布时间</p>
                                    <p class="font-medium"><?php echo format_date($case['created_at']); ?></p>
                                </div>
                            </div>
                            
                            <!-- 案例简介 -->
                            <?php if (!empty($case['description'])): ?>
                            <div class="mb-8 p-4 bg-primary/5 rounded-xl">
                                <h3 class="text-lg font-bold mb-3 text-secondary flex items-center">
                                    <i class="fa fa-info-circle text-primary mr-2"></i> 案例简介
                                </h3>
                                <p class="text-gray-700">
                                    <?php echo nl2br(htmlspecialchars($case['description'])); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- 案例详情 -->
                            <div class="case-content text-gray-700 mb-10">
                                <h3 class="text-xl font-bold mb-4 text-secondary">案例详情</h3>
                                <?php echo $case['content']; ?>
                            </div>
                            
                            <!-- 案例图片集 -->
                            <?php if (!empty($case_images)): ?>
                            <div id="case-gallery" class="mb-10">
                                <h3 class="text-xl font-bold mb-6 text-secondary">案例图片</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <?php foreach ($case_images as $image): ?>
                                    <div class="gallery-item">
                                        <img src="<?php echo $site_config['cases_upload_path'] . $image; ?>" 
                                            alt="<?php echo htmlspecialchars($case['title']); ?>图片" 
                                            class="w-full h-64 object-cover">
                                        <div class="gallery-overlay">
                                            <i class="fa fa-search-plus text-white text-2xl"></i>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- 分享按钮 -->
                            <div class="pt-6 border-t border-gray-200">
                                <div class="flex flex-wrap items-center gap-4">
                                    <span class="text-gray-500">分享：</span>
                                    <a href="javascript:window.open('https://wx.qq.com/share?url='+encodeURIComponent(location.href)+'&title=<?php echo urlencode($case['title']); ?>', '_blank', 'width=600,height=400')" 
                                       class="bg-[#07C160]/10 hover:bg-[#07C160] text-[#07C160] hover:text-white w-9 h-9 rounded-full flex items-center justify-center transition-all">
                                        <i class="fa fa-weixin"></i>
                                    </a>
                                    <a href="javascript:window.open('http://service.weibo.com/share/share.php?url='+encodeURIComponent(location.href)+'&title=<?php echo urlencode($case['title']); ?>', '_blank', 'width=600,height=400')" 
                                       class="bg-[#E6162D]/10 hover:bg-[#E6162D] text-[#E6162D] hover:text-white w-9 h-9 rounded-full flex items-center justify-center transition-all">
                                        <i class="fa fa-weibo"></i>
                                    </a>
                                    <a href="javascript:window.open('https://twitter.com/intent/tweet?url='+encodeURIComponent(location.href)+'&text=<?php echo urlencode($case['title']); ?>', '_blank', 'width=600,height=400')" 
                                       class="bg-[#1DA1F2]/10 hover:bg-[#1DA1F2] text-[#1DA1F2] hover:text-white w-9 h-9 rounded-full flex items-center justify-center transition-all">
                                        <i class="fa fa-twitter"></i>
                                    </a>
                                    <a href="javascript:window.open('https://www.linkedin.com/shareArticle?url='+encodeURIComponent(location.href)+'&title=<?php echo urlencode($case['title']); ?>', '_blank', 'width=600,height=400')" 
                                       class="bg-[#0A66C2]/10 hover:bg-[#0A66C2] text-[#0A66C2] hover:text-white w-9 h-9 rounded-full flex items-center justify-center transition-all">
                                        <i class="fa fa-linkedin"></i>
                                    </a>
                                    <button id="copy-link" 
                                            class="bg-gray-100 hover:bg-gray-700 text-gray-700 hover:text-white w-9 h-9 rounded-full flex items-center justify-center transition-all">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 上一篇下一篇 -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-xl card-shadow">
                            <?php if ($prev_case): ?>
                            <a href="case-detail.php?id=<?php echo $prev_case['id']; ?>" class="flex items-center">
                                <i class="fa fa-angle-left text-primary text-xl mr-3"></i>
                                <div>
                                    <p class="text-gray-500 text-sm">上一篇</p>
                                    <p class="text-secondary font-medium hover:text-primary transition-colors">
                                        <?php echo htmlspecialchars($prev_case['title']); ?>
                                    </p>
                                </div>
                            </a>
                            <?php else: ?>
                            <div class="flex items-center">
                                <i class="fa fa-angle-left text-gray-300 text-xl mr-3"></i>
                                <div>
                                    <p class="text-gray-500 text-sm">上一篇</p>
                                    <p class="text-gray-400">没有了</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="bg-white p-6 rounded-xl card-shadow text-right">
                            <?php if ($next_case): ?>
                            <a href="case-detail.php?id=<?php echo $next_case['id']; ?>" class="flex items-center justify-end">
                                <div>
                                    <p class="text-gray-500 text-sm">下一篇</p>
                                    <p class="text-secondary font-medium hover:text-primary transition-colors">
                                        <?php echo htmlspecialchars($next_case['title']); ?>
                                    </p>
                                </div>
                                <i class="fa fa-angle-right text-primary text-xl ml-3"></i>
                            </a>
                            <?php else: ?>
                            <div class="flex items-center justify-end">
                                <div>
                                    <p class="text-gray-500 text-sm">下一篇</p>
                                    <p class="text-gray-400">没有了</p>
                                </div>
                                <i class="fa fa-angle-right text-gray-300 text-xl ml-3"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- 侧边栏 -->
                <div class="lg:col-span-1">
                    <!-- 咨询表单 -->
                    <div class="bg-primary/10 rounded-xl p-6 card-shadow mb-8">
                        <h3 class="text-lg font-bold mb-4 text-secondary">咨询此案例</h3>
                        <p class="text-gray-600 text-sm mb-4">
                            对该案例感兴趣？填写以下信息，我们将尽快与您联系
                        </p>
                        <form action="contact.php" method="get">
                            <input type="hidden" name="subject" value="咨询案例：<?php echo htmlspecialchars($case['title']); ?>">
                            <div class="mb-3">
                                <input type="text" name="name" placeholder="您的姓名" 
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm">
                            </div>
                            <div class="mb-3">
                                <input type="tel" name="phone" placeholder="联系电话" 
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm">
                            </div>
                            <div class="mb-4">
                                <textarea name="content" placeholder="请输入您的咨询内容" rows="3" 
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm"></textarea>
                            </div>
                            <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white font-medium px-4 py-2 rounded-lg transition-all hover-lift text-sm">
                                提交咨询
                            </button>
                        </form>
                    </div>
                    
                    <!-- 案例信息 -->
                    <div class="bg-white rounded-xl p-6 card-shadow mb-8">
                        <h3 class="text-lg font-bold mb-4 text-secondary">案例信息</h3>
                        <ul class="space-y-4">
                            <li class="flex justify-between pb-3 border-b border-gray-100">
                                <span class="text-gray-500">案例分类</span>
                                <span class="text-secondary font-medium">
                                    <a href="cases.php?category_id=<?php echo $case['category_id']; ?>" class="hover:text-primary transition-colors">
                                        <?php echo $case['category_name']; ?>
                                    </a>
                                </span>
                            </li>
                            <li class="flex justify-between pb-3 border-b border-gray-100">
                                <span class="text-gray-500">客户名称</span>
                                <span class="text-secondary"><?php echo htmlspecialchars($case['client']); ?></span>
                            </li>
                            <li class="flex justify-between pb-3 border-b border-gray-100">
                                <span class="text-gray-500">发布时间</span>
                                <span class="text-secondary"><?php echo format_date($case['created_at']); ?></span>
                            </li>
                            <li class="flex justify-between pb-3 border-b border-gray-100">
                                <span class="text-gray-500">浏览次数</span>
                                <span class="text-secondary"><?php echo $case['view_count']; ?></span>
                            </li>
                            <?php if ($case['is_recommend'] == 1): ?>
                            <li class="flex justify-between">
                                <span class="text-gray-500">推荐案例</span>
                                <span class="text-primary"><i class="fa fa-star"></i> 是</span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- 相关案例 -->
                    <div class="bg-white rounded-xl p-6 card-shadow">
                        <h3 class="text-lg font-bold mb-4 text-secondary flex items-center">
                            <i class="fa fa-link text-primary mr-2"></i> 相关案例
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($related_cases as $rel_case): ?>
                            <a href="case-detail.php?id=<?php echo $rel_case['id']; ?>" class="block group">
                                <div class="flex gap-3">
                                    <img src="<?php echo $site_config['cases_upload_path'] . $rel_case['cover_image']; ?>" 
                                        alt="<?php echo htmlspecialchars($rel_case['title']); ?>" 
                                        class="w-20 h-20 object-cover rounded-lg group-hover:opacity-90 transition-opacity">
                                    <div>
                                        <h4 class="font-medium text-secondary group-hover:text-primary transition-colors line-clamp-2 text-sm">
                                            <?php echo htmlspecialchars($rel_case['title']); ?>
                                        </h4>
                                        <p class="text-gray-500 text-xs mt-1">客户：<?php echo htmlspecialchars($rel_case['client']); ?></p>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 咨询区域 -->
    <section class="section-padding bg-accent">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="section-title">需要类似的解决方案？</h2>
                <p class="text-gray-600 max-w-2xl mx-auto mb-8">
                    我们拥有丰富的行业经验，可为您量身定制专业的展览展示解决方案，欢迎咨询
                </p>
                <a href="contact.php" class="inline-block bg-primary hover:bg-primary/90 text-white font-medium px-8 py-3 rounded-lg transition-all hover-lift">
                    联系我们 <i class="fa fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- 页脚 -->
    <footer class="bg-dark text-white pt-16 pb-8">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                <div>
                    <img src="https://www.jas-expo.com/images/logo.png" alt="福州佳势展览有限公司logo" class="h-14 mb-6">
                    <p class="text-gray-400 mb-6">
                        福州佳势展览有限公司是一家集展览展示设计、制作、搭建于一体的专业展览服务企业，为客户提供全方位的展览解决方案。
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-primary transition-colors">
                            <i class="fa fa-weixin text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-primary transition-colors">
                            <i class="fa fa-weibo text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-primary transition-colors">
                            <i class="fa fa-linkedin text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-primary transition-colors">
                            <i class="fa fa-youtube-play text-xl"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">快速链接</h4>
                    <ul class="space-y-3">
                        <li><a href="index.html" class="text-gray-400 hover:text-primary transition-colors">首页</a></li>
                        <li><a href="about.html" class="text-gray-400 hover:text-primary transition-colors">关于我们</a></li>
                        <li><a href="services.html" class="text-gray-400 hover:text-primary transition-colors">业务范围</a></li>
                        <li><a href="cases.php" class="text-gray-400 hover:text-primary transition-colors">成功案例</a></li>
                        <li><a href="news.php" class="text-gray-400 hover:text-primary transition-colors">行业资讯</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-primary transition-colors">联系我们</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">业务范围</h4>
                    <ul class="space-y-3">
                        <li><a href="services.html#exhibition" class="text-gray-400 hover:text-primary transition-colors">展览展陈</a></li>
                        <li><a href="services.html#signage" class="text-gray-400 hover:text-primary transition-colors">标牌工程</a></li>
                        <li><a href="services.html#event" class="text-gray-400 hover:text-primary transition-colors">活动布置</a></li>
                        <li><a href="services.html#cultural" class="text-gray-400 hover:text-primary transition-colors">文化墙</a></li>
                        <li><a href="services.html#promotion" class="text-gray-400 hover:text-primary transition-colors">宣传导视</a></li>
                        <li><a href="services.html#equipment" class="text-gray-400 hover:text-primary transition-colors">设备器材</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">联系我们</h4>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <i class="fa fa-map-marker text-primary mt-1 mr-3"></i>
                            <span class="text-gray-400">福建省福州市仓山区浦上大道276号仓山万达C区1#楼17层</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fa fa-phone text-primary mr-3"></i>
                            <span class="text-gray-400">400-888-9999</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fa fa-envelope text-primary mr-3"></i>
                            <span class="text-gray-400">45025075@qq.com</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fa fa-clock-o text-primary mr-3"></i>
                            <span class="text-gray-400">周一至周五: 9:00 - 18:00</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-500 text-sm mb-4 md:mb-0">
                        © 2023 <?php echo $site_config['name']; ?> 版权所有 | 闽ICP备XXXXXXXX号
                    </p>
                    <div class="flex space-x-6">
                        <a href="#" class="text-gray-500 hover:text-primary text-sm transition-colors">隐私政策</a>
                        <a href="#" class="text-gray-500 hover:text-primary text-sm transition-colors">服务条款</a>
                        <a href="#" class="text-gray-500 hover:text-primary text-sm transition-colors">网站地图</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- 返回顶部按钮 -->
    <button id="back-to-top" class="fixed bottom-8 right-8 bg-primary text-white w-12 h-12 rounded-full flex items-center justify-center shadow-lg opacity-0 invisible transition-all duration-300 hover:bg-primary/90">
        <i class="fa fa-chevron-up"></i>
    </button>

    <!-- JavaScript -->
    <script>
        // 页面加载完成后初始化图片画廊
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化图片画廊
            if (document.getElementById('case-gallery')) {
                lightgallery(document.getElementById('case-gallery'), {
                    plugins: [lgZoom, lgThumbnail],
                    speed: 500,
                    thumbnail: true
                });
            }
            
            // 移动端菜单切换
            const menuToggle = document.getElementById('menu-toggle');
            const mobileMenu = document.getElementById('mobile-menu');
            
            menuToggle.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
                const icon = menuToggle.querySelector('i');
                if (mobileMenu.classList.contains('hidden')) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                } else {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                }
            });
            
            // 导航栏滚动效果
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 100) {
                    navbar.classList.add('shadow-md');
                    navbar.classList.remove('bg-light/95');
                    navbar.classList.add('bg-light');
                } else {
                    navbar.classList.remove('shadow-md');
                    navbar.classList.add('bg-light/95');
                    navbar.classList.remove('bg-light');
                }
            });
            
            // 返回顶部按钮
            const backToTopButton = document.getElementById('back-to-top');
            
            window.addEventListener('scroll', () => {
                if (window.scrollY > 500) {
                    backToTopButton.classList.remove('opacity-0', 'invisible');
                    backToTopButton.classList.add('opacity-100', 'visible');
                } else {
                    backToTopButton.classList.add('opacity-0', 'invisible');
                    backToTopButton.classList.remove('opacity-100', 'visible');
                }
            });
            
            backToTopButton.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // 复制链接功能
            document.getElementById('copy-link').addEventListener('click', () => {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    const button = document.getElementById('copy-link');
                    const originalIcon = button.innerHTML;
                    button.innerHTML = '<i class="fa fa-check"></i>';
                    setTimeout(() => {
                        button.innerHTML = originalIcon;
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>
    