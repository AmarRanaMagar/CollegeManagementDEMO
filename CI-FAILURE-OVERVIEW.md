# Demo Preview CI Failure Overview

Repository: `AmarRanaMagar/CollegeManagementDEMO`
Workflow: `.github/workflows/demo-preview.yml`

## Current status

The GitHub Actions job linked in the request is:

- Run: `35822430723`
- Job: `107056943197`
- URL: https://github.com/AmarRanaMagar/CollegeManagementDEMO/actions/runs/35822430723/job/107056943197

At the time of investigation, GitHub reported that this job check had not completed, so a complete log was not available yet. The failure history below is based on the completed job logs available in this conversation.

## Failure 1: Missing Composer autoloader

Job: `107052066251`
Run: `35820818645`

### Error

```text
Warning: require(/var/www/vendor/autoload.php): Failed to open stream: No such file or directory in /var/www/artisan on line 18
Fatal error: Uncaught Error: Failed opening required '/var/www/vendor/autoload.php'
in /var/www/artisan:18
Process completed with exit code 255.
```

### Root cause

The Dockerfile successfully installed Composer dependencies during the image build and generated `vendor/autoload.php`. However, `docker-compose.yml` mounted the GitHub workspace over `/var/www`:

```yaml
volumes:
  - ./:/var/www
```

Because the checkout did not contain `vendor/`, this bind mount hid the `vendor/` directory created inside the image.

### Fix applied

A named volume was added for Composer dependencies:

```yaml
volumes:
  - ./:/var/www
  - vendor:/var/www/vendor
```

and declared at the bottom of `docker-compose.yml`:

```yaml
volumes:
  vendor:
```

The obsolete Compose `version` field was also removed.

## Failure 2: Laravel log permission denied

Job: `107053290629`
Run: `35821226295`

### Error

```text
In StreamHandler.php line 146:
The stream or file "/var/www/storage/logs/laravel.log" could not be opened in append mode:
Failed to open stream: Permission denied
Process completed with exit code 1.
```

### Root cause

The runtime image runs PHP as the non-root `www` user:

```dockerfile
USER www
```

The source tree was bind-mounted from the GitHub runner, so Laravel's writable directories were not guaranteed to be writable by `www`.

Laravel requires write access to:

```text
storage/
storage/logs/
bootstrap/cache/
```

### Fix applied

Named volumes were added for Laravel's writable directories:

```yaml
volumes:
  - ./:/var/www
  - vendor:/var/www/vendor
  - storage:/var/www/storage
  - bootstrap-cache:/var/www/bootstrap/cache
```

and declared:

```yaml
volumes:
  vendor:
  storage:
  bootstrap-cache:
```

This prevents the host bind mount from replacing the image's writable Laravel directories.

## Failure 3: `.env` permission denied during key generation

Job: `107055528127`
Run: `35821960509`

### Error

```text
In KeyGenerateCommand.php line 96:
file_put_contents(/var/www/.env): Failed to open stream: Permission denied
Process completed with exit code 1.
```

### Root cause

The workflow creates `.env` on the GitHub Actions host:

```yaml
cp .env.example .env
```

That file is then exposed inside the container through:

```yaml
- ./:/var/www
```

The container's default user is `www`, but `php artisan key:generate --force` must modify `/var/www/.env`. The `www` user did not have permission to write the bind-mounted file.

### Fix

Run only the key-generation command as root:

```yaml
- name: Generate application key
  run: docker compose exec -T -u root app php artisan key:generate --force
```

Do not change the whole container to root. The normal PHP-FPM process should continue running as `www`.

## Relevant final workflow section

```yaml
- name: Build and start application
  run: docker compose up -d --build

- name: Generate application key
  run: docker compose exec -T -u root app php artisan key:generate --force

- name: Wait for application
  shell: bash
  run: |
    for attempt in {1..30}; do
      if curl --fail --silent http://localhost:8080/ > /dev/null; then
        echo "Application is ready."
        exit 0
      fi
      sleep 2
    done

    docker compose logs
    exit 1
```

## Relevant final Compose volume configuration

```yaml
services:
  app:
    volumes:
      - ./:/var/www
      - vendor:/var/www/vendor
      - storage:/var/www/storage
      - bootstrap-cache:/var/www/bootstrap/cache
      - ./php/local.ini:/usr/local/etc/php/conf.d/local.ini

volumes:
  vendor:
  storage:
  bootstrap-cache:
```

## Non-failing warnings

The following messages appeared in the logs but did not cause the job failures:

```text
Package swiftmailer/swiftmailer is abandoned
Package symfony/debug is abandoned
```

The Docker Compose `version` warning was also non-fatal and was addressed by removing the obsolete `version: '3'` field.

The MySQL service successfully started in the permission-failure run:

```text
mysqld: ready for connections.
```

## Overall diagnosis

The failures were caused by interactions between Docker image contents, host bind mounts, named volumes, and container user permissions:

1. The full `/var/www` bind mount hid image-installed Composer dependencies.
2. Host-mounted Laravel directories were not writable by the `www` container user.
3. The host-created `.env` file was not writable by `www` when Artisan attempted to generate `APP_KEY`.

The implemented solution preserves `vendor`, `storage`, and `bootstrap/cache` with named volumes and runs only the `.env`-modifying Artisan command as root.

## Validation checklist

After the next workflow run, verify:

- `docker compose up -d --build` completes successfully.
- `docker compose exec -T app test -f vendor/autoload.php` succeeds.
- `docker compose exec -T app test -w storage` succeeds.
- `docker compose exec -T -u root app php artisan key:generate --force` succeeds.
- `curl --fail http://localhost:8080/` returns success within the retry loop.
- `docker compose logs` contains no Laravel permission errors.
