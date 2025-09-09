#!/bin/bash

# Script cài đặt và cấu hình Nginx + PHP-FPM cho WebsiteBlog
# Chạy với quyền sudo: sudo bash setup.sh

# Kiểm tra quyền sudo
if [ "$(id -u)" -ne 0 ]; then
   echo "Script này cần được chạy với quyền sudo" 
   exit 1
fi

# Biến cấu hình
BACKEND_DOMAIN="api.yourdomain.com"
FRONTEND_DOMAIN="yourdomain.com"
BACKEND_PATH="/var/www/websiteblog"
FRONTEND_PATH="/var/www/websiteblog-frontend"

# Màu sắc
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Bắt đầu cài đặt WebsiteBlog ===${NC}"

# Cập nhật hệ thống
echo -e "${YELLOW}Đang cập nhật hệ thống...${NC}"
apt update && apt upgrade -y

# Cài đặt Nginx và PHP-FPM
echo -e "${YELLOW}Đang cài đặt Nginx và PHP-FPM...${NC}"
apt install -y nginx php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd php8.1-intl

# Cài đặt MySQL nếu chưa có
read -p "Bạn có muốn cài đặt MySQL không? (y/n): " install_mysql
if [[ $install_mysql == "y" ]]; then
    echo -e "${YELLOW}Đang cài đặt MySQL...${NC}"
    apt install -y mysql-server
    
    echo -e "${YELLOW}Thiết lập bảo mật cho MySQL...${NC}"
    mysql_secure_installation
    
    echo -e "${YELLOW}Tạo database cho ứng dụng...${NC}"
    read -p "Nhập tên database: " db_name
    read -p "Nhập tên user database: " db_user
    read -p "Nhập mật khẩu user database: " db_pass
    
    mysql -e "CREATE DATABASE ${db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -e "CREATE USER '${db_user}'@'localhost' IDENTIFIED BY '${db_pass}';"
    mysql -e "GRANT ALL PRIVILEGES ON ${db_name}.* TO '${db_user}'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
fi

# Tạo thư mục cho backend và frontend
echo -e "${YELLOW}Đang tạo thư mục cho ứng dụng...${NC}"
mkdir -p $BACKEND_PATH
mkdir -p $FRONTEND_PATH

# Tạo cấu hình Nginx cho backend
echo -e "${YELLOW}Đang tạo cấu hình Nginx cho backend API...${NC}"
cat > /etc/nginx/sites-available/websiteblog-api << EOL
server {
    listen 80;
    server_name $BACKEND_DOMAIN;
    root $BACKEND_PATH/public;
    
    index index.php;
    charset utf-8;
    
    # Cấu hình logs
    access_log /var/log/nginx/api-access.log;
    error_log /var/log/nginx/api-error.log;
    
    # Cấu hình CORS cho API
    add_header 'Access-Control-Allow-Origin' '*' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
    add_header 'Access-Control-Allow-Headers' 'X-Requested-With, Content-Type, Accept, Origin, Authorization' always;
    add_header 'Access-Control-Allow-Credentials' 'true' always;
    
    # Xử lý OPTIONS requests cho CORS preflight
    if (\$request_method = 'OPTIONS') {
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'X-Requested-With, Content-Type, Accept, Origin, Authorization' always;
        add_header 'Access-Control-Allow-Credentials' 'true' always;
        add_header 'Content-Type' 'text/plain charset=UTF-8';
        add_header 'Content-Length' 0;
        return 204;
    }
    
    # Chuyển tất cả request không tồn tại đến index.php
    location / {
        try_files \$uri \$uri/ /index.php\$is_args\$args;
    }
    
    # Xử lý PHP qua PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param PHP_VALUE "upload_max_filesize = 100M \n post_max_size = 100M";
        fastcgi_param HTTP_PROXY "";
        fastcgi_intercept_errors on;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
    }
    
    # Deny access to các file hệ thống
    location ~ /\.ht {
        deny all;
    }
    
    # Deny access to các file .git, .env
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOL

