# LumiCHAT

LumiCHAT is a Laravel + Rasa–powered mental-health chatbot.

---

## ✨ Features
- ⚡ Real-time chat interface with **Rasa** integration
- 💾 Chat history saved to the database
- 🧩 Responsive UI built with **Tailwind CSS**
- 🔒 Secure and private messaging

---

## 🧰 Prerequisites
- 🐘 **PHP** 8.2+ and **Composer** 2.x  
- 🟩 **Node.js** 18/20 LTS and **npm**  
- 🐬 **MySQL** 8+ (or MariaDB 10.5+)  
- 🐍 **Python** 3.10 (recommended for Rasa 3.x) and **pip**  
- 🔧 **Git**

> We **do not commit** `.venv/`, `rasa-bot/models/`, `*.tar.gz`, `/vendor`, `/node_modules`, or `.env`.  
> Each developer creates these locally.

---

## 🧬 Clone the project
```bash
git clone https://github.com/Nyzo0310/Lumichat_v1.7.git
cd Lumichat_v1.7
cd lumichat-backend

# 1) PHP dependencies
composer install

# 2) Environment
cp .env.example .env
php artisan key:generate

# 3) Configure database in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=lumichat
# DB_USERNAME=root
# DB_PASSWORD=secret

# 4) Migrate (add --seed if seeds are available)
php artisan migrate

# 5) Frontend assets (Vite + Tailwind)
npm install
npm run dev   # use `npm run build` for production

# 6) (Linux/macOS) ensure writable folders
# sudo chmod -R 775 storage bootstrap/cache
