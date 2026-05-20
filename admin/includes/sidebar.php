<?php
// 获取当前页面路径，用于高亮显示当前菜单项
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<div class="sidebar p-3">
    <ul class="nav flex-column">
        <li class="nav-item mb-1">
            <a class="nav-link <?php echo ($current_dir == '' && $current_page == 'index.php') ? 'active' : ''; ?>" href="<?php BASE_URL?>/admin/index.php">
                <i class="fa fa-dashboard me-2"></i> 控制台
            </a>
        </li>
        
        <li class="nav-item mb-1">
            <a class="nav-link <?php echo ($current_dir == 'news' && in_array($current_page, ['news_list.php', 'news_add.php', '../news_edit.php'])) ? 'active' : ''; ?>" href="<?php BASE_URL?>/admin/news/news_list.php">
                <i class="fa fa-newspaper-o me-2"></i> 新闻管理
            </a>
        </li>
        
        <li class="nav-item mb-1">
            <a class="nav-link <?php echo ($current_dir == 'news' && $current_page == 'news_category.php') ? 'active' : ''; ?>" href="<?php BASE_URL?>/admin/news/news_category.php">
                <i class="fa fa-th-list me-2"></i> 新闻分类
            </a>
        </li>
        
        <li class="nav-item mb-1">
            <a class="nav-link <?php echo ($current_dir == 'cases' && in_array($current_page, ['case_list.php', 'case_add.php', 'case_edit.php'])) ? 'active' : ''; ?>" href="<?php BASE_URL?>/admin/cases/case_list.php">
                <i class="fa fa-briefcase me-2"></i> 案例管理
            </a>
        </li>
        
        <li class="nav-item mb-1">
            <a class="nav-link <?php echo ($current_dir == 'cases' && $current_page == 'case_category.php') ? 'active' : ''; ?>" href="<?php BASE_URL?>/admin/cases/case_category.php">
                <i class="fa fa-tags me-2"></i> 案例分类
            </a>
        </li>
        
        <li class="nav-item mb-1">
            <a class="nav-link <?php echo ($current_dir == 'admin' && in_array($current_page, ['admin_list.php', 'admin_add.php', 'admin_edit.php'])) ? 'active' : ''; ?>" href="<?php BASE_URL?>/admin/admin/admin_list.php">
                <i class="fa fa-users me-2"></i> 管理员管理
            </a>
        </li>
        
        <li class="nav-item mt-5">
            <a class="nav-link text-danger" href="../logout.php">
                <i class="fa fa-sign-out me-2"></i> 退出登录
            </a>
        </li>
    </ul>
</div>