# Tạo cấu hình Nginx cho frontend
echo -e "${YELLOW}Đang tạo cấu hình Nginx cho frontend...${NC}"
cat > /etc/nginx/sites-available/websiteblog-frontend << EOL
server {
    listen 80;
    server_name $FRONTEND_DOMAIN www.$FRONTEND_DOMAIN;
    root $FRONTEND_PATH/public;
    
    index index.php index.html;
    charset utf-8;
    
    # Cấu hình logs
    access_log /var/log/nginx/frontend-access.log;
    error_log /var/log/nginx/frontend-error.log;
    
    # Chuyển tất cả request không tồn tại đến index.php
    location / {
        try_files \$uri \$uri/ /index.php\$is_args\$args;
    }
    
    # Cấu hình cache cho các file tĩnh
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|webp|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
    
    # Xử lý PHP qua PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param PHP_VALUE "upload_max_filesize = 100M \n post_max_size = 100M";
        fastcgi_param HTTP_PROXY "";
        fastcgi_intercept_errors on;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
    }
    
    # Deny access to các file hệ thống
    location ~ /\.ht {
        deny all;
    }
    
    # Deny access to các file .git, .env
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOL

# Kích hoạt các site
echo -e "${YELLOW}Đang kích hoạt các cấu hình Nginx...${NC}"
ln -sf /etc/nginx/sites-available/websiteblog-api /etc/nginx/sites-enabled/
ln -sf /etc/nginx/sites-available/websiteblog-frontend /etc/nginx/sites-enabled/

# Kiểm tra cấu hình Nginx
nginx -t

# Khởi động lại Nginx
systemctl restart nginx

# Cài đặt Certbot (Let's Encrypt SSL) nếu cần
read -p "Bạn có muốn cài đặt SSL cho các domain không? (y/n): " install_ssl
if [[ $install_ssl == "y" ]]; then
    echo -e "${YELLOW}Đang cài đặt Certbot...${NC}"
    apt install -y certbot python3-certbot-nginx
    
    echo -e "${YELLOW}Đang cấu hình SSL cho backend API...${NC}"
    certbot --nginx -d $BACKEND_DOMAIN
    
    echo -e "${YELLOW}Đang cấu hình SSL cho frontend...${NC}"
    certbot --nginx -d $FRONTEND_DOMAIN -d www.$FRONTEND_DOMAIN
    
    echo -e "${YELLOW}Đang thiết lập tự động gia hạn SSL...${NC}"
    echo "0 3 * * * /usr/bin/certbot renew --quiet" | crontab -
fi

# Thiết lập quyền cho thư mục web
echo -e "${YELLOW}Đang thiết lập quyền cho thư mục...${NC}"
chown -R www-data:www-data $BACKEND_PATH
chown -R www-data:www-data $FRONTEND_PATH
chmod -R 755 $BACKEND_PATH
chmod -R 755 $FRONTEND_PATH

# Cài đặt Composer nếu cần
read -p "Bạn có muốn cài đặt Composer không? (y/n): " install_composer
if [[ $install_composer == "y" ]]; then
    echo -e "${YELLOW}Đang cài đặt Composer...${NC}"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    php -r "unlink('composer-setup.php');"
fi

# Cài đặt Git nếu cần
read -p "Bạn có muốn cài đặt Git không? (y/n): " install_git
if [[ $install_git == "y" ]]; then
    echo -e "${YELLOW}Đang cài đặt Git...${NC}"
    apt install -y git
fi

# Hoàn tất
echo -e "${GREEN}=== Cài đặt hoàn tất ===${NC}"
echo -e "${GREEN}Backend API:${NC} http://$BACKEND_DOMAIN"
echo -e "${GREEN}Frontend:${NC} http://$FRONTEND_DOMAIN"
echo ""
echo -e "${YELLOW}Tiếp theo, bạn cần:${NC}"
echo "1. Upload mã nguồn của backend API vào $BACKEND_PATH"
echo "2. Upload mã nguồn frontend vào $FRONTEND_PATH"
echo "3. Tạo file .env cho backend API"
echo "4. Chạy composer install trong thư mục backend"
echo "5. Cấu hình database nếu cần"
echo ""
echo -e "${YELLOW}Thư mục log:${NC}"
echo "- Backend API: /var/log/nginx/api-access.log và api-error.log"
echo "- Frontend: /var/log/nginx/frontend-access.log và frontend-error.log"
echo ""
echo -e "${GREEN}Chúc bạn thành công!${NC}"
