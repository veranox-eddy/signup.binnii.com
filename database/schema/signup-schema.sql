CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "pending_signups"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "name" varchar not null,
  "email" varchar not null,
  "password_hash" varchar,
  "country_code" varchar not null,
  "organization_name" varchar,
  "billing_timezone" varchar,
  "status" varchar not null default 'draft',
  "failure_reason" varchar,
  "verification_token_hash" varchar,
  "verification_expires_at" datetime,
  "verification_sent_at" datetime,
  "last_resend_at" datetime,
  "resend_count" integer not null default '0',
  "verified_at" datetime,
  "push_attempts" integer not null default '0',
  "next_push_at" datetime,
  "last_push_error" varchar,
  "pushed_at" datetime,
  "synced_at" datetime,
  "mysql_user_id" integer,
  "mysql_organization_id" integer,
  "handoff_token" varchar,
  "handoff_expires_at" datetime,
  "request_ip" varchar,
  "user_agent" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "pending_signups_uuid_unique" on "pending_signups"("uuid");
CREATE UNIQUE INDEX pending_signups_email_active ON pending_signups(
  email
)
WHERE status IN('draft','pending_verification','verified','pushing');
CREATE INDEX pending_signups_pushable ON pending_signups(status, next_push_at);
CREATE INDEX pending_signups_token ON pending_signups(verification_token_hash);
CREATE TABLE IF NOT EXISTS "market_mirror"(
  "code" varchar not null,
  "name" varchar not null,
  "country_code" varchar not null,
  "currency" varchar not null,
  "is_active" tinyint(1) not null default '0',
  "is_fallback" tinyint(1) not null default '0',
  "synced_at" datetime not null,
  primary key("code")
);
CREATE TABLE IF NOT EXISTS "push_runs"(
  "id" integer primary key autoincrement not null,
  "started_at" datetime not null,
  "finished_at" datetime,
  "attempted" integer not null default '0',
  "succeeded" integer not null default '0',
  "failed" integer not null default '0',
  "error" text
);

INSERT INTO migrations VALUES(1,'2026_08_17_000001_create_pending_signups_table',1);
INSERT INTO migrations VALUES(2,'2026_08_17_000002_create_market_mirror_table',1);
INSERT INTO migrations VALUES(3,'2026_08_17_000003_create_push_runs_table',1);
