<?php
// 确保会话已启动
if (!isset($_SESSION)) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="../index.php">
            <i class="fa fa-cogs me-2"></i>新闻案例管理系统
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fa fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../admin/admin_edit.php?id=<?php echo $_SESSION['admin_id']; ?>">
                            <i class="fa fa-user me-2"></i>个人资料
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">
                            <i class="fa fa-sign-out me-2"></i>退出登录
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>