-- Đầu tiên, tạo cơ sở dữ liệu
CREATE DATABASE IF NOT EXISTS webblog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Sau đó, chọn cơ sở dữ liệu để sử dụng
USE webblog;

-- Bảng Người dùng
CREATE TABLE IF NOT EXISTS Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Sẽ được hash
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive', 'unverified') NOT NULL DEFAULT 'unverified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng Chủ đề
CREATE TABLE IF NOT EXISTS Categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
);

-- Bảng Bài viết
CREATE TABLE IF NOT EXISTS Posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- Bảng Bình luận
CREATE TABLE IF NOT EXISTS Comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    status ENUM('visible', 'hidden', 'reported') NOT NULL DEFAULT 'visible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES Posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- Bảng trung gian Post_Categories (Mối quan hệ N-N)
CREATE TABLE IF NOT EXISTS Post_Categories (
    post_id INT NOT NULL,
    category_id INT NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES Posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES Categories(id) ON DELETE CASCADE
);

-- Insert an admin user (password: admin123)
INSERT INTO Users (username, email, password, role, status)
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert a regular user (password: user123)
INSERT INTO Users (username, email, password, role, status)
VALUES ('user', 'user@example.com', '$2y$10$9.7ym1sLbJnNM8HlycxSM.TfWvBRUBIpQsZMAd4I1F.YfxfxV0uFu', 'user', 'active');
