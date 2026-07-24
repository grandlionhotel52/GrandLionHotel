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
APP_KEY=base64:pCBLTThCGjECgSsWl5T1aCQjDdVtYIZ3o3jT1kOhdQo=
DB_URL=mysql://user:password@host:port/database
```

Use the value from Railway's `MYSQL_PUBLIC_URL` for `DB_URL`.

## Notes

- `DB_CONNECTION` is already set to `mysql` in `render.yaml`.
- The startup script runs `php artisan migrate --force` during deployment.
- Keep the Railway MySQL service active, because Render depends on it.
