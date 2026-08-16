CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "name" varchar not null,
  "email" varchar not null,
  "username" varchar,
  "password" varchar not null,
  "phone" varchar,
  "avatar_path" varchar,
  "type" varchar check("type" in('admin', 'teacher', 'classroom_login')) not null,
  "access_level" varchar check("access_level" in('organization', 'center')) not null default 'center',
  "is_active" tinyint(1) not null default '1',
  "last_active_at" datetime,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "email_verified_at" datetime,
  foreign key("organization_id") references "organizations"("id")
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" varchar not null,
  "queue" varchar not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE INDEX "failed_jobs_connection_queue_failed_at_index" on "failed_jobs"(
  "connection",
  "queue",
  "failed_at"
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "permissions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "permissions_name_guard_name_unique" on "permissions"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "roles_name_guard_name_unique" on "roles"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "model_has_permissions"(
  "permission_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  primary key("permission_id", "model_id", "model_type")
);
CREATE INDEX "model_has_permissions_model_id_model_type_index" on "model_has_permissions"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "model_has_roles"(
  "role_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("role_id", "model_id", "model_type")
);
CREATE INDEX "model_has_roles_model_id_model_type_index" on "model_has_roles"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "role_has_permissions"(
  "permission_id" integer not null,
  "role_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("permission_id", "role_id")
);
CREATE TABLE IF NOT EXISTS "centers"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "name" varchar not null,
  "external_ref" varchar,
  "email" varchar,
  "phone" varchar,
  "phone_country_code" varchar,
  "timezone" varchar not null,
  "tax_id" varchar,
  "address_line1" varchar,
  "address_line2" varchar,
  "city" varchar,
  "state" varchar,
  "country" varchar,
  "zip" varchar,
  "licensed_capacity" integer,
  "desired_capacity" integer,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("organization_id") references "organizations"("id")
);
CREATE TABLE IF NOT EXISTS "center_settings"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "phone_visible_on_app" tinyint(1) not null default '0',
  "delayed_media_hours" integer not null default '0',
  "auto_send_report_time" time,
  "attendance_time_format" varchar check("attendance_time_format" in('12h', '24h')) not null default '12h',
  "teacher_editable_timecards" tinyint(1) not null default '0',
  "sign_in_identification" varchar check("sign_in_identification" in('none', 'name_initials', 'e_signature')) not null default 'none',
  "child_name_display" varchar check("child_name_display" in('full_last', 'last_initial')) not null default 'full_last',
  "parents_can_sign_in" tinyint(1) not null default '0',
  "safe_pickup" tinyint(1) not null default '0',
  "checkin_zone_enabled" tinyint(1) not null default '0',
  "sign_in_code_enabled" tinyint(1) not null default '0',
  "classroom_access" tinyint(1) not null default '0',
  "parents_can_mark_absent" tinyint(1) not null default '0',
  "is_full_week_center" tinyint(1) not null default '0',
  "curriculum_enabled" tinyint(1) not null default '0',
  "curriculum_trial_ends_on" date,
  "staff_management_enabled" tinyint(1) not null default '0',
  "smart_billing_enabled" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "show_waitlist_position" tinyint(1) not null default '0',
  foreign key("center_id") references "centers"("id")
);
CREATE UNIQUE INDEX "center_settings_center_id_unique" on "center_settings"(
  "center_id"
);
CREATE TABLE IF NOT EXISTS "center_user"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "user_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id"),
  foreign key("user_id") references "users"("id")
);
CREATE UNIQUE INDEX "center_user_center_id_user_id_unique" on "center_user"(
  "center_id",
  "user_id"
);
CREATE TABLE IF NOT EXISTS "age_ranges"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "label" varchar not null,
  "min_months" integer,
  "max_months" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id")
);
CREATE TABLE IF NOT EXISTS "classrooms"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "name" varchar not null,
  "display_name" varchar,
  "external_ref" varchar,
  "age_range_id" integer,
  "desired_capacity" integer,
  "student_staff_ratio" varchar,
  "developmental_framework" varchar check("developmental_framework" in('age_0_3', 'age_3_6')),
  "login_username" varchar,
  "is_floating" tinyint(1) not null default '0',
  "photo_sharing_enabled" tinyint(1) not null default '1',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("center_id") references "centers"("id"),
  foreign key("age_range_id") references "age_ranges"("id")
);
CREATE UNIQUE INDEX "classrooms_login_username_unique" on "classrooms"(
  "login_username"
);
CREATE TABLE IF NOT EXISTS "staff"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "user_id" integer,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "gender" varchar check("gender" in('male', 'female', 'x')),
  "email" varchar,
  "phone" varchar,
  "avatar_path" varchar,
  "position" varchar,
  "primary_classroom_id" integer,
  "is_floating" tinyint(1) not null default '0',
  "status" varchar check("status" in('active', 'upcoming', 'deactivated')) not null,
  "hired_on" date,
  "deactivated_on" date,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "date_of_birth" date,
  "address_line1" varchar,
  "address_line2" varchar,
  "city" varchar,
  "state" varchar,
  "country" varchar,
  "zip" varchar,
  foreign key("center_id") references "centers"("id"),
  foreign key("user_id") references "users"("id"),
  foreign key("primary_classroom_id") references "classrooms"("id")
);
CREATE TABLE IF NOT EXISTS "staff_certifications"(
  "id" integer primary key autoincrement not null,
  "staff_id" integer not null,
  "type" varchar not null,
  "label" varchar,
  "expiry_date" date,
  "no_expiry" tinyint(1) not null default '0',
  "file_path" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("staff_id") references "staff"("id")
);
CREATE TABLE IF NOT EXISTS "children"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "date_of_birth" date not null,
  "gender" varchar check("gender" in('boy', 'girl', 'x')) not null,
  "photo_path" varchar,
  "photo_consent" tinyint(1) not null default '0',
  "is_subsidized" tinyint(1) not null default '0',
  "address_line1" varchar,
  "address_line2" varchar,
  "city" varchar,
  "state" varchar,
  "country" varchar,
  "zip" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "is_active" tinyint(1) not null default '1',
  foreign key("center_id") references "centers"("id")
);
CREATE TABLE IF NOT EXISTS "enrollments"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "classroom_id" integer not null,
  "status" varchar check("status" in('active', 'upcoming', 'graduated', 'scheduled')) not null,
  "rotation" varchar check("rotation" in('morning', 'afternoon', 'day', 'before_after_school')),
  "enrolled_on" date,
  "graduated_on" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id"),
  foreign key("classroom_id") references "classrooms"("id")
);
CREATE TABLE IF NOT EXISTS "enrollment_days"(
  "id" integer primary key autoincrement not null,
  "enrollment_id" integer not null,
  "weekday" integer not null,
  foreign key("enrollment_id") references "enrollments"("id")
);
CREATE UNIQUE INDEX "enrollment_days_enrollment_id_weekday_unique" on "enrollment_days"(
  "enrollment_id",
  "weekday"
);
CREATE TABLE IF NOT EXISTS "allergies"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "note" varchar not null,
  "severity" varchar check("severity" in('minor', 'moderate', 'severe', 'other')) not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id")
);
CREATE TABLE IF NOT EXISTS "child_records"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "type" varchar not null,
  "label" varchar,
  "expiry_date" date,
  "no_expiry" tinyint(1) not null default '0',
  "file_path" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id")
);
CREATE TABLE IF NOT EXISTS "child_notes"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "category" varchar check("category" in('special_instructions', 'schedule', 'favorite_things', 'other', 'msp', 'insurance', 'general')) not null,
  "body" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id")
);
CREATE TABLE IF NOT EXISTS "child_pickups"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "name" varchar not null,
  "phone" varchar,
  "details" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id")
);
CREATE TABLE IF NOT EXISTS "guardians"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "email" varchar,
  "mobile_country_code" varchar,
  "mobile_phone" varchar,
  "home_phone" varchar,
  "work_phone" varchar,
  "registration_status" varchar check("registration_status" in('registered', 'invited', 'not_invited')) not null,
  "invited_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "password" varchar,
  "remember_token" varchar,
  "email_verified_at" datetime,
  "last_login_at" datetime,
  "receive_fewer_emails" tinyint(1) not null default '0',
  "email_language" varchar not null default 'en',
  foreign key("center_id") references "centers"("id")
);
CREATE TABLE IF NOT EXISTS "child_guardian"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "guardian_id" integer not null,
  "type" varchar check("type" in('parent', 'guardian')) not null,
  "relationship" varchar,
  "is_emergency" tinyint(1) not null default '0',
  "priority" integer,
  "is_account_admin" tinyint(1) not null default '0',
  "has_full_photo_access" tinyint(1) not null default '1',
  "nickname" varchar,
  foreign key("child_id") references "children"("id"),
  foreign key("guardian_id") references "guardians"("id")
);
CREATE UNIQUE INDEX "child_guardian_child_id_guardian_id_unique" on "child_guardian"(
  "child_id",
  "guardian_id"
);
CREATE TABLE IF NOT EXISTS "virtual_areas"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "name" varchar not null,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id")
);
CREATE TABLE IF NOT EXISTS "child_attendances"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "classroom_id" integer not null,
  "attendance_date" date not null,
  "check_in_at" datetime,
  "check_in_by" varchar,
  "check_out_at" datetime,
  "check_out_by" varchar,
  "check_in_signature" varchar,
  "check_out_signature" varchar,
  "status" varchar check("status" in('present', 'checked_out', 'absent', 'moved')) not null,
  "moved_to_classroom_id" integer,
  "moved_to_virtual_area_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id"),
  foreign key("classroom_id") references "classrooms"("id"),
  foreign key("moved_to_classroom_id") references "classrooms"("id"),
  foreign key("moved_to_virtual_area_id") references "virtual_areas"("id")
);
CREATE INDEX "child_attendances_classroom_id_attendance_date_index" on "child_attendances"(
  "classroom_id",
  "attendance_date"
);
CREATE TABLE IF NOT EXISTS "absences"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "start_date" date not null,
  "end_date" date,
  "reason" varchar check("reason" in('appointment', 'center_closure', 'holiday', 'home_day', 'not_scheduled', 'no_show', 'sick', 'vacation')) not null,
  "note" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id")
);
CREATE TABLE IF NOT EXISTS "staff_absences"(
  "id" integer primary key autoincrement not null,
  "staff_id" integer not null,
  "start_date" date not null,
  "end_date" date,
  "reason" varchar check("reason" in('appointment', 'center_closure', 'holiday', 'home_day', 'not_scheduled', 'no_show', 'sick', 'vacation')) not null,
  "note" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("staff_id") references "staff"("id")
);
CREATE TABLE IF NOT EXISTS "staff_attendances"(
  "id" integer primary key autoincrement not null,
  "staff_id" integer not null,
  "work_date" date not null,
  "clock_in_at" datetime,
  "clock_out_at" datetime,
  "source" varchar check("source" in('kiosk', 'manual')) not null default 'kiosk',
  "status" varchar check("status" in('open', 'sent')) not null default 'open',
  "sent_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("staff_id") references "staff"("id")
);
CREATE TABLE IF NOT EXISTS "entries"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "classroom_id" integer not null,
  "staff_id" integer,
  "type" varchar check("type" in('check_in', 'check_out', 'activity', 'food', 'fluids', 'sleep', 'toilet', 'mood', 'health', 'temperature', 'incident', 'supplies', 'notes', 'move_rooms', 'name_to_face')) not null,
  "occurred_at" datetime not null,
  "payload" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id"),
  foreign key("classroom_id") references "classrooms"("id"),
  foreign key("staff_id") references "staff"("id")
);
CREATE INDEX "entries_child_id_occurred_at_index" on "entries"(
  "child_id",
  "occurred_at"
);
CREATE TABLE IF NOT EXISTS "daily_reports"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "report_date" date not null,
  "status" varchar check("status" in('open', 'sent')) not null default 'open',
  "sent_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id")
);
CREATE UNIQUE INDEX "daily_reports_child_id_report_date_unique" on "daily_reports"(
  "child_id",
  "report_date"
);
CREATE TABLE IF NOT EXISTS "daily_report_logs"(
  "id" integer primary key autoincrement not null,
  "daily_report_id" integer not null,
  "action" varchar not null,
  "actor_id" integer,
  "created_at" datetime not null,
  foreign key("daily_report_id") references "daily_reports"("id"),
  foreign key("actor_id") references "users"("id")
);
CREATE TABLE IF NOT EXISTS "applications"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "child_id" integer,
  "child_first_name" varchar not null,
  "child_last_name" varchar not null,
  "date_of_birth" date,
  "gender" varchar check("gender" in('boy', 'girl', 'x')),
  "address_line1" varchar,
  "address_line2" varchar,
  "city" varchar,
  "state" varchar,
  "country" varchar,
  "zip" varchar,
  "classroom_id" integer,
  "preferred_start_date" date,
  "preferred_time_of_day" varchar check("preferred_time_of_day" in('mornings', 'afternoons', 'full_days', 'before_school', 'after_school', 'first_available')),
  "preferred_weekly_days" text,
  "subsidy_flag" tinyint(1) not null default '0',
  "priority" integer,
  "internal_notes" text,
  "stage" varchar check("stage" in('applicant', 'waitlist', 'registration', 'enrolled', 'cancelled')) not null,
  "status" varchar check("status" in('new', 'in_progress', 'ready_to_review', 'approved', 'enrolled', 'cancelled')) not null,
  "submitted_at" datetime,
  "invite_sent_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id"),
  foreign key("child_id") references "children"("id"),
  foreign key("classroom_id") references "classrooms"("id")
);
CREATE TABLE IF NOT EXISTS "application_contacts"(
  "id" integer primary key autoincrement not null,
  "application_id" integer not null,
  "type" varchar check("type" in('guardian', 'emergency')) not null,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "relationship" varchar,
  "email" varchar,
  "phone" varchar,
  "address_line1" varchar,
  "address_line2" varchar,
  "city" varchar,
  "state" varchar,
  "country" varchar,
  "zip" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("application_id") references "applications"("id")
);
CREATE TABLE IF NOT EXISTS "application_allergies"(
  "id" integer primary key autoincrement not null,
  "application_id" integer not null,
  "note" varchar not null,
  "severity" varchar check("severity" in('minor', 'moderate', 'severe', 'other')) not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("application_id") references "applications"("id")
);
CREATE TABLE IF NOT EXISTS "application_responses"(
  "id" integer primary key autoincrement not null,
  "application_id" integer not null,
  "item_type" varchar check("item_type" in('permission', 'e_consent', 'document')) not null,
  "item_id" integer not null,
  "granted" tinyint(1),
  "signed_at" datetime,
  "file_path" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("application_id") references "applications"("id")
);
CREATE TABLE IF NOT EXISTS "registration_form_fields"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "form_type" varchar check("form_type" in('applicant', 'package')) not null,
  "group" varchar not null,
  "label" varchar not null,
  "input_type" varchar not null default 'short_answer',
  "is_required" tinyint(1) not null default '0',
  "is_hidden" tinyint(1) not null default '0',
  "is_custom" tinyint(1) not null default '0',
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id")
);
CREATE TABLE IF NOT EXISTS "registration_permissions"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "title" varchar not null,
  "body" text,
  "is_required" tinyint(1) not null default '0',
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id")
);
CREATE TABLE IF NOT EXISTS "registration_consents"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "title" varchar not null,
  "body" text,
  "is_required" tinyint(1) not null default '0',
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id")
);
CREATE TABLE IF NOT EXISTS "registration_documents"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "title" varchar not null,
  "body" text,
  "is_required" tinyint(1) not null default '0',
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id")
);
CREATE TABLE IF NOT EXISTS "subsidy_programs"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "name" varchar not null,
  "provider" varchar,
  "details" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id")
);
CREATE TABLE IF NOT EXISTS "subsidy_agreements"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "subsidy_program_id" integer not null,
  "days_approved" integer,
  "days_approved_unit" varchar check("days_approved_unit" in('full_days', 'half_days')) not null,
  "days_approved_period" varchar check("days_approved_period" in('week', 'month')) not null,
  "max_absent_days" integer,
  "max_absent_period" varchar check("max_absent_period" in('week', 'month', 'year')),
  "covid_days_count_absent" tinyint(1) not null default '0',
  "start_date" date,
  "end_date" date,
  "additional_info" text,
  "status" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id"),
  foreign key("subsidy_program_id") references "subsidy_programs"("id")
);
CREATE TABLE IF NOT EXISTS "subsidy_payments"(
  "id" integer primary key autoincrement not null,
  "subsidy_agreement_id" integer not null,
  "payment_period" varchar not null,
  "description" varchar,
  "estimated_amount" numeric,
  "received_amount" numeric,
  "difference" numeric as(received_amount - estimated_amount) stored,
  "payment_date" date,
  "action_taken" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("subsidy_agreement_id") references "subsidy_agreements"("id")
);
CREATE TABLE IF NOT EXISTS "billing_ledger_entries"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "entry_date" date not null,
  "description" varchar not null,
  "type" varchar check("type" in('charge', 'payment', 'credit')) not null,
  "amount" numeric not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id")
);
CREATE TABLE IF NOT EXISTS "conversations"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "subject" varchar not null,
  "type" varchar check("type" in('message', 'notice')) not null,
  "created_by" integer not null,
  "shared_with_teachers" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "channel" varchar check("channel" in('email', 'sms')) not null default 'email',
  "archived_at" datetime,
  "scheduled_at" datetime,
  foreign key("center_id") references "centers"("id"),
  foreign key("created_by") references "users"("id")
);
CREATE TABLE IF NOT EXISTS "conversation_participants"(
  "id" integer primary key autoincrement not null,
  "conversation_id" integer not null,
  "participant_type" varchar not null,
  "participant_id" integer not null,
  "role" varchar,
  foreign key("conversation_id") references "conversations"("id")
);
CREATE TABLE IF NOT EXISTS "messages"(
  "id" integer primary key autoincrement not null,
  "conversation_id" integer not null,
  "sender_type" varchar not null,
  "sender_id" integer not null,
  "body" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("conversation_id") references "conversations"("id")
);
CREATE TABLE IF NOT EXISTS "media"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "classroom_id" integer,
  "uploaded_by" integer,
  "media_type" varchar check("media_type" in('photo', 'video')) not null,
  "file_path" varchar not null,
  "caption" text,
  "status" varchar check("status" in('draft', 'sent')) not null default 'draft',
  "sent_at" datetime,
  "occurred_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id"),
  foreign key("classroom_id") references "classrooms"("id"),
  foreign key("uploaded_by") references "staff"("id")
);
CREATE TABLE IF NOT EXISTS "media_child"(
  "media_id" integer not null,
  "child_id" integer not null,
  foreign key("media_id") references "media"("id"),
  foreign key("child_id") references "children"("id"),
  primary key("media_id", "child_id")
);
CREATE TABLE IF NOT EXISTS "likes"(
  "id" integer primary key autoincrement not null,
  "guardian_id" integer not null,
  "likeable_type" varchar not null,
  "likeable_id" integer not null,
  "created_at" datetime not null,
  foreign key("guardian_id") references "guardians"("id")
);
CREATE UNIQUE INDEX "likes_guardian_id_likeable_type_likeable_id_unique" on "likes"(
  "guardian_id",
  "likeable_type",
  "likeable_id"
);
CREATE TABLE IF NOT EXISTS "health_logs"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "classroom_id" integer not null,
  "staff_id" integer,
  "logged_at" datetime not null,
  "type" varchar not null,
  "value" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id"),
  foreign key("classroom_id") references "classrooms"("id"),
  foreign key("staff_id") references "staff"("id")
);
CREATE TABLE IF NOT EXISTS "sleep_checks"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "classroom_id" integer not null,
  "staff_id" integer,
  "checked_at" datetime not null,
  "position" varchar,
  "status" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id"),
  foreign key("classroom_id") references "classrooms"("id"),
  foreign key("staff_id") references "staff"("id")
);
CREATE TABLE IF NOT EXISTS "incidents"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "classroom_id" integer,
  "type_of_incident" varchar not null,
  "occurred_at" datetime not null,
  "description" text,
  "parent_notified" tinyint(1) not null default '0',
  "parent_notified_at" datetime,
  "parent_signature" varchar,
  "reported_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id"),
  foreign key("classroom_id") references "classrooms"("id"),
  foreign key("reported_by") references "staff"("id")
);
CREATE TABLE IF NOT EXISTS "incident_staff"(
  "incident_id" integer not null,
  "staff_id" integer not null,
  foreign key("incident_id") references "incidents"("id"),
  foreign key("staff_id") references "staff"("id"),
  primary key("incident_id", "staff_id")
);
CREATE TABLE IF NOT EXISTS "activities"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "title" varchar not null,
  "description" text,
  "tags" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id")
);
CREATE TABLE IF NOT EXISTS "weekly_plans"(
  "id" integer primary key autoincrement not null,
  "classroom_id" integer not null,
  "week_start_date" date not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("classroom_id") references "classrooms"("id")
);
CREATE TABLE IF NOT EXISTS "curriculum_assignments"(
  "id" integer primary key autoincrement not null,
  "classroom_id" integer not null,
  "curriculum" varchar check("curriculum" in('none', 'infants', 'toddlers', 'preschool')) not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("classroom_id") references "classrooms"("id")
);
CREATE TABLE IF NOT EXISTS "menus_calendars"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "type" varchar check("type" in('calendar', 'menu', 'central_lesson_plan')) not null,
  "name" varchar not null,
  "parent_visible" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id")
);
CREATE TABLE IF NOT EXISTS "calendar_events"(
  "id" integer primary key autoincrement not null,
  "menus_calendar_id" integer not null,
  "event_date" date not null,
  "title" varchar not null,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("menus_calendar_id") references "menus_calendars"("id")
);
CREATE UNIQUE INDEX "weekly_plans_classroom_id_week_start_date_unique" on "weekly_plans"(
  "classroom_id",
  "week_start_date"
);
CREATE UNIQUE INDEX "curriculum_assignments_classroom_id_unique" on "curriculum_assignments"(
  "classroom_id"
);
CREATE TABLE IF NOT EXISTS "health_screenings"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "staff_administered_enabled" tinyint(1) not null default '0',
  "family_administered_enabled" tinyint(1) not null default '0',
  "questions" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id")
);
CREATE UNIQUE INDEX "health_screenings_center_id_unique" on "health_screenings"(
  "center_id"
);
CREATE TABLE IF NOT EXISTS "health_screening_results"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "screened_on" date not null,
  "administered_by" varchar check("administered_by" in('staff', 'family')) not null,
  "passed" tinyint(1) not null,
  "answers" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id")
);
CREATE TABLE IF NOT EXISTS "notification_preferences"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "classroom_id" integer,
  "new_messages" tinyint(1) not null default '1',
  "new_comments" tinyint(1) not null default '1',
  "new_likes" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id"),
  foreign key("classroom_id") references "classrooms"("id")
);
CREATE TABLE IF NOT EXISTS "staff_enrollments"(
  "id" integer primary key autoincrement not null,
  "staff_id" integer not null,
  "classroom_id" integer not null,
  "status" varchar check("status" in('active', 'upcoming', 'graduated', 'scheduled')) not null,
  "rotation" varchar check("rotation" in('morning', 'afternoon', 'day', 'before_after_school')),
  "start_date" date,
  "end_date" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("staff_id") references "staff"("id") on delete cascade,
  foreign key("classroom_id") references "classrooms"("id")
);
CREATE TABLE IF NOT EXISTS "staff_enrollment_days"(
  "id" integer primary key autoincrement not null,
  "staff_enrollment_id" integer not null,
  "weekday" integer not null,
  foreign key("staff_enrollment_id") references "staff_enrollments"("id") on delete cascade
);
CREATE UNIQUE INDEX "staff_enrollment_days_staff_enrollment_id_weekday_unique" on "staff_enrollment_days"(
  "staff_enrollment_id",
  "weekday"
);
CREATE TABLE IF NOT EXISTS "staff_records"(
  "id" integer primary key autoincrement not null,
  "staff_id" integer not null,
  "type" varchar not null,
  "label" varchar,
  "expiry_date" date,
  "no_expiry" tinyint(1) not null default '0',
  "file_path" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("staff_id") references "staff"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "staff_notes"(
  "id" integer primary key autoincrement not null,
  "staff_id" integer not null,
  "category" varchar check("category" in('special_instructions', 'schedule', 'favorite_things', 'other')) not null,
  "body" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("staff_id") references "staff"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "staff_emergency_contacts"(
  "id" integer primary key autoincrement not null,
  "staff_id" integer not null,
  "name" varchar not null,
  "relationship" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("staff_id") references "staff"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "message_attachments"(
  "id" integer primary key autoincrement not null,
  "message_id" integer not null,
  "file_path" varchar not null,
  "original_name" varchar not null,
  "size" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("message_id") references "messages"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "message_templates"(
  "id" integer primary key autoincrement not null,
  "center_id" integer not null,
  "name" varchar not null,
  "subject" varchar,
  "body" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id")
);
CREATE UNIQUE INDEX "message_templates_center_id_name_unique" on "message_templates"(
  "center_id",
  "name"
);
CREATE TABLE IF NOT EXISTS "classroom_alerts"(
  "id" integer primary key autoincrement not null,
  "classroom_id" integer not null,
  "type" varchar check("type" in('medication', 'sunscreen', 'diaper_cream', 'bug_spray', 'reminder', 'other')) not null,
  "remind_at" time not null,
  "alert_date" date,
  "staff_id" integer,
  "instructions" text,
  "created_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("classroom_id") references "classrooms"("id") on delete cascade,
  foreign key("staff_id") references "staff"("id") on delete set null,
  foreign key("created_by") references "users"("id")
);
CREATE TABLE IF NOT EXISTS "weekly_routines"(
  "id" integer primary key autoincrement not null,
  "classroom_id" integer not null,
  "name" varchar not null,
  "color" varchar,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("classroom_id") references "classrooms"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "weekly_plan_items"(
  "id" integer primary key autoincrement not null,
  "weekly_plan_id" integer not null,
  "plan_date" date not null,
  "activity_id" integer,
  "notes" text,
  "sort_order" integer not null default('0'),
  "weekly_routine_id" integer,
  foreign key("activity_id") references activities("id") on delete no action on update no action,
  foreign key("weekly_plan_id") references weekly_plans("id") on delete no action on update no action,
  foreign key("weekly_routine_id") references "weekly_routines"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "journal_entries"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "guardian_id" integer not null,
  "title" varchar not null,
  "description" text,
  "entry_date" date not null,
  "is_private" tinyint(1) not null default '0',
  "is_favorite" tinyint(1) not null default '0',
  "is_milestone" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id"),
  foreign key("guardian_id") references "guardians"("id")
);
CREATE INDEX "journal_entries_child_id_entry_date_index" on "journal_entries"(
  "child_id",
  "entry_date"
);
CREATE TABLE IF NOT EXISTS "journal_entry_media"(
  "id" integer primary key autoincrement not null,
  "journal_entry_id" integer not null,
  "media_type" varchar check("media_type" in('photo', 'video')) not null,
  "file_path" varchar not null,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("journal_entry_id") references "journal_entries"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "milestone_definitions"(
  "id" integer primary key autoincrement not null,
  "center_id" integer,
  "child_id" integer,
  "age_group" varchar check("age_group" in('prenatal', 'infant', 'toddler', 'preschool', 'school')) not null,
  "category" varchar check("category" in('firsts', 'physical', 'cognitive', 'language', 'social')) not null,
  "name" varchar not null,
  "sort_order" integer not null default '0',
  "is_custom" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("center_id") references "centers"("id"),
  foreign key("child_id") references "children"("id")
);
CREATE INDEX "milestone_definitions_age_group_category_sort_order_index" on "milestone_definitions"(
  "age_group",
  "category",
  "sort_order"
);
CREATE TABLE IF NOT EXISTS "child_milestones"(
  "id" integer primary key autoincrement not null,
  "child_id" integer not null,
  "milestone_definition_id" integer,
  "custom_name" varchar,
  "achieved_on" date,
  "description" text,
  "recorded_by_guardian_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("child_id") references "children"("id"),
  foreign key("milestone_definition_id") references "milestone_definitions"("id"),
  foreign key("recorded_by_guardian_id") references "guardians"("id")
);
CREATE UNIQUE INDEX "child_milestones_child_id_milestone_definition_id_unique" on "child_milestones"(
  "child_id",
  "milestone_definition_id"
);
CREATE TABLE IF NOT EXISTS "guardian_notification_preferences"(
  "id" integer primary key autoincrement not null,
  "guardian_id" integer not null,
  "report_started" tinyint(1) not null default '1',
  "report_ready" tinyint(1) not null default '1',
  "new_entry" tinyint(1) not null default '1',
  "new_photo" tinyint(1) not null default '1',
  "new_comment" tinyint(1) not null default '1',
  "classroom_photos" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("guardian_id") references "guardians"("id") on delete cascade
);
CREATE UNIQUE INDEX "guardian_notification_preferences_guardian_id_unique" on "guardian_notification_preferences"(
  "guardian_id"
);
CREATE TABLE IF NOT EXISTS "guardian_password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "comments"(
  "id" integer primary key autoincrement not null,
  "parent_id" integer,
  "media_id" integer,
  "child_id" integer,
  "guardian_id" integer,
  "thread_subject" varchar not null,
  "body" text not null,
  "status" varchar not null default('inbox'),
  "created_at" datetime,
  "updated_at" datetime,
  "journal_entry_id" integer,
  foreign key("guardian_id") references guardians("id") on delete no action on update no action,
  foreign key("child_id") references children("id") on delete no action on update no action,
  foreign key("media_id") references media("id") on delete no action on update no action,
  foreign key("parent_id") references comments("id") on delete no action on update no action,
  foreign key("journal_entry_id") references "journal_entries"("id")
);
CREATE TABLE IF NOT EXISTS "classroom_user"(
  "id" integer primary key autoincrement not null,
  "classroom_id" integer not null,
  "user_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("classroom_id") references "classrooms"("id"),
  foreign key("user_id") references "users"("id")
);
CREATE UNIQUE INDEX "classroom_user_classroom_id_user_id_unique" on "classroom_user"(
  "classroom_id",
  "user_id"
);
CREATE TABLE IF NOT EXISTS "markets"(
  "id" integer primary key autoincrement not null,
  "code" varchar not null,
  "name" varchar not null,
  "country_code" varchar not null,
  "currency" varchar not null default 'USD',
  "annual_discount_rate" numeric not null default '0.8',
  "tax_name" varchar not null default '',
  "tax_rate" numeric not null default '0',
  "tax_confirmed_at" datetime,
  "tax_notice" varchar,
  "is_active" tinyint(1) not null default '0',
  "is_fallback" integer,
  "source" varchar check("source" in('local_stub', 'platform')) not null default 'local_stub',
  "contract_version" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "markets_code_unique" on "markets"("code");
CREATE UNIQUE INDEX "markets_is_fallback_unique" on "markets"("is_fallback");
CREATE TABLE IF NOT EXISTS "market_plans"(
  "id" integer primary key autoincrement not null,
  "market_id" integer not null,
  "plan_key" varchar check("plan_key" in('go', 'plus', 'pro')) not null,
  "display_name" varchar not null,
  "monthly_base_fee" numeric not null,
  "annual_base_fee" numeric not null,
  "included_active_children" integer not null,
  "monthly_overage_rate" numeric not null,
  "annual_overage_rate" numeric not null,
  "best_for" varchar,
  "sort_order" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("market_id") references "markets"("id")
);
CREATE UNIQUE INDEX "market_plans_market_id_plan_key_unique" on "market_plans"(
  "market_id",
  "plan_key"
);
CREATE TABLE IF NOT EXISTS "platform_settings"(
  "id" integer primary key autoincrement not null,
  "free_trial_enabled" tinyint(1) not null default '1',
  "default_trial_length_days" integer not null default '14',
  "trial_plan_entitlement" varchar check("trial_plan_entitlement" in('go', 'plus', 'pro')) not null default 'pro',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "organizations"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "status" tinyint(1) not null default('1'),
  "market_id" integer,
  "lifecycle_status" varchar check("lifecycle_status" in('active', 'payment_issue', 'grace_period', 'read_only', 'suspended', 'unsubscribed')) not null default 'active',
  "billing_timezone" varchar not null default 'America/Vancouver',
  "is_test_account" tinyint(1) not null default '0',
  foreign key("market_id") references "markets"("id")
);
CREATE TABLE IF NOT EXISTS "subscriptions"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "plan_key" varchar check("plan_key" in('go', 'plus', 'pro')),
  "billing_cycle" varchar check("billing_cycle" in('monthly', 'annual')),
  "effective_date" date,
  "current_period_start" date,
  "current_period_end" date,
  "renewal_at" date,
  "pending_plan_key" varchar check("pending_plan_key" in('go', 'plus', 'pro')),
  "pending_effective_date" date,
  "is_trialing" tinyint(1) not null default '0',
  "trial_started_at" datetime,
  "trial_ends_at" datetime,
  "trial_plan_key" varchar check("trial_plan_key" in('go', 'plus', 'pro')),
  "trial_days_granted" integer,
  "payment_method_readiness" varchar check("payment_method_readiness" in('not_set_up', 'pending', 'verified')) not null default 'not_set_up',
  "cancellation_scheduled_at" datetime,
  "cancellation_withdrawable_until" datetime,
  "last_synced_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id")
);
CREATE UNIQUE INDEX "subscriptions_organization_id_unique" on "subscriptions"(
  "organization_id"
);
CREATE TABLE IF NOT EXISTS "email_verification_tokens"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "token_hash" varchar not null,
  "expires_at" datetime not null,
  "consumed_at" datetime,
  "created_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "email_verification_tokens_token_hash_unique" on "email_verification_tokens"(
  "token_hash"
);
CREATE TABLE IF NOT EXISTS "login_handoffs"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "token_hash" varchar not null,
  "expires_at" datetime not null,
  "consumed_at" datetime,
  "issued_ip" varchar,
  "consumed_ip" varchar,
  "redirect_to" varchar not null default '/organizations',
  "created_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "login_handoffs_token_hash_unique" on "login_handoffs"(
  "token_hash"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_organizations_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(4,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(5,'2026_07_16_061953_create_permission_tables',1);
INSERT INTO migrations VALUES(6,'2026_07_16_100000_create_centers_table',1);
INSERT INTO migrations VALUES(7,'2026_07_16_100001_create_center_settings_table',1);
INSERT INTO migrations VALUES(8,'2026_07_16_100002_create_center_user_table',1);
INSERT INTO migrations VALUES(9,'2026_07_16_110000_create_age_ranges_table',1);
INSERT INTO migrations VALUES(10,'2026_07_16_110001_create_classrooms_table',1);
INSERT INTO migrations VALUES(11,'2026_07_16_120000_create_staff_table',1);
INSERT INTO migrations VALUES(12,'2026_07_16_120001_create_staff_certifications_table',1);
INSERT INTO migrations VALUES(13,'2026_07_16_120002_create_staff_classroom_table',1);
INSERT INTO migrations VALUES(14,'2026_07_16_120003_create_staff_schedules_table',1);
INSERT INTO migrations VALUES(15,'2026_07_16_130000_create_children_table',1);
INSERT INTO migrations VALUES(16,'2026_07_16_130001_create_enrollments_table',1);
INSERT INTO migrations VALUES(17,'2026_07_16_130002_create_enrollment_days_table',1);
INSERT INTO migrations VALUES(18,'2026_07_16_130003_create_allergies_table',1);
INSERT INTO migrations VALUES(19,'2026_07_16_130004_create_child_records_table',1);
INSERT INTO migrations VALUES(20,'2026_07_16_130005_create_child_notes_table',1);
INSERT INTO migrations VALUES(21,'2026_07_16_130006_create_child_pickups_table',1);
INSERT INTO migrations VALUES(22,'2026_07_16_130007_create_guardians_table',1);
INSERT INTO migrations VALUES(23,'2026_07_16_130008_create_child_guardian_table',1);
INSERT INTO migrations VALUES(24,'2026_07_16_140000_create_virtual_areas_table',1);
INSERT INTO migrations VALUES(25,'2026_07_16_140001_create_child_attendances_table',1);
INSERT INTO migrations VALUES(26,'2026_07_16_140002_create_absences_table',1);
INSERT INTO migrations VALUES(27,'2026_07_16_140003_create_staff_absences_table',1);
INSERT INTO migrations VALUES(28,'2026_07_16_140004_create_staff_attendances_table',1);
INSERT INTO migrations VALUES(29,'2026_07_16_150000_create_entries_table',1);
INSERT INTO migrations VALUES(30,'2026_07_16_150001_create_daily_reports_table',1);
INSERT INTO migrations VALUES(31,'2026_07_16_150002_create_daily_report_logs_table',1);
INSERT INTO migrations VALUES(32,'2026_07_16_160000_create_applications_table',1);
INSERT INTO migrations VALUES(33,'2026_07_16_160001_create_application_contacts_table',1);
INSERT INTO migrations VALUES(34,'2026_07_16_160002_create_application_allergies_table',1);
INSERT INTO migrations VALUES(35,'2026_07_16_160003_create_application_responses_table',1);
INSERT INTO migrations VALUES(36,'2026_07_16_160004_create_registration_form_fields_table',1);
INSERT INTO migrations VALUES(37,'2026_07_16_160005_create_registration_permissions_table',1);
INSERT INTO migrations VALUES(38,'2026_07_16_160006_create_registration_consents_table',1);
INSERT INTO migrations VALUES(39,'2026_07_16_160007_create_registration_documents_table',1);
INSERT INTO migrations VALUES(40,'2026_07_16_170000_create_subsidy_programs_table',1);
INSERT INTO migrations VALUES(41,'2026_07_16_170001_create_subsidy_agreements_table',1);
INSERT INTO migrations VALUES(42,'2026_07_16_170002_create_subsidy_payments_table',1);
INSERT INTO migrations VALUES(43,'2026_07_16_170003_create_billing_ledger_entries_table',1);
INSERT INTO migrations VALUES(44,'2026_07_16_180000_create_conversations_table',1);
INSERT INTO migrations VALUES(45,'2026_07_16_180001_create_media_table',1);
INSERT INTO migrations VALUES(46,'2026_07_16_180002_create_comments_table',1);
INSERT INTO migrations VALUES(47,'2026_07_16_190000_create_health_logs_table',1);
INSERT INTO migrations VALUES(48,'2026_07_16_190001_create_sleep_checks_table',1);
INSERT INTO migrations VALUES(49,'2026_07_16_190002_create_incidents_table',1);
INSERT INTO migrations VALUES(50,'2026_07_16_200000_create_activities_table',1);
INSERT INTO migrations VALUES(51,'2026_07_16_200001_create_weekly_plans_table',1);
INSERT INTO migrations VALUES(52,'2026_07_16_200002_create_curriculum_assignments_table',1);
INSERT INTO migrations VALUES(53,'2026_07_16_200003_create_menus_calendars_table',1);
INSERT INTO migrations VALUES(54,'2026_07_17_000000_add_unique_indexes_to_curriculum_tables',1);
INSERT INTO migrations VALUES(55,'2026_07_17_100000_create_health_screenings_table',1);
INSERT INTO migrations VALUES(56,'2026_07_17_100001_create_notification_preferences_table',1);
INSERT INTO migrations VALUES(57,'2026_07_25_100000_add_profile_fields_to_staff_table',1);
INSERT INTO migrations VALUES(58,'2026_07_25_100001_create_staff_enrollments_table',1);
INSERT INTO migrations VALUES(59,'2026_07_25_100002_create_staff_enrollment_days_table',1);
INSERT INTO migrations VALUES(60,'2026_07_25_100003_create_staff_records_table',1);
INSERT INTO migrations VALUES(61,'2026_07_25_100004_create_staff_notes_table',1);
INSERT INTO migrations VALUES(62,'2026_07_25_100005_create_staff_emergency_contacts_table',1);
INSERT INTO migrations VALUES(63,'2026_07_25_110000_drop_staff_classroom_and_staff_schedules_tables',1);
INSERT INTO migrations VALUES(64,'2026_07_25_120000_add_channel_to_conversations_table',1);
INSERT INTO migrations VALUES(65,'2026_07_25_120001_create_message_attachments_table',1);
INSERT INTO migrations VALUES(66,'2026_07_25_120002_create_message_templates_table',1);
INSERT INTO migrations VALUES(67,'2026_07_25_130000_create_classroom_alerts_table',1);
INSERT INTO migrations VALUES(68,'2026_07_25_140000_add_show_waitlist_position_to_center_settings_table',1);
INSERT INTO migrations VALUES(69,'2026_07_25_150000_add_archived_at_to_conversations_table',1);
INSERT INTO migrations VALUES(70,'2026_07_27_120000_add_scheduled_at_to_conversations_table',1);
INSERT INTO migrations VALUES(71,'2026_07_27_130000_create_weekly_routines_table',1);
INSERT INTO migrations VALUES(72,'2026_07_27_130100_add_weekly_routine_id_to_weekly_plan_items_table',1);
INSERT INTO migrations VALUES(73,'2026_07_29_100000_add_auth_to_guardians_table',1);
INSERT INTO migrations VALUES(74,'2026_07_29_100001_add_access_flags_to_child_guardian_table',1);
INSERT INTO migrations VALUES(75,'2026_07_29_100002_create_journal_entries_table',1);
INSERT INTO migrations VALUES(76,'2026_07_29_100003_create_milestone_tables',1);
INSERT INTO migrations VALUES(77,'2026_07_29_100004_create_guardian_notification_preferences_table',1);
INSERT INTO migrations VALUES(78,'2026_07_29_100005_create_guardian_password_reset_tokens_table',1);
INSERT INTO migrations VALUES(79,'2026_07_29_100006_add_journal_entry_id_to_comments_table',1);
INSERT INTO migrations VALUES(80,'2026_08_14_000000_add_status_to_organizations_table',1);
INSERT INTO migrations VALUES(81,'2026_08_15_000000_add_is_active_to_children_table',1);
INSERT INTO migrations VALUES(82,'2026_08_15_100000_create_classroom_user_table',1);
INSERT INTO migrations VALUES(83,'2026_08_16_195000_create_markets_table',1);
INSERT INTO migrations VALUES(84,'2026_08_16_195001_create_market_plans_table',1);
INSERT INTO migrations VALUES(85,'2026_08_16_195002_create_platform_settings_table',1);
INSERT INTO migrations VALUES(86,'2026_08_16_200000_add_saas_fields_to_organizations_table',1);
INSERT INTO migrations VALUES(87,'2026_08_16_200001_add_email_verified_at_to_users_table',1);
INSERT INTO migrations VALUES(88,'2026_08_16_200002_create_subscriptions_table',1);
INSERT INTO migrations VALUES(89,'2026_08_16_200003_create_email_verification_tokens_table',1);
INSERT INTO migrations VALUES(90,'2026_08_16_200004_create_login_handoffs_table',1);
