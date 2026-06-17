# 六、啟動專案
```bash
cd /Users/tsanyen/Documents/GitHub/website
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
npm run build
```
# 七、SSL 憑證（HTTPS）
安裝 Homebrew 版 Certbot
```bash
brew install certbot
# 取得憑證
sudo certbot --nginx -d tsanyen.com -d www.tsanyen.com
```