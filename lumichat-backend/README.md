---

## 👩‍💻 Developer Setup — Clone & Install

### ✅ Prerequisites
- **PHP** 8.2+ and **Composer** 2.x  
- **Node.js** 18/20 LTS and **npm**  
- **MySQL** 8+ (or MariaDB 10.5+)  
- **Python** 3.10 (recommended for Rasa 3.x) and **pip**  
- Git

> Note: We **do not commit** `.venv/`, `rasa-bot/models/`, `*.tar.gz`, `/vendor`, `/node_modules`, or `.env`. Each dev creates these locally.

---

### 🔁 Clone the Repo
```bash
git clone https://github.com/Nyzo0310/Lumichat_v1.7.git
cd Lumichat_v1.7
🧱 Laravel Backend Setup
bash
Copy code
cd lumichat-backend

# PHP deps
composer install

# Env + app key
cp .env.example .env
php artisan key:generate

# Edit .env → set your DB credentials:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=lumichat
# DB_USERNAME=root
# DB_PASSWORD=secret

# DB schema
php artisan migrate   # add --seed if seeds are available

# Frontend assets
npm install
npm run dev

# (Linux/macOS) ensure writable:
# sudo chmod -R 775 storage bootstrap/cache

# Run Laravel
php artisan serve
# -> http://127.0.0.1:8000
🤖 Rasa Bot Setup
bash
Copy code
cd lumichat-backend/rasa-bot

# Create & activate a virtual env (do NOT commit .venv/)
python -m venv .venv

# Windows:
.venv\Scripts\activate
# macOS/Linux:
# source .venv/bin/activate

# Install Python deps
# If requirements.txt is missing, create it on a machine that already works:  pip freeze > requirements.txt
pip install -r requirements.txt

# Train NLU/Core
rasa train

# Run in two terminals:
# 1) Rasa server (REST API):
rasa run --enable-api -p 5005
# 2) Actions server (if using custom actions):
rasa run actions -p 5055
🌐 Laravel ↔️ Rasa Connection
Add these to lumichat-backend/.env (or adjust to your ports):

ini
Copy code
# Rasa
RASA_BASE_URL=http://127.0.0.1:5005
RASA_REST_WEBHOOK=/webhooks/rest/webhook
RASA_ACTION_SERVER=http://127.0.0.1:5055/webhook
RASA_TIMEOUT=20
Your Laravel code should POST to:

bash
Copy code
${RASA_BASE_URL}${RASA_REST_WEBHOOK}
with a JSON body like:

json
Copy code
{ "sender": "<user-id>", "message": "hello" }
🧪 Common Commands
Laravel

bash
Copy code
php artisan serve                    # run app
php artisan migrate                  # apply migrations
npm run dev                          # Vite dev
npm run build                        # production build
Rasa

bash
Copy code
rasa train                           # retrain after editing data/*
rasa run --enable-api -p 5005        # Rasa server
rasa run actions -p 5055             # custom actions
🛠️ Troubleshooting
“Class or file permissions” (Linux/macOS):
chmod -R 775 storage bootstrap/cache

“Cannot connect to DB”: verify .env DB_* values and run php artisan migrate.

“Rasa not responding”: ensure both rasa run …5005 and rasa run actions …5055 are running.

Yellow “M” in VS Code but git status clean: Developer: Reload Window.

Large files rejected on push: artifacts/venv/models are intentionally ignored; do not commit them.

📦 What Not to Commit (already in .gitignore)
bash
Copy code
.venv/
rasa-bot/models/
*.tar.gz
/vendor/
/node_modules/
.env
/storage/*.key
/bootstrap/cache/
/storage/logs/
/storage/framework/*