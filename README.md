# Soriano Laravel Activities

## Membership Management

A Laravel and MySQL project for managing members, membership plans, applications,
renewals, and payment records. Payments are simulated for academic purposes.

- [Live demo](https://soriano-laravel-demo.onrender.com)
- Laravel folder: [mm_project](mm_project)
- Database: `laravel_membership_db`

Run `php artisan serve` inside `mm_project` with MAMP MySQL running.
The local app opens at http://127.0.0.1:8000/members.

## Email setup

I followed the tutorial using Gmail SMTP locally and successfully received the
application email. Render's free hosting blocks the usual SMTP ports, so the
deployed app uses Brevo's HTTPS API. Both use the same Laravel notification and
database queue; only the delivery method changes. Delivery was tested in both
setups.

### Local: Gmail SMTP

Enable Google two-step verification and create an app password. Add these values
to `mm_project/.env`, replacing the examples with your own details:

```dotenv
APP_NAME="Membership Management"
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-address@gmail.com
MAIL_PASSWORD=your-google-app-password
MAIL_FROM_ADDRESS=your-address@gmail.com
MAIL_FROM_NAME="Membership Management"
QUEUE_CONNECTION=database
```

Run `php artisan config:clear`, then keep `php artisan queue:work database`
running in another terminal while testing.

### Deployed: Brevo API

Create a free Brevo account, verify a sender, and generate an API key with
transactional email permission. Add these values in Render's environment settings:

```dotenv
APP_NAME="Membership Management"
MAIL_MAILER=brevo
BREVO_API_KEY=your-brevo-api-key
MAIL_FROM_ADDRESS=your-verified-sender@example.com
MAIL_FROM_NAME="Membership Management"
QUEUE_CONNECTION=database
```

Save and redeploy. The startup script runs the queue worker alongside the app;
no separate worker service is needed. On free hosting, queued messages wait while
the service sleeps and are processed when it wakes.

To test, create a new member with an inbox you control, then create their
Application transaction and check the inbox and spam folder. The email confirms
receipt of the application; it does not verify the address or approve membership.
Keep passwords, API keys, and `.env` out of Git.

## Deployment

On Render, use root directory `mm_project`, Dockerfile `Dockerfile`, and Docker
build context `.`. Configure the Aiven MySQL connection and CA certificate in
Render's environment settings. Local and hosted data are separate; pushing code
does not copy the database.

### Hosting availability

The database uses Aiven's free MySQL plan. The account's trial credits are expected
to expire around November 26, 2026, but free services continue after the trial.
Aiven is retiring its MySQL free tier on **December 21, 2026**. Unless the database
is migrated or upgraded before then, the live demo will stop working. If checking
the project later, please refer to the documentation screenshots or run it locally
with a configured MySQL database.

Sources: [Aiven trial and free plans](https://aiven.io/docs/platform/concepts/service-pricing),
[MySQL free-tier retirement](https://aiven.io/docs/products/mysql/howto/mysql-free-tier-migration-options).
