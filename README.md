# Soriano Laravel Activities

## Membership Management

- Local Laravel folder: [mm_project](mm_project)
- Local MySQL database: `laravel_membership_db`
- Local application: http://127.0.0.1:8000/members

Run `php artisan serve` from inside `mm_project` with MAMP MySQL running.

Render's root directory must be `mm_project`, with Dockerfile `Dockerfile` and
Docker build context `.` relative to that directory. Aiven's current database
remains `tw33_tasks`; local data is separate and is not copied by a Git push.
