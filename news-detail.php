<?php
require_once 'config.php';

// 检查新闻ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("无效的新闻ID");
}

$news_id = intval($_GET['id']);

// 获取新闻详情
$stmt = $pdo->prepare("SELECT n.*, c.name as category_name 
                       FROM news n 
                       LEFT JOIN news_category c ON n.category_id = c.id 
                       WHERE n.id = :id AND n.status = 1");
$stmt->execute([':id' => $news_id]);
$news = $stmt->fetch();

if (!$news) {
    die("未找到该新闻");
}

// 更新浏览次数
$pdo->prepare("UPDATE news SET view_count = view_count + 1 WHERE id = :id")
    ->execute([':id' => $news_id]);

// 处理评论提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $user_name = trim($_POST['user_name'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    
    if (!empty($user_name) && !empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO news_comment (news_id, user_name, content, parent_id, status) 
                              VALUES (:news_id, :user_name, :content, :parent_id, 1)");
        $stmt->execute([
            ':news_id' => $news_id,
            ':user_name' => $user_name,
            ':content' => $content,
            ':parent_id' => $parent_id
        ]);
        
        // 更新评论数量
        $pdo->prepare("UPDATE news SET comment_count = comment_count + 1 WHERE id = :id")
            ->execute([':id' => $news_id]);
        
        // 刷新页面
        header("Location: news-detail.php?id={$news_id}#comments");
        exit;
    }
}

