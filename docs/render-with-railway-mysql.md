# Render App With Railway MySQL

Use this setup when the Laravel app runs on Render and the production MySQL database runs on Railway.

## Railway

1. Keep the Railway MySQL service online.
2. Open the MySQL service Variables tab.
3. Copy `MYSQL_PUBLIC_URL`.

Do not use `MYSQLHOST` for Render. That host is for Railway services inside the same Railway project. Render needs the public Railway MySQL URL.

## Render

1. Create a new Render Blueprint from the GitHub repository.
2. Select `grandlionhotel52/GrandLionHotel`.
3. Render will use `render.yaml` and create only the Laravel web service.
4. Set these required variables when Render asks:

```env
APP_URL=https://your-render-url.onrender.com
APP_KEY=base64:GENERATE_A_NEW_PRIVATE_KEY
DB_URL=mysql://user:password@host:port/database
```

Use the value from Railway's `MYSQL_PUBLIC_URL` for `DB_URL`.

To create or update the initial administrator during the next deployment, add:

```env
SEED_ADMIN_ON_DEPLOY=true
SEED_ADMIN_EMAIL=admin@example.com
SEED_ADMIN_PASSWORD=use-a-strong-private-password
SEED_ADMIN_NAME=Administrator
SEED_ADMIN_PHONE=09170000001
```

After the administrator can sign in, remove `SEED_ADMIN_ON_DEPLOY` (or set it to
`false`) so later deployments do not reset the account password.

## Notes

- `DB_CONNECTION` is already set to `mysql` in `render.yaml`.
- The startup script runs `php artisan migrate --force` during deployment.
- Keep the Railway MySQL service active, because Render depends on it.
- Render free web services do not support persistent disks. Database records stay in Railway MySQL, but uploaded local files can be lost on redeploy or restart.
