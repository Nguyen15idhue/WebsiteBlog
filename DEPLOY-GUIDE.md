# Hướng dẫn triển khai WebsiteBlog trên VPS Linux

Tài liệu này hướng dẫn chi tiết cách triển khai cả backend API và frontend của WebsiteBlog trên cùng một VPS Linux để chúng chạy tự động mà không cần chạy lệnh PHP server mỗi lần khởi động.

## Các file cấu hình

Dự án có sẵn các file cấu hình:
- `nginx-api.conf`: Cấu hình Nginx cho backend API
- `nginx-frontend.conf`: Cấu hình Nginx cho frontend
- `setup-vps.sh`: Script tự động cài đặt và cấu hình VPS

## Triển khai tự động trên VPS

### Bước 1: Chuẩn bị

1. VPS chạy Linux (Ubuntu/Debian khuyên dùng)
2. Đã cấu hình tên miền trỏ về IP của VPS
   - api.yourdomain.com (cho backend API)
   - yourdomain.com (cho frontend)

### Bước 2: Upload script cài đặt

Upload file `setup-vps.sh` lên VPS của bạn:

```bash
scp setup-vps.sh user@your-vps-ip:~/
```

### Bước 3: Chạy script cài đặt

```bash
ssh user@your-vps-ip
chmod +x ~/setup-vps.sh
sudo ~/setup-vps.sh
```

Script sẽ hỏi bạn một số câu hỏi về việc cài đặt MySQL, SSL, Composer, và Git. Trả lời theo nhu cầu của bạn.

### Bước 4: Upload mã nguồn

Sau khi script chạy xong, upload mã nguồn lên VPS:

#### Backend API:

```bash
# Dùng SCP
scp -r WebsiteBlog/* user@your-vps-ip:/var/www/websiteblog/

# Hoặc dùng Git
ssh user@your-vps-ip
cd /var/www/websiteblog
git clone https://github.com/yourusername/WebsiteBlog.git .
```

#### Frontend:

```bash
# Dùng SCP
scp -r WebsiteBlogFrontend/* user@your-vps-ip:/var/www/websiteblog-frontend/

# Hoặc dùng Git
ssh user@your-vps-ip
cd /var/www/websiteblog-frontend
git clone https://github.com/yourusername/WebsiteBlogFrontend.git .
```

### Bước 5: Cài đặt dependencies và cấu hình

```bash
# Backend API
ssh user@your-vps-ip
cd /var/www/websiteblog
composer install

# Tạo và cấu hình file .env
cp .env.example .env
nano .env  # Chỉnh sửa cấu hình database, JWT_SECRET, v.v.

# Import database nếu cần
mysql -u dbuser -p dbname < database/setup_webblog.sql
```

## Kiểm tra kết quả

Sau khi hoàn thành, bạn có thể truy cập:

- Backend API: http://api.yourdomain.com/ (hoặc https:// nếu đã cài SSL)
- Frontend: http://yourdomain.com/ (hoặc https:// nếu đã cài SSL)

API sẽ tự động chạy mỗi khi VPS khởi động mà không cần phải chạy lệnh PHP server.

## Khắc phục sự cố

### Kiểm tra log

```bash
# Log của Nginx
sudo tail -f /var/log/nginx/api-error.log
sudo tail -f /var/log/nginx/frontend-error.log

# Log của PHP
sudo tail -f /var/log/php8.1-fpm.log
```

### Khởi động lại dịch vụ

```bash
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

## Cấu hình HTTPS (nếu chưa chọn trong quá trình cài đặt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d api.yourdomain.com
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

## Bảo mật

1. Cài đặt tường lửa:
```bash
sudo apt install ufw
sudo ufw allow 'Nginx Full'
sudo ufw allow 'OpenSSH'
sudo ufw enable
```

2. Cấu hình fail2ban để bảo vệ SSH:
```bash
sudo apt install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

---

Với cách này, cả backend API và frontend sẽ chạy tự động trên VPS mỗi khi khởi động, không cần chạy lệnh PHP server thủ công nữa.