// 获取评论
$comments = $pdo->prepare("SELECT * FROM news_comment 
                          WHERE news_id = :news_id AND status = 1 
                          ORDER BY parent_id ASC, created_at ASC");
$comments->execute([':news_id' => $news_id]);
$comments = $comments->fetchAll();

// 整理评论为嵌套结构
$comment_list = [];
foreach ($comments as $comment) {
    if ($comment['parent_id'] == 0) {
        $comment['replies'] = [];
        $comment_list[] = $comment;
    } else {
        // 查找父评论并添加回复
        foreach ($comment_list as &$parent_comment) {
            if ($parent_comment['id'] == $comment['parent_id']) {
                $parent_comment['replies'][] = $comment;
                break;
            }
        }
    }
}

// 获取上一篇和下一篇
$prev_news = $pdo->prepare("SELECT id, title FROM news 
                           WHERE id < :id AND status = 1 
                           ORDER BY id DESC LIMIT 1");
$prev_news->execute([':id' => $news_id]);
$prev_news = $prev_news->fetch();

$next_news = $pdo->prepare("SELECT id, title FROM news 
                           WHERE id > :id AND status = 1 
                           ORDER BY id ASC LIMIT 1");
$next_news->execute([':id' => $news_id]);
$next_news = $next_news->fetch();

// 获取相关资讯
$related_news = $pdo->prepare("SELECT id, title, cover_image, created_at 
                              FROM news 
                              WHERE category_id = :category_id AND id != :id AND status = 1 
                              ORDER BY created_at DESC 
                              LIMIT 4");
$related_news->execute([
    ':category_id' => $news['category_id'],
    ':id' => $news_id
]);
$related_news = $related_news->fetchAll();

// 获取热门资讯
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
    <title><?php echo htmlspecialchars($news['title']); ?> - <?php echo $site_config['name']; ?></title>
    <meta name="description" content="<?php echo !empty($news['summary']) ? htmlspecialchars($news['summary']) : cut_str(strip_tags($news['content']), 150); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($news['title']); ?>,<?php echo htmlspecialchars($news['category_name']); ?>">
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
            .article-content p {
                @apply mb-6 leading-relaxed;
            }
            .article-content img {
                @apply max-w-full h-auto my-8 rounded-lg;
            }
            .article-content h3 {
                @apply text-xl font-bold mb-4 mt-8 text-secondary;
            }
            .article-content ul {
                @apply list-disc pl-6 mb-6 space-y-2;
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
                <h1 class="text-[clamp(2rem,4vw,3rem)] font-bold text-secondary mb-4">行业资讯详情</h1>
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
                                    <a href="news.php" class="text-gray-600 hover:text-primary">行业资讯</a>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <i class="fa fa-angle-right text-gray-400 mx-2"></i>
                                    <span class="text-primary"><?php echo cut_str($news['title'], 15); ?></span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- 文章内容 -->
    <section class="section-padding bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- 主内容区 -->
                <div class="lg:col-span-2">
                    <article class="bg-white rounded-xl overflow-hidden card-shadow p-6 md:p-8">
                        <div class="mb-8">
                            <span class="inline-block bg-primary/10 text-primary text-sm font-medium px-4 py-1 rounded-full mb-4">
                                <?php echo $news['category_name']; ?>
                            </span>
                            <h2 class="text-2xl md:text-3xl font-bold mb-6 text-secondary">
                                <?php echo htmlspecialchars($news['title']); ?>
                            </h2>
                            <div class="flex flex-wrap items-center text-gray-500 text-sm gap-4">
                                <span class="flex items-center"><i class="fa fa-calendar-o mr-2"></i> <?php echo format_date($news['created_at']); ?></span>
                                <span class="flex items-center"><i class="fa fa-user-o mr-2"></i> <?php echo htmlspecialchars($news['author']); ?></span>
                                <span class="flex items-center"><i class="fa fa-eye mr-2"></i> <?php echo $news['view_count']; ?> 阅读</span>
                                <span class="flex items-center"><i class="fa fa-comment-o mr-2"></i> <?php echo $news['comment_count']; ?> 评论</span>
                            </div>
                        </div>
                        
                        <?php if (!empty($news['cover_image'])): ?>
                        <div class="mb-8">
                            <img src="<?php echo $site_config['upload_url'] . $news['cover_image']; ?>" 
                                alt="<?php echo htmlspecialchars($news['title']); ?>" 
                                class="w-full h-auto rounded-lg">
                        </div>
                        <?php endif; ?>
                        
                        <div class="article-content text-gray-700">
                            <?php echo $news['content']; ?>
                        </div>
                        
                        <!-- 文章标签 -->
                        <?php 
                        // 简单的标签提取，实际应用中可以创建标签表关联
                        $tags = ['展览行业', '数字化转型', '行业报告', 'VR技术', '线上线下融合'];
                        ?>
                        <div class="mt-12 pt-6 border-t border-gray-200">
                            <div class="flex flex-wrap gap-2">
                                <span class="text-gray-500">标签：</span>
                                <?php foreach ($tags as $tag): ?>
                                <a href="news.php?keyword=<?php echo urlencode($tag); ?>" 
                                   class="bg-gray-100 hover:bg-primary/10 text-gray-700 hover:text-primary px-3 py-1 rounded-full text-sm transition-colors">
                                    <?php echo $tag; ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- 分享按钮 -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex flex-wrap items-center gap-4">
                                <span class="text-gray-500">分享：</span>
                                <a href="javascript:window.open('https://wx.qq.com/share?url='+encodeURIComponent(location.href)+'&title=<?php echo urlencode($news['title']); ?>', '_blank', 'width=600,height=400')" 
                                   class="bg-[#07C160]/10 hover:bg-[#07C160] text-[#07C160] hover:text-white w-9 h-9 rounded-full flex items-center justify-center transition-all">
                                    <i class="fa fa-weixin"></i>
                                </a>
                                <a href="javascript:window.open('http://service.weibo.com/share/share.php?url='+encodeURIComponent(location.href)+'&title=<?php echo urlencode($news['title']); ?>', '_blank', 'width=600,height=400')" 
                                   class="bg-[#E6162D]/10 hover:bg-[#E6162D] text-[#E6162D] hover:text-white w-9 h-9 rounded-full flex items-center justify-center transition-all">
                                    <i class="fa fa-weibo"></i>
                                </a>
                                <a href="javascript:window.open('https://twitter.com/intent/tweet?url='+encodeURIComponent(location.href)+'&text=<?php echo urlencode($news['title']); ?>', '_blank', 'width=600,height=400')" 
                                   class="bg-[#1DA1F2]/10 hover:bg-[#1DA1F2] text-[#1DA1F2] hover:text-white w-9 h-9 rounded-full flex items-center justify-center transition-all">
                                    <i class="fa fa-twitter"></i>
                                </a>
                                <a href="javascript:window.open('https://www.linkedin.com/shareArticle?url='+encodeURIComponent(location.href)+'&title=<?php echo urlencode($news['title']); ?>', '_blank', 'width=600,height=400')" 
                                   class="bg-[#0A66C2]/10 hover:bg-[#0A66C2] text-[#0A66C2] hover:text-white w-9 h-9 rounded-full flex items-center justify-center transition-all">
                                    <i class="fa fa-linkedin"></i>
                                </a>
                                <button id="copy-link" 
                                        class="bg-gray-100 hover:bg-gray-700 text-gray-700 hover:text-white w-9 h-9 rounded-full flex items-center justify-center transition-all">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- 上一篇下一篇 -->
                        <div class="mt-10 pt-6 border-t border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <p class="text-gray-500 mb-2 text-sm">上一篇</p>
                                    <?php if ($prev_news): ?>
                                    <a href="news-detail.php?id=<?php echo $prev_news['id']; ?>" 
                                       class="text-secondary hover:text-primary font-medium transition-colors">
                                        <i class="fa fa-angle-left mr-1"></i> <?php echo htmlspecialchars($prev_news['title']); ?>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-gray-400">没有了</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <p class="text-gray-500 mb-2 text-sm">下一篇</p>
                                    <?php if ($next_news): ?>
                                    <a href="news-detail.php?id=<?php echo $next_news['id']; ?>" 
                                       class="text-secondary hover:text-primary font-medium transition-colors">
                                        <?php echo htmlspecialchars($next_news['title']); ?> <i class="fa fa-angle-right ml-1"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-gray-400">没有了</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 评论区 -->
                        <div id="comments" class="mt-12 pt-8 border-t border-gray-200">
                            <h3 class="text-xl font-bold mb-6 text-secondary">评论 (<?php echo count($comments); ?>)</h3>
                            
                            <!-- 评论输入框 -->
                            <div class="mb-10">
                                <form method="post" action="news-detail.php?id=<?php echo $news_id; ?>#comments">
                                    <input type="hidden" name="parent_id" value="0">
                                    <div class="mb-4">
                                        <input type="text" name="user_name" placeholder="请输入您的昵称" required
                                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary">
                                    </div>
                                    <textarea name="content" placeholder="请输入您的评论..." rows="4" required
                                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"></textarea>
                                    <div class="flex justify-end mt-4">
                                        <button type="submit" name="submit_comment" 
                                            class="bg-primary hover:bg-primary/90 text-white font-medium px-6 py-2 rounded-md transition-all hover-lift">
                                            发表评论
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- 评论列表 -->
                            <?php if (count($comment_list) > 0): ?>
                            <div class="space-y-6">
                                <?php foreach ($comment_list as $comment): ?>
                                <div class="flex gap-4 pb-6 border-b border-gray-100">
                                    <img src="https://picsum.photos/id/<?php echo 1000 + $comment['id'] % 50; ?>/100/100" alt="用户头像" class="w-12 h-12 rounded-full object-cover">
                                    <div class="flex-grow">
                                        <div class="flex justify-between items-start mb-2">
                                            <h4 class="font-medium text-secondary"><?php echo htmlspecialchars($comment['user_name']); ?></h4>
                                            <span class="text-gray-500 text-sm"><?php echo format_date($comment['created_at']); ?></span>
                                        </div>
                                        <p class="text-gray-700 mb-3">
                                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                        </p>
                                        <button class="reply-btn text-primary text-sm hover:underline" 
                                                data-comment-id="<?php echo $comment['id']; ?>">
                                            <i class="fa fa-reply mr-1"></i> 回复
                                        </button>
                                        
                                        <!-- 回复表单 (默认隐藏) -->
                                        <div class="reply-form hidden mt-4 p-4 bg-gray-50 rounded-lg">
                                            <form method="post" action="news-detail.php?id=<?php echo $news_id; ?>#comments">
                                                <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                                                <div class="mb-3">
                                                    <input type="text" name="user_name" placeholder="请输入您的昵称" required
                                                        class="w-full px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm">
                                                </div>
                                                <textarea name="content" placeholder="请输入回复内容..." rows="2" required
                                                    class="w-full px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm"></textarea>
                                                <div class="flex justify-end mt-2 gap-2">
                                                    <button type="button" class="cancel-reply text-gray-500 text-sm px-3 py-1 border border-gray-300 rounded hover:bg-gray-100">
                                                        取消
                                                    </button>
                                                    <button type="submit" name="submit_comment" 
                                                        class="bg-primary hover:bg-primary/90 text-white text-sm px-3 py-1 rounded">
                                                        回复
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <!-- 评论的回复 -->
                                        <?php if (!empty($comment['replies'])): ?>
                                        <div class="mt-4 ml-6 pl-4 border-l-2 border-gray-200 space-y-4">
                                            <?php foreach ($comment['replies'] as $reply): ?>
                                            <div class="flex gap-3">
                                                <img src="https://picsum.photos/id/<?php echo 2000 + $reply['id'] % 50; ?>/100/100" alt="回复用户头像" class="w-10 h-10 rounded-full object-cover">
                                                <div>
                                                    <div class="flex justify-between items-start mb-1">
                                                        <h5 class="font-medium text-secondary text-sm"><?php echo htmlspecialchars($reply['user_name']); ?></h5>
                                                        <span class="text-gray-500 text-xs"><?php echo format_date($reply['created_at']); ?></span>
                                                    </div>
                                                    <p class="text-gray-700 text-sm">
                                                        <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-10 bg-accent rounded-xl">
                                <p class="text-gray-500">暂无评论，欢迎发表您的看法</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
                
                <!-- 侧边栏 -->
                <div class="lg:col-span-1">
                    <!-- 作者信息 -->
                    <div class="bg-white rounded-xl p-6 card-shadow mb-8">
                        <h3 class="text-lg font-bold mb-4 text-secondary">作者信息</h3>
                        <div class="flex items-center gap-4">
                            <img src="https://picsum.photos/id/1005/100/100" alt="<?php echo htmlspecialchars($news['author']); ?>" class="w-16 h-16 rounded-full object-cover">
                            <div>
                                <h4 class="font-medium text-secondary"><?php echo htmlspecialchars($news['author']); ?></h4>
                                <p class="text-gray-500 text-sm">行业分析师</p>
                            </div>
                        </div>
                        <p class="text-gray-600 mt-4 text-sm">
                            专注展览行业研究与分析，拥有多年行业经验，致力于为读者提供有深度的行业洞察。
                        </p>
                    </div>
                    
                    <!-- 相关资讯 -->
                    <div class="bg-white rounded-xl p-6 card-shadow mb-8">
                        <h3 class="text-lg font-bold mb-4 text-secondary flex items-center">
                            <i class="fa fa-link text-primary mr-2"></i> 相关资讯
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($related_news as $rel_news): ?>
                            <a href="news-detail.php?id=<?php echo $rel_news['id']; ?>" class="block group">
                                <div class="flex gap-3">
                                    <?php if (!empty($rel_news['cover_image'])): ?>
                                    <img src="<?php echo $site_config['upload_url'] . $rel_news['cover_image']; ?>" 
                                        alt="<?php echo htmlspecialchars($rel_news['title']); ?>" 
                                        class="w-20 h-20 object-cover rounded-lg group-hover:opacity-90 transition-opacity">
                                    <?php endif; ?>
                                    <div>
                                        <h4 class="font-medium text-secondary group-hover:text-primary transition-colors line-clamp-2">
                                            <?php echo htmlspecialchars($rel_news['title']); ?>
                                        </h4>
                                        <p class="text-gray-500 text-sm mt-1"><i class="fa fa-calendar-o mr-1"></i> <?php echo format_date($rel_news['created_at']); ?></p>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- 热门资讯 -->
                    <div class="bg-white rounded-xl p-6 card-shadow mb-8">
                        <h3 class="text-lg font-bold mb-4 text-secondary flex items-center">
                            <i class="fa fa-fire text-primary mr-2"></i> 热门资讯
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($hot_news as $hot): ?>
                            <div class="flex gap-3">
                                <span class="bg-gray-100 text-gray-700 w-6 h-6 rounded-full flex items-center justify-center text-sm font-medium flex-shrink-0">
                                    <?php echo array_search($hot, $hot_news) + 1; ?>
                                </span>
                                <h4 class="font-medium text-secondary hover:text-primary transition-colors line-clamp-2">
                                    <a href="news-detail.php?id=<?php echo $hot['id']; ?>">
                                        <?php echo htmlspecialchars($hot['title']); ?>
                                    </a>
                                </h4>
                            </div>
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

    <!-- 订阅资讯 -->
    <section class="section-padding bg-primary/10">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-2xl md:text-3xl font-bold text-secondary mb-4">订阅行业资讯</h2>
                <p class="text-gray-600 max-w-2xl mx-auto mb-8">
                    订阅我们的资讯推送，及时获取展览行业最新动态、展会信息和专业见解，把握市场机遇
                </p>
                <form class="max-w-xl mx-auto flex flex-col sm:flex-row gap-3">
                    <input type="email" placeholder="请输入您的邮箱地址" class="flex-grow px-4 py-3 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary">
                    <button type="submit" class="bg-primary hover:bg-primary/90 text-white font-medium px-6 py-3 rounded-md transition-all hover-lift whitespace-nowrap">
                        立即订阅
                    </button>
                </form>
                <p class="text-gray-500 text-sm mt-4">
                    我们尊重您的隐私，不会向第三方分享您的信息
                </p>
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
        
        // 评论回复功能
        document.querySelectorAll('.reply-btn').forEach(button => {
            button.addEventListener('click', () => {
                const commentId = button.getAttribute('data-comment-id');
                const replyForm = button.nextElementSibling;
                
                // 隐藏所有其他回复表单
                document.querySelectorAll('.reply-form').forEach(form => {
                    if (form !== replyForm) {
                        form.classList.add('hidden');
                    }
                });
                
                // 切换当前回复表单显示状态
                replyForm.classList.toggle('hidden');
            });
        });
        
        // 取消回复
        document.querySelectorAll('.cancel-reply').forEach(button => {
            button.addEventListener('click', () => {
                button.closest('.reply-form').classList.add('hidden');
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
    </script>
</body>
</html>
    