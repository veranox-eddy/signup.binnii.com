# Scheduler / cron setup

The staged-registration flow has three moving parts that only run from the
scheduler. Without them a registration stops at the "Activating your
account…" page and never provisions.

| Command | Cadence | What breaks without it |
| --- | --- | --- |
| `signup:push --once` | every minute | verified signups are never pushed to api.binnii.com — the activating page spins forever |
| `signup:pull-markets` | hourly | the market mirror goes stale; the country list falls back to Canada only and a WARNING is logged |
| `signup:purge` | daily 04:10 | abandoned drafts, delivered rows and expired handoff tokens accumulate |

The cadences live in `routes/console.php`. The host only needs **one** cron
line to drive them — there is no daemon and no systemd unit anymore. All
three commands append their output to `storage/logs/signup-schedule.log`,
so a silently misconfigured `signup:push` ("refusing to run") is visible.

## Remove the old systemd service

Earlier deploys ran `signup:push` as a systemd daemon
(`binnii-signup-push.service`). It has been retired — even if the unit never
started (its `WorkingDirectory` pointed at the pre-`/html` path), remove it,
or the day someone fixes the path it will run **alongside** the cron-driven
schedule:

```bash
# See whether it exists and in what state
systemctl status binnii-signup-push.service --no-pager || true

sudo systemctl stop binnii-signup-push.service     2>/dev/null || true
sudo systemctl disable binnii-signup-push.service  2>/dev/null || true
sudo rm -f /etc/systemd/system/binnii-signup-push.service
sudo rm -f /etc/systemd/system/multi-user.target.wants/binnii-signup-push.service
sudo systemctl daemon-reload
sudo systemctl reset-failed

# Confirm no stray daemon is left (empty if the unit never started)
pgrep -af "artisan signup:push" || echo "no stray push worker"
```

Checkpoint: `systemctl status binnii-signup-push.service` must answer
`Unit ... could not be found.` and `pgrep` must print nothing.

## Install

Remove any old schedule first — it may exist in either place:

```bash
sudo ls -l /etc/cron.d/ | grep -i binnii || true
sudo -u www-data crontab -l 2>/dev/null || true
```

- Old entries in the `www-data` user crontab: save a copy with `-l`, then
  `sudo -u www-data crontab -r`
- Old files under `/etc/cron.d/`: delete them

Then install `deploy/crontab` as `/etc/cron.d/binnii-signup` — note the
`user` column, which plain `crontab -e` files do not have. Files in
`/etc/cron.d/` must have **no extension** (a `.` makes cron ignore the
file):

```bash
sudo install -m 0644 -o root -g root \
  /var/www/html/signup.binnii.com/deploy/crontab /etc/cron.d/binnii-signup

sudo touch /var/log/binnii-signup.log
sudo chown www-data: /var/log/binnii-signup.log
sudo systemctl restart cron
```

## Rules that are easy to get wrong

- **Run as `www-data`, the same user as php-fpm.** The staging store is
  SQLite in WAL mode: a command run as `ubuntu` or `root` creates
  `signup.sqlite-wal` / `-shm` owned by that user, and php-fpm can then no
  longer write. Symptom: the signup form 500s while CLI commands work.
- **Never redirect to `/dev/null`.** A wrong path makes `cd` fail, `&&`
  short-circuits, and the error disappears — the schedule silently never
  runs.
- **The app path is `/var/www/html/signup.binnii.com`** (with `/html`).
- `SIGNUP_DB_PATH` must be **absolute**. A relative path resolves against
  the process cwd, which differs between php-fpm (`/`) and artisan (the
  project dir) — the web app and the CLI then read two different files.

## Verify

```bash
cd /var/www/html/signup.binnii.com
sudo -u www-data php artisan schedule:list                   # all three commands; `signup:push --once` Next Due within a minute
sudo -u www-data HOME=/tmp php artisan signup:push --once -v # must NOT print "refusing to run"
sudo -u www-data HOME=/tmp php artisan signup:pull-markets   # expects "N markets mirrored."
sleep 90 && tail -20 /var/log/binnii-signup.log              # cron is firing
tail -20 storage/logs/signup-schedule.log                    # the commands' own output
sudo -u www-data sqlite3 /var/lib/binnii-signup/signup.sqlite \
  "select id,started_at,attempted,succeeded,failed,error from push_runs order by id desc limit 5;"
ls -l /var/lib/binnii-signup/                                # every file owned by www-data
```

`push_runs` must grow by one row per minute (even with `attempted=0`). If
it does not grow, cron is not running — read `/var/log/binnii-signup.log`
before suspecting the code.
