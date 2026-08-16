# signup.binnii.com

Binnii 註冊站(staged registration,`../specs/saas/build-signup-staged-registration.md`):
兩步驟註冊寫入**本機 SQLite 暫存**、email 驗證、`signup:push` worker 把已驗證的註冊
push 到 `api.binnii.com` 的內部端點建立租戶,最後以一次性 handoff token 交接到
`app.binnii.com` 自動登入。

- **本 repo 沒有任何可寫的 MySQL 連線**:唯一的 MySQL 存取是 `mysql_ro`
  (`users(email, deleted_at)` 欄位級唯讀,查重用),程式層另有 ConnectionGuard 擋非 SELECT。
- **本 repo 不提供任何被其他系統呼叫的端點**——純對人網站 + 出向 worker。
- **MySQL schema 擁有者是 `app.binnii.com`**(本 repo 沒有可寫 MySQL 連線,無從 migrate);
  本站**私有的 SQLite 暫存庫**是唯一例外,migration 放在 `database/migrations/sqlite/`
  (跑在 `signup` 連線上),讓暫存庫保有正常的增量 migration 能力。
- 官網(binnii.com)是純靜態站,與本 repo 無關。
- `.env`:`APP_URL`、`APP_CONSOLE_URL`(handoff 轉址)、`SIGNUP_DB_PATH`、
  `MYSQL_RO_*`、`SIGNUP_INTAKE_URL` / `SIGNUP_INTAKE_SECRET` / `SIGNUP_INTAKE_CLIENT`。

## 佈署

- nginx:`deploy/nginx-signup.conf`(root 指到 `<repo>/public`,標準 Laravel try_files)。
- push worker:`deploy/binnii-signup-push.service`(systemd 常駐,每 10 秒一輪);
  cron:`deploy/crontab`(每 5 分鐘 `signup:pull-markets`、每日 `signup:purge`)。
- MySQL 帳號:`deploy/mysql-grant.sql` —— 只有 `users(email, deleted_at)` 的欄位級 SELECT。
- SQLite 檔:`/var/lib/binnii-signup/signup.sqlite`(`www-data:www-data`、檔 660、目錄 770,
  不在 `public/`、不進 repo);SQLite migration 一律
  `php artisan migrate --database=signup --path=database/migrations/sqlite`。
- **部署腳本 MUST NOT 出現不帶上述參數的 `php artisan migrate`**(MySQL schema 由
  app.binnii.com 擁有)。
- 出向:只需要連得到 api 機的 443(staging 同機時為 `127.0.0.1:8082`);
  **不需要、也不應該**連得到 app 機。
