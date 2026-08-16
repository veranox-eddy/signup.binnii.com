<?php

namespace Tests\Feature;

use App\Models\PendingSignup;
use App\Services\EmailAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ConnectionGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Stand-in for the two GRANTed columns of daycare.users. Raw PDO on
        // purpose: the connection guard blocks CREATE through the query API
        // (which is exactly what we want in production code).
        DB::connection('mysql_ro')->getPdo()->exec(
            'create table if not exists users (id integer primary key autoincrement, email varchar(190), deleted_at datetime null)'
        );
        DB::connection('mysql_ro')->getPdo()->exec('delete from users');
    }

    public function test_write_statements_on_mysql_ro_are_refused_before_execution(): void
    {
        foreach ([
            "insert into users (email) values ('x@example.test')",
            "update users set email = 'y@example.test'",
            'delete from users',
        ] as $statement) {
            try {
                DB::connection('mysql_ro')->statement($statement);
                $this->fail("Write statement was not blocked: {$statement}");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('read-only', $e->getMessage());
            }
        }

        // Reads still work.
        $this->assertSame([], DB::connection('mysql_ro')->select('select email from users limit 1'));
    }

    public function test_query_builder_writes_are_blocked_too(): void
    {
        $this->expectException(RuntimeException::class);

        DB::connection('mysql_ro')->table('users')->insert(['email' => 'taken@example.test']);
    }

    public function test_email_availability_never_selects_star_or_count(): void
    {
        $statements = [];
        // Connection::listen() receives every connection's queries — filter
        // to mysql_ro (the column GRANT only exists on MySQL; the local
        // SQLite side may use whatever SQL shape it likes).
        DB::connection('mysql_ro')->listen(function ($query) use (&$statements) {
            if ($query->connectionName === 'mysql_ro') {
                $statements[] = $query->sql;
            }
        });

        $this->assertFalse(app(EmailAvailability::class)->isTaken('free@example.test'));

        $this->assertNotEmpty($statements);
        foreach ($statements as $sql) {
            $this->assertStringNotContainsString('select *', strtolower($sql));
            $this->assertStringNotContainsString('count(*)', strtolower($sql));
        }
    }

    public function test_email_availability_sees_mysql_and_active_sqlite_rows(): void
    {
        $service = app(EmailAvailability::class);

        // Insert the MySQL stand-in row via schema-side PDO (not the guarded
        // query API) — the guard blocks even test conveniences on purpose.
        DB::connection('mysql_ro')->getPdo()->exec("insert into users (email) values ('taken@example.test')");
        $this->assertTrue($service->isTaken('TAKEN@example.test'));

        PendingSignup::create([
            'uuid' => 'a0000000-0000-0000-0000-000000000001',
            'name' => 'Pending Person', 'email' => 'pending@example.test',
            'country_code' => 'CA', 'status' => PendingSignup::STATUS_PENDING_VERIFICATION,
        ]);
        $this->assertTrue($service->isTaken('pending@example.test'));

        // Finished rows never block a new registration.
        PendingSignup::create([
            'uuid' => 'a0000000-0000-0000-0000-000000000002',
            'name' => 'Done Person', 'email' => 'done@example.test',
            'country_code' => 'CA', 'status' => PendingSignup::STATUS_FAILED,
        ]);
        $this->assertFalse($service->isTaken('done@example.test'));
    }

    public function test_this_repo_has_no_writable_mysql_connection(): void
    {
        $this->assertSame('signup', config('database.default'));
        $this->assertSame('sqlite', config('database.connections.signup.driver'));

        // The framework merges its default connection entries into the
        // config; what matters is that no code path ever uses them: the
        // only MySQL connection referenced anywhere in app/ is mysql_ro.
        $hits = collect(glob(app_path('{,*/,*/*/}*.php'), GLOB_BRACE))
            ->filter(fn ($file) => preg_match(
                "/connection\\('(?!mysql_ro)[^']*mysql[^']*'\\)/",
                (string) file_get_contents($file)
            ))
            ->values()
            ->all();

        $this->assertSame([], $hits);
    }
}
