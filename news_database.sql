-- 创建数据库
CREATE DATABASE IF NOT EXISTS jas_expo_news DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 选择数据库
USE jas_expo_news;

-- 创建新闻表
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT '新闻标题',
    category_id INT NOT NULL COMMENT '分类ID',
    author VARCHAR(100) NOT NULL COMMENT '作者',
    summary TEXT COMMENT '摘要',
    content LONGTEXT NOT NULL COMMENT '正文内容',
    cover_image VARCHAR(255) COMMENT '封面图片',
    view_count INT DEFAULT 0 COMMENT '浏览次数',
    comment_count INT DEFAULT 0 COMMENT '评论次数',
    is_recommend TINYINT DEFAULT 0 COMMENT '是否推荐 0-不推荐 1-推荐',
    status TINYINT DEFAULT 1 COMMENT '状态 0-草稿 1-发布',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='新闻表';

-- 创建新闻分类表
CREATE TABLE IF NOT EXISTS news_category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '分类名称',
    sort_order INT DEFAULT 0 COMMENT '排序',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='新闻分类表';

-- 创建评论表
CREATE TABLE IF NOT EXISTS news_comment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL COMMENT '新闻ID',
    user_name VARCHAR(100) NOT NULL COMMENT '用户名',
    content TEXT NOT NULL COMMENT '评论内容',
    parent_id INT DEFAULT 0 COMMENT '父评论ID，0表示顶级评论',
    status TINYINT DEFAULT 1 COMMENT '状态 0-待审核 1-已通过 2-已拒绝',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='新闻评论表';

-- 创建管理员表
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    password VARCHAR(255) NOT NULL COMMENT '密码(加密存储)',
    nickname VARCHAR(100) COMMENT '昵称',
    last_login_time DATETIME COMMENT '最后登录时间',
    status TINYINT DEFAULT 1 COMMENT '状态 0-禁用 1-正常',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 插入默认分类
INSERT INTO news_category (name, sort_order) VALUES 
('行业动态', 1),
('趋势分析', 2),
('展会信息', 3),
('技术应用', 4),
('公司新闻', 5);

-- 插入默认管理员(密码: admin123)
INSERT INTO admin (username, password, nickname) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '管理员');
    