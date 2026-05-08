# Skilora — Production Cron Schedule

## System Crontab (`crontab -e` as www-data or deploy user)

```cron
# Import job feed from ANETI + Reddit + RemoteOK + WeWorkRemotely (every 6 hours)
0 */6 * * * cd /var/www/skilora && php bin/console app:recruitment:import-feed --max-age=30 >> var/log/feed_import.log 2>&1

# Expire stale job offers (daily at 02:00)
# Rules: explicit expires_at < NOW(), feed jobs > 7 days, manual jobs > 30 days
0 2 * * * cd /var/www/skilora && php bin/console app:expire-job-offers >> var/log/expire_jobs.log 2>&1

# Clear Symfony cache on stale assets (weekly, Sunday 03:00)
0 3 * * 0 cd /var/www/skilora && php bin/console cache:clear --env=prod >> var/log/cache_clear.log 2>&1
```

## Symfony Messenger Worker (systemd service, always running)

```bash
# /etc/systemd/system/skilora-messenger.service
[Unit]
Description=Skilora Messenger Worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/skilora
ExecStart=/usr/bin/php bin/console messenger:consume async --time-limit=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

## Python Scripts (optional, if using Python crawler directly)

```cron
# Python job crawler (alternative to PHP import command)
*/30 * * * * /usr/bin/python3 /var/www/skilora/python/job_feed_crawler.py >> /var/log/skilora/crawler.log 2>&1
```

## Health Monitoring

- Endpoint: `GET /health` → `{"status":"ok","db":"ok","time":"..."}`
- Add to UptimeRobot / BetterUptime as HTTP monitor every 5 min
- Alert on non-200 response
