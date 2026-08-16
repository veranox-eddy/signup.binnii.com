# signup.binnii.com

Binnii 註冊站:兩步驟註冊(帳號 → 組織)、14 天免費試用開通、email 驗證、
一次性 handoff token 交接到 app.binnii.com 自動登入。

- 規格:`../specs/saas/build-signup-free-trial.md`
- **DB schema 的擁有者是 `app.binnii.com`**:本 repo 的 `database/migrations/` 永遠是空的,
  部署流程 **不可** 執行 `php artisan migrate`。
- 官網(binnii.com)是純靜態站,與本 repo 無關。

## 部署

- nginx `root` 指到 `<repo>/public`,PHP-FPM 8.3,Laravel 標準
  `try_files $uri $uri/ /index.php?$query_string`。
- 部署腳本 MUST NOT 含 `php artisan migrate`(schema 由 app.binnii.com 擁有)。
- `.env`:`DB_*` 與 app.binnii.com 指向同一個 MySQL database;
  `APP_URL=https://signup.binnii.com`、`APP_CONSOLE_URL=https://app.binnii.com`。
- 測試以 `database/schema/sqlite-schema.sql`(由 app.binnii.com 的 migrations 產出的
  schema 快照)建立暫用 sqlite schema;app 端 schema 變更後請依
  `app/Providers/AppServiceProvider.php` 檔頭註解重新產生。兩個 repo 在部署上
  完全獨立,無任何跨目錄依賴。
