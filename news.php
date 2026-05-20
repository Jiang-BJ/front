<?php
require_once 'config.php';

// 获取分类
$categories = $pdo->query("SELECT * FROM news_category ORDER BY sort_order ASC")->fetchAll();

// 获取当前分类ID
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// 分页处理
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page - 1) * $site_config['news_per_page'];

// 构建查询条件
$where = 'status = 1';
$params = [];

if ($category_id > 0) {
    $where .= ' AND category_id = :category_id';
    $params[':category_id'] = $category_id;
}

// 搜索功能
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
if (!empty($keyword)) {
    $where .= ' AND (title LIKE :keyword OR summary LIKE :keyword OR content LIKE :keyword)';
    $params[':keyword'] = "%{$keyword}%";
}

// 获取总记录数
$total_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM news WHERE {$where}");
$total_stmt->execute($params);
$total = $total_stmt->fetch()['total'];
$total_pages = ceil($total / $site_config['news_per_page']);

// 获取新闻列表
$stmt = $pdo->prepare("SELECT n.*, c.name as category_name 
                       FROM news n 
                       LEFT JOIN news_category c ON n.category_id = c.id 
                       WHERE {$where} 
                       ORDER BY n.created_at DESC 
                       LIMIT :start, :per_page");
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $site_config['news_per_page'], PDO::PARAM_INT);
$stmt->execute($params);
$news_list = $stmt->fetchAll();

