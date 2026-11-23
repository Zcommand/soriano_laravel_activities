#!/bin/bash
set -eu

# Both processes share the existing Render instance and database queue.
runuser -u www-data -- php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=45 --memory=128 --no-interaction &
queue_pid=$!
apache2-foreground &
web_pid=$!

shutdown() {
    trap '' TERM INT
    kill -TERM "$queue_pid" "$web_pid" 2>/dev/null || true
    wait "$queue_pid" "$web_pid" 2>/dev/null || true
}
trap 'shutdown; exit 0' TERM INT

# A failed worker must not leave a healthy-looking web service behind.
status=0
wait -n "$queue_pid" "$web_pid" || status=$?
shutdown
if [ "$status" -eq 0 ]; then
    status=1
fi
exit "$status"
