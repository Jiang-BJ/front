-- 选择数据库
USE jas_expo_news;

-- 创建成功案例表
CREATE TABLE IF NOT EXISTS cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT '案例标题',
    category_id INT NOT NULL COMMENT '案例分类ID',
    client VARCHAR(100) NOT NULL COMMENT '客户名称',
    description TEXT COMMENT '案例描述',
    content LONGTEXT NOT NULL COMMENT '案例详情',
    cover_image VARCHAR(255) COMMENT '封面图片',
    images TEXT COMMENT '案例图片集，用逗号分隔',
    view_count INT DEFAULT 0 COMMENT '浏览次数',
    is_recommend TINYINT DEFAULT 0 COMMENT '是否推荐 0-不推荐 1-推荐',
    status TINYINT DEFAULT 1 COMMENT '状态 0-隐藏 1-显示',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='成功案例表';

-- 创建案例分类表
CREATE TABLE IF NOT EXISTS case_category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '分类名称',
    sort_order INT DEFAULT 0 COMMENT '排序',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='案例分类表';

-- 创建联系表单提交表
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '姓名',
    phone VARCHAR(20) NOT NULL COMMENT '电话',
    email VARCHAR(100) COMMENT '邮箱',
    subject VARCHAR(255) NOT NULL COMMENT '主题',
    content TEXT NOT NULL COMMENT '留言内容',
    source VARCHAR(50) COMMENT '来源页面',
    ip_address VARCHAR(50) COMMENT 'IP地址',
    status TINYINT DEFAULT 0 COMMENT '状态 0-未处理 1-已处理',
    reply TEXT COMMENT '回复内容',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='联系表单提交表';

-- 插入默认案例分类
INSERT INTO case_category (name, sort_order) VALUES 
('展览展陈', 1),
('标牌工程', 2),
('活动布置', 3),
('文化墙', 4),
('宣传导视', 5);