// 获取热门新闻
$hot_news = $pdo->query("SELECT id, title, view_count 
                         FROM news 
                         WHERE status = 1 
                         ORDER BY view_count DESC 
                         LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>行业资讯 - <?php echo $site_config['name']; ?></title>
    <meta name="description" content="福州佳势展览有限公司行业资讯，提供展览行业最新动态、趋势分析、展会信息等内容">
    <meta name="keywords" content="展览行业资讯,展会动态,展览趋势,佳势展览">
    <!-- 引入外部资源 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    
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
            .section-title {
                @apply text-[clamp(1.8rem,3vw,2.5rem)] font-bold text-secondary mb-4;
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
                    <a href="cases.html" class="text-secondary font-medium hover:text-primary transition-colors">成功案例</a>
                    <a href="news.php" class="text-primary font-medium hover:text-primary/80 transition-colors">行业资讯</a>
                    <a href="contact.html" class="text-secondary font-medium hover:text-primary transition-colors">联系我们</a>
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
                <a href="cases.html" class="block py-2 text-secondary hover:text-primary transition-colors">成功案例</a>
                <a href="news.php" class="block py-2 text-primary font-medium">行业资讯</a>
                <a href="contact.html" class="block py-2 text-secondary hover:text-primary transition-colors">联系我们</a>
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
                <h1 class="text-[clamp(2rem,4vw,3rem)] font-bold text-secondary mb-4">行业资讯</h1>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    了解展览行业最新动态、前沿技术、市场趋势和展会信息，把握行业发展脉搏
                </p>
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
                                    <span class="text-primary">行业资讯</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- 新闻内容区 -->
    <section class="section-padding bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- 左侧主内容区 -->
                <div class="lg:col-span-2">
                    <!-- 搜索和筛选 -->
                    <div class="bg-accent rounded-xl p-6 mb-8">
                        <form method="get" action="news.php" class="flex flex-col md:flex-row gap-4">
                            <div class="flex-grow">
                                <div class="relative">
                                    <input type="text" name="keyword" placeholder="请输入关键词搜索..." 
                                        value="<?php echo htmlspecialchars($keyword); ?>"
                                        class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary">
                                    <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            <div class="md:w-48">
                                <select name="category_id" 
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary appearance-none bg-white">
                                    <option value="0">全部分类</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo $category['name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="md:w-24">
                                <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white font-medium px-4 py-3 rounded-lg transition-all hover-lift">
                                    搜索
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- 新闻列表 -->
                    <?php if (count($news_list) > 0): ?>
                    <div class="space-y-8">
                        <?php foreach ($news_list as $news): ?>
                        <article class="bg-white rounded-xl overflow-hidden card-shadow hover-lift">
                            <a href="news-detail.php?id=<?php echo $news['id']; ?>">
                                <div class="md:flex">
                                    <?php if (!empty($news['cover_image'])): ?>
                                    <div class="md:w-1/3">
                                        <img src="<?php echo $site_config['upload_url'] . $news['cover_image']; ?>" 
                                            alt="<?php echo htmlspecialchars($news['title']); ?>" 
                                            class="w-full h-64 md:h-full object-cover">
                                    </div>
                                    <?php endif; ?>
                                    <div class="<?php echo !empty($news['cover_image']) ? 'md:w-2/3' : 'md:w-full'; ?> p-6">
                                        <div class="flex items-center mb-3">
                                            <span class="bg-primary/10 text-primary text-xs font-medium px-3 py-1 rounded-full">
                                                <?php echo $news['category_name']; ?>
                                            </span>
                                            <span class="text-gray-500 text-xs ml-3">
                                                <i class="fa fa-calendar-o mr-1"></i> <?php echo format_date($news['created_at']); ?>
                                            </span>
                                        </div>
                                        <h3 class="text-xl font-bold mb-3 text-secondary hover:text-primary transition-colors">
                                            <?php echo htmlspecialchars($news['title']); ?>
                                        </h3>
                                        <p class="text-gray-600 mb-4 line-clamp-2">
                                            <?php echo !empty($news['summary']) ? htmlspecialchars($news['summary']) : cut_str(strip_tags($news['content']), 150); ?>
                                        </p>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-500 text-sm">
                                                <i class="fa fa-eye mr-1"></i> <?php echo $news['view_count']; ?> 阅读
                                                <i class="fa fa-comment-o ml-3 mr-1"></i> <?php echo $news['comment_count']; ?> 评论
                                            </span>
                                            <span class="text-primary font-medium text-sm hover:underline">
                                                阅读全文 <i class="fa fa-angle-right ml-1"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- 分页 -->
                    <?php if ($total_pages > 1): ?>
                    <div class="mt-12">
                        <nav class="flex justify-center" aria-label="分页">
                            <ul class="inline-flex -space-x-px">
                                <li>
                                    <a href="?page=1<?php echo $category_id ? '&category_id=' . $category_id : ''; ?><?php echo $keyword ? '&keyword=' . urlencode($keyword) : ''; ?>" 
                                       class="py-2 px-4 border rounded-l-lg bg-white text-gray-500 hover:bg-gray-50">
                                        <i class="fa fa-angle-double-left"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="?page=<?php echo max(1, $page - 1); ?><?php echo $category_id ? '&category_id=' . $category_id : ''; ?><?php echo $keyword ? '&keyword=' . urlencode($keyword) : ''; ?>" 
                                       class="py-2 px-4 border bg-white text-gray-500 hover:bg-gray-50">
                                        <i class="fa fa-angle-left"></i>
                                    </a>
                                </li>
                                
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<li><span class="py-2 px-4 border bg-white text-gray-300">...</span></li>';
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    $active = $i == $page ? 'bg-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50';
                                    echo '<li>';
                                    echo '<a href="?page=' . $i . ($category_id ? '&category_id=' . $category_id : '') . ($keyword ? '&keyword=' . urlencode($keyword) : '') . '" ';
                                    echo 'class="py-2 px-4 border ' . $active . '">';
                                    echo $i;
                                    echo '</a></li>';
                                }
                                
                                if ($end_page < $total_pages) {
                                    echo '<li><span class="py-2 px-4 border bg-white text-gray-300">...</span></li>';
                                }
                                ?>
                                
                                <li>
                                    <a href="?page=<?php echo min($total_pages, $page + 1); ?><?php echo $category_id ? '&category_id=' . $category_id : ''; ?><?php echo $keyword ? '&keyword=' . urlencode($keyword) : ''; ?>" 
                                       class="py-2 px-4 border bg-white text-gray-500 hover:bg-gray-50">
                                        <i class="fa fa-angle-right"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="?page=<?php echo $total_pages; ?><?php echo $category_id ? '&category_id=' . $category_id : ''; ?><?php echo $keyword ? '&keyword=' . urlencode($keyword) : ''; ?>" 
                                       class="py-2 px-4 border rounded-r-lg bg-white text-gray-500 hover:bg-gray-50">
                                        <i class="fa fa-angle-double-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="text-center py-16 bg-accent rounded-xl">
                        <i class="fa fa-search text-5xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-medium text-gray-500 mb-2">未找到相关资讯</h3>
                        <p class="text-gray-400">请尝试其他关键词或分类进行搜索</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- 右侧边栏 -->
                <div class="lg:col-span-1">
                    <!-- 分类导航 -->
                    <div class="bg-white rounded-xl p-6 card-shadow mb-8">
                        <h3 class="text-lg font-bold mb-4 text-secondary flex items-center">
                            <i class="fa fa-th-list text-primary mr-2"></i> 资讯分类
                        </h3>
                        <ul class="space-y-2">
                            <li>
                                <a href="news.php" class="flex justify-between items-center p-3 rounded-lg hover:bg-primary/10 transition-colors <?php echo $category_id == 0 ? 'bg-primary/10 text-primary' : 'text-gray-700'; ?>">
                                    <span>全部资讯</span>
                                    <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                                        <?php echo $total; ?>
                                    </span>
                                </a>
                            </li>
                            <?php foreach ($categories as $category): ?>
                            <?php 
                            // 获取每个分类的新闻数量
                            $cat_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM news WHERE category_id = :id AND status = 1");
                            $cat_count_stmt->execute([':id' => $category['id']]);
                            $cat_count = $cat_count_stmt->fetch()['count'];
                            ?>
                            <li>
                                <a href="news.php?category_id=<?php echo $category['id']; ?>" 
                                   class="flex justify-between items-center p-3 rounded-lg hover:bg-primary/10 transition-colors <?php echo $category_id == $category['id'] ? 'bg-primary/10 text-primary' : 'text-gray-700'; ?>">
                                    <span><?php echo $category['name']; ?></span>
                                    <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                                        <?php echo $cat_count; ?>
                                    </span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- 热门资讯 -->
                    <div class="bg-white rounded-xl p-6 card-shadow mb-8">
                        <h3 class="text-lg font-bold mb-4 text-secondary flex items-center">
                            <i class="fa fa-fire text-primary mr-2"></i> 热门资讯
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($hot_news as $news): ?>
                            <div class="flex gap-3">
                                <span class="bg-gray-100 text-gray-700 w-6 h-6 rounded-full flex items-center justify-center text-sm font-medium flex-shrink-0">
                                    <?php echo array_search($news, $hot_news) + 1; ?>
                                </span>
                                <h4 class="font-medium text-secondary hover:text-primary transition-colors line-clamp-2">
                                    <a href="news-detail.php?id=<?php echo $news['id']; ?>">
                                        <?php echo htmlspecialchars($news['title']); ?>
                                    </a>
                                </h4>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- 推荐资讯 -->
                    <div class="bg-white rounded-xl p-6 card-shadow mb-8">
                        <h3 class="text-lg font-bold mb-4 text-secondary flex items-center">
                            <i class="fa fa-star text-primary mr-2"></i> 推荐资讯
                        </h3>
                        <div class="space-y-4">
                            <?php 
                            $recommend_news = $pdo->query("SELECT id, title, cover_image, created_at 
                                                       FROM news 
                                                       WHERE status = 1 AND is_recommend = 1 
                                                       ORDER BY created_at DESC 
                                                       LIMIT 3")->fetchAll();
                            foreach ($recommend_news as $news): 
                            ?>
                            <a href="news-detail.php?id=<?php echo $news['id']; ?>" class="block group">
                                <?php if (!empty($news['cover_image'])): ?>
                                <img src="<?php echo $site_config['upload_url'] . $news['cover_image']; ?>" 
                                    alt="<?php echo htmlspecialchars($news['title']); ?>" 
                                    class="w-full h-40 object-cover rounded-lg mb-3 group-hover:opacity-90 transition-opacity">
                                <?php endif; ?>
                                <h4 class="font-medium text-secondary group-hover:text-primary transition-colors line-clamp-2">
                                    <?php echo htmlspecialchars($news['title']); ?>
                                </h4>
                                <p class="text-gray-500 text-sm mt-1">
                                    <i class="fa fa-calendar-o mr-1"></i> <?php echo format_date($news['created_at']); ?>
                                </p>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- 订阅资讯 -->
                    <div class="bg-primary/10 rounded-xl p-6 card-shadow">
                        <h3 class="text-lg font-bold mb-4 text-secondary">订阅资讯</h3>
                        <p class="text-gray-600 text-sm mb-4">
                            输入您的邮箱，订阅行业最新资讯，第一时间获取展览行业动态
                        </p>
                        <form>
                            <div class="mb-3">
                                <input type="email" placeholder="请输入您的邮箱地址" 
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary">
                            </div>
                            <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white font-medium px-4 py-3 rounded-lg transition-all hover-lift">
                                立即订阅
                            </button>
                        </form>
                        <p class="text-gray-500 text-xs mt-3">
                            我们尊重您的隐私，不会向第三方分享您的信息
                        </p>
                    </div>
                </div>
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
                        <li><a href="cases.html" class="text-gray-400 hover:text-primary transition-colors">成功案例</a></li>
                        <li><a href="news.php" class="text-gray-400 hover:text-primary transition-colors">行业资讯</a></li>
                        <li><a href="contact.html" class="text-gray-400 hover:text-primary transition-colors">联系我们</a></li>
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
    </script>
</body>
</html>
    