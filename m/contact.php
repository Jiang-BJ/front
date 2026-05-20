<?php
require_once 'config.php';

$success = false;
$error = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    // 验证表单数据
    if (empty($name)) {
        $error = '请输入您的姓名';
    } elseif (empty($phone)) {
        $error = '请输入您的联系电话';
    } elseif (empty($subject)) {
        $error = '请输入咨询主题';
    } elseif (empty($content)) {
        $error = '请输入咨询内容';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的邮箱地址';
    } else {
        try {
            // 获取IP地址
            $ip_address = $_SERVER['REMOTE_ADDR'];
            // 获取来源页面
            $source = $_SERVER['HTTP_REFERER'] ?? '直接访问';
            
            // 保存到数据库
            $stmt = $pdo->prepare("INSERT INTO contact_messages 
                                 (name, phone, email, subject, content, source, ip_address) 
                                 VALUES (:name, :phone, :email, :subject, :content, :source, :ip_address)");
            $stmt->execute([
                ':name' => $name,
                ':phone' => $phone,
                ':email' => $email,
                ':subject' => $subject,
                ':content' => $content,
                ':source' => $source,
                ':ip_address' => $ip_address
            ]);
            
            $success = true;
            // 清空表单
            $name = $phone = $email = $subject = $content = '';
        } catch (PDOException $e) {
            $error = '提交失败，请稍后再试：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>联系我们 - <?php echo $site_config['name']; ?></title>
    <meta name="description" content="联系福州佳势展览有限公司，咨询展览展示设计、制作、搭建等服务，我们将竭诚为您服务">
    <meta name="keywords" content="佳势展览,联系我们,展览服务咨询,福州展览公司">
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
                    <a href="cases.php" class="text-secondary font-medium hover:text-primary transition-colors">成功案例</a>
                    <a href="news.php" class="text-secondary font-medium hover:text-primary transition-colors">行业资讯</a>
                    <a href="contact.php" class="text-primary font-medium hover:text-primary/80 transition-colors">联系我们</a>
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
                <a href="cases.php" class="block py-2 text-secondary hover:text-primary transition-colors">成功案例</a>
                <a href="news.php" class="block py-2 text-secondary hover:text-primary transition-colors">行业资讯</a>
                <a href="contact.php" class="block py-2 text-primary font-medium">联系我们</a>
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
                <h1 class="text-[clamp(2rem,4vw,3rem)] font-bold text-secondary mb-4">联系我们</h1>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    无论您有任何疑问或需求，都可以通过以下方式联系我们，我们将尽快回复您
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
                                    <span class="text-primary">联系我们</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- 联系内容区 -->
    <section class="section-padding bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- 联系信息 -->
                <div>
                    <h2 class="section-title">联系方式</h2>
                    <p class="text-gray-600 mb-8">
                        福州佳势展览有限公司是一家集展览展示设计、制作、搭建于一体的专业展览服务企业，
                        为客户提供全方位的展览解决方案。欢迎随时联系我们，我们将竭诚为您服务。
                    </p>
                    
                    <div class="space-y-6 mb-10">
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary flex-shrink-0">
                                <i class="fa fa-map-marker text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-secondary mb-1">公司地址</h3>
                                <p class="text-gray-600">福建省福州市仓山区浦上大道276号仓山万达C区1#楼17层</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary flex-shrink-0">
                                <i class="fa fa-phone text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-secondary mb-1">联系电话</h3>
                                <p class="text-gray-600">400-888-9999</p>
                                <p class="text-gray-600 mt-1">0591-88888888</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary flex-shrink-0">
                                <i class="fa fa-envelope text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-secondary mb-1">电子邮箱</h3>
                                <p class="text-gray-600">45025075@qq.com</p>
                                <p class="text-gray-600 mt-1">info@jas-expo.com</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary flex-shrink-0">
                                <i class="fa fa-clock-o text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-secondary mb-1">工作时间</h3>
                                <p class="text-gray-600">周一至周五: 9:00 - 18:00</p>
                                <p class="text-gray-600 mt-1">周六至周日: 休息</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 社交媒体 -->
                    <div>
                        <h3 class="text-lg font-medium text-secondary mb-4">关注我们</h3>
                        <div class="flex space-x-4">
                            <a href="#" class="w-10 h-10 bg-[#07C160]/10 hover:bg-[#07C160] text-[#07C160] hover:text-white rounded-full flex items-center justify-center transition-all">
                                <i class="fa fa-weixin"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-[#E6162D]/10 hover:bg-[#E6162D] text-[#E6162D] hover:text-white rounded-full flex items-center justify-center transition-all">
                                <i class="fa fa-weibo"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-[#1DA1F2]/10 hover:bg-[#1DA1F2] text-[#1DA1F2] hover:text-white rounded-full flex items-center justify-center transition-all">
                                <i class="fa fa-twitter"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-[#0A66C2]/10 hover:bg-[#0A66C2] text-[#0A66C2] hover:text-white rounded-full flex items-center justify-center transition-all">
                                <i class="fa fa-linkedin"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-[#FF0000]/10 hover:bg-[#FF0000] text-[#FF0000] hover:text-white rounded-full flex items-center justify-center transition-all">
                                <i class="fa fa-youtube-play"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- 联系表单 -->
                <div>
                    <div class="bg-accent rounded-xl p-6 md:p-8 card-shadow">
                        <h2 class="section-title">发送消息</h2>
                        <p class="text-gray-600 mb-6">
                            请填写以下表单，我们将尽快与您联系
                        </p>
                        
                        <?php if ($success): ?>
                        <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-6">
                            <i class="fa fa-check-circle mr-2"></i> 您的消息已成功提交，我们将尽快与您联系，感谢您的咨询！
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                        <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-6">
                            <i class="fa fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="post" action="contact.php" class="space-y-4">
                            <div>
                                <label for="name" class="block text-gray-700 mb-2">姓名 <span class="text-red-500">*</span></label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                    placeholder="请输入您的姓名" required>
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-gray-700 mb-2">电话 <span class="text-red-500">*</span></label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                    placeholder="请输入您的联系电话" required>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-gray-700 mb-2">邮箱</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                    placeholder="请输入您的邮箱地址（选填）">
                            </div>
                            
                            <div>
                                <label for="subject" class="block text-gray-700 mb-2">主题 <span class="text-red-500">*</span></label>
                                <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject); ?>"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                    placeholder="请输入咨询主题" required>
                            </div>
                            
                            <div>
                                <label for="content" class="block text-gray-700 mb-2">内容 <span class="text-red-500">*</span></label>
                                <textarea id="content" name="content" rows="5"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary"
                                    placeholder="请详细描述您的需求或问题" required><?php echo htmlspecialchars($content); ?></textarea>
                            </div>
                            
                            <div>
                                <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white font-medium px-6 py-3 rounded-lg transition-all hover-lift">
                                    提交消息
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 地图 -->
    <section class="h-96 bg-gray-200">
        <!-- 这里可以嵌入地图，例如百度地图、高德地图或谷歌地图 -->
        <div class="w-full h-full flex items-center justify-center">
            <img src="https://picsum.photos/id/1031/1200/600" alt="公司位置地图" class="w-full h-full object-cover">
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
    