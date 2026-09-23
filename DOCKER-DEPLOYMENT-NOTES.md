# Docker and Deployment Notes

> **Personal reference note:** This document records what was done and possible next
> steps. Treat it as a learning/reference guide, not as instructions that must be
> followed exactly. Verify commands, versions, permissions, backups, costs, and
> environment-specific behavior before using them, especially in production.

## Project

`CollegeManagementDEMO` is a Laravel 8 application using PHP-FPM, Nginx, and MySQL.

## Completed

- Added `.dockerignore` to exclude secrets, Git files, dependencies, tests, local Docker data, logs, caches, and development-only files from the build context.
- Reduced Composer dependencies in the production image with `--no-dev`.
- Moved `laravel/ui` to production dependencies because the application uses `Auth::routes()`.
- Updated `composer.lock`.
- Replaced the single-stage PHP Dockerfile with a multi-stage build:
  - Builder stage installs Composer dependencies and compiles PHP extensions.
  - Runtime stage contains PHP-FPM, required runtime libraries, application code, and production dependencies.
- Required PHP extensions verified:
  - `gd`
  - `pdo_mysql`
  - `zip`
  - `exif`
  - `pcntl`
- Removed the fake "College overview" dashboard preview from the landing page.
- Created project image names:
  - `college-management-demo/php:latest`
  - `college-management-demo/nginx:alpine`
  - `college-management-demo/mysql:5.7.22`
- Preserved a local rollback tag:
  - `college-management-demo/php:before-multistage`
- Migrated the existing `unifiedtransform` database into the current project.
- Database persistence is configured with:

  ```text
  ./docker-data/unifiedtransform/mysql:/var/lib/mysql
  ```

- Verified the database after migration:
  - 31 tables
  - 3 users
- Fixed the local Laravel 500 error by creating `.env` and generating `APP_KEY`.
- Verified the application at:

  ```text
  http://localhost:8080
  ```

- Homepage currently returns HTTP 200.
- Committed and pushed the changes to GitHub:

  ```text
  3cd53aa Optimize Docker image and simplify landing page
  ```

## Current local architecture

```text
Browser
  |
  v
Nginx container :8080
  |
  v
PHP-FPM/Laravel container
  |
  v
MySQL container :3307
```

The currently running services are:

```text
app
webserver
08c4ac2cf1fd_db
```

The database data is stored on the host, not inside the application image.

## Important local commands

Start services:

```powershell
docker compose up -d
```

Rebuild only the PHP image:

```powershell
docker compose build app
docker compose up -d app
```

Check services:

```powershell
docker compose ps
```

View images:

```powershell
docker images
```

View logs:

```powershell
docker logs app
docker logs webserver
docker logs 08c4ac2cf1fd_db
```

Stop services without deleting data:

```powershell
docker compose stop
```

The database data is retained when containers are removed and recreated, as long as this directory is preserved:

```text
docker-data/unifiedtransform/mysql
```

Avoid deleting that directory or using destructive database commands such as `migrate:fresh` in production.

## Environment and secrets

- `.env` is local configuration and is excluded from Git.
- `APP_KEY` is required by Laravel for encryption, sessions, and cookies.
- The production `APP_KEY` must remain private and stable.
- Do not place production secrets in the Dockerfile, image, frontend code, or Git repository.
- CI should use a temporary testing `.env`.
- Production should use server-side environment variables, Docker secrets, or AWS Secrets Manager.

## Image tags and rollback

`latest` is a mutable convenience tag. Production deployments should use immutable Git commit tags, for example:

```text
college-management-demo/php:3cd53aa
```

GitHub Actions should build and push the commit-tagged image to Amazon ECR. ECS should deploy the commit tag rather than relying only on `latest`.

If a deployment fails, ECS can be changed back to the previous commit tag.

## Planned work

### GitHub Actions CI

- Build the PHP image on every push to `main`.
- Create a temporary CI environment.
- Run Composer installation and Laravel tests.
- Use a temporary MySQL service for database tests.
- Add Docker BuildKit cache support to reduce repeated build time.

### Amazon ECR

- Create ECR repositories for application images.
- Configure GitHub Actions AWS OIDC authentication.
- Build and push images tagged with `${{ github.sha }}`.
- Optionally push `latest` as a convenience tag.
- Keep commit tags for rollback.

### AWS production deployment

- Run the PHP/Nginx application on ECS or Fargate.
- Use Amazon RDS for MySQL instead of running MySQL in ECS.
- Store `APP_KEY`, database credentials, and mail credentials in AWS Secrets Manager.
- Use an Application Load Balancer for HTTPS traffic.
- Use Route 53 for DNS and AWS Certificate Manager for TLS certificates.
- Send application and Nginx logs to CloudWatch.
- Store user uploads in durable storage such as Amazon S3.

### Database migrations

- Add schema changes through Laravel migration files.
- Test migrations locally and in CI.
- Run `php artisan migrate --force` as a controlled one-time deployment task.
- Take a database backup or RDS snapshot before important production migrations.
- Use backward-compatible migrations for zero-downtime deployments.

## Production image guidance

- Do not mount `./:/var/www` in production; it hides the optimized files baked into the image.
- Use the official `nginx:alpine` image unless custom Nginx changes require a custom image.
- Use the official MySQL image only for local development.
- Keep the production database outside the application image.
- Keep `APP_DEBUG=false` in production.
- Use strong, unique production credentials.
