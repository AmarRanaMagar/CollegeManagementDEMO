# College Management System

Laravel 8 college-management application with Docker Compose, PHP-FPM, Nginx, and MySQL.

## Requirements

Install and start:

- Docker Desktop with the Linux engine enabled
- WSL2 with Ubuntu integration enabled (recommended on Windows)
- Git

The project uses:

- PHP 7.4-FPM
- Nginx
- MySQL 5.7.22
- Laravel 8

## First-time setup

### Recommended: run from WSL2

Keeping the project inside the WSL Linux filesystem gives Docker better performance than running it from a Windows-mounted drive.

```bash
mkdir -p ~/projects
cd ~/projects
git clone https://github.com/AmarRanaMagar/CollegeManagementDEMO.git Unifiedtransform
cd Unifiedtransform
```

Copy the environment file:

```bash
cp .env.example .env
```

The Docker database settings required by this project are already represented in `.env.example`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=unifiedtransform
DB_USERNAME=unifiedtransform
DB_PASSWORD=secret
```

Build and start the containers:

```bash
docker compose up -d --build
docker compose ps
```

Install PHP dependencies, generate the application key, and prepare Laravel:

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan config:clear
docker compose exec app php artisan storage:link
```

For a new database, run migrations and seed the initial data:

```bash
docker compose exec app php artisan migrate --seed
```

Open the application:

```text
http://localhost:8080
```

### Windows PowerShell startup

If the project is stored in WSL at `~/projects/Unifiedtransform`, run this from PowerShell:

```powershell
wsl -d Ubuntu -- bash -lc 'cd "$HOME/projects/Unifiedtransform" && docker compose up -d --build && docker compose ps'
```

Then run the setup commands inside Ubuntu WSL, or prefix each command in the same way:

```powershell
wsl -d Ubuntu -- bash -lc 'cd "$HOME/projects/Unifiedtransform" && docker compose exec app php artisan migrate --seed'
```

## Login

After seeding, use the seeded administrator account:

```text
Email:    admin@ut.com
Password: password
```

Change the password after the first login.

## Initial application setup

Sign in as the administrator and open:

```text
http://localhost:8080/academics/settings
```

Create academic data in this order:

1. Academic session, for example `2026 - 2027`
2. Semester, for example `First Semester`
3. Class, for example `Class 1`
4. Section, for example `Section A`
5. Courses
6. Teachers and teacher assignments
7. Students

The Academic Settings link is also available from the administrator sidebar.

## Daily commands

Start existing containers:

```bash
docker compose up -d
```

View service status:

```bash
docker compose ps
```

View application logs:

```bash
docker compose logs -f app
```

Open a shell in the PHP container:

```bash
docker compose exec app bash
```

Stop the services without deleting database data:

```bash
docker compose stop
```

Stop and remove the containers while preserving the database volume:

```bash
docker compose down
```

## Database safety

The MySQL data is stored in the named Docker volume `dbdata`.

Do **not** use either command unless you intentionally want to delete existing data:

```bash
docker compose down -v
docker compose exec app php artisan migrate:fresh --seed
```

Use `docker compose down` without `-v` when you only need to remove containers.

## Common Laravel commands

Run migrations:

```bash
docker compose exec app php artisan migrate
```

Clear and rebuild cached configuration:

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan config:cache
```

Run the test suite:

```bash
docker compose exec app php artisan test
```

## Ports

| Service | Host address |
| --- | --- |
| Web application | `http://localhost:8080` |
| MySQL | `localhost:3307` |

Inside Docker, the application connects to MySQL using `db:3306`. From the host machine, database tools must use `localhost:3307`.

## Profile pictures

Users can update profile pictures from the authenticated **My Profile** page. Administrators can also update student and teacher profile pictures from their edit pages.

Before testing uploads, verify that the public storage link exists:

```bash
docker compose exec app php artisan storage:link
```

## Troubleshooting

Check that Docker Desktop is running and that all services are healthy:

```bash
docker compose ps
docker compose logs --tail=100 app
docker compose logs --tail=100 db
```

If the application key is missing:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan config:clear
```

If the database container was recreated and the application cannot connect, wait a few seconds and retry:

```bash
docker compose restart db app
docker compose exec app php artisan migrate
```

Do not run `migrate:fresh` or remove the `dbdata` volume as a troubleshooting shortcut, because both can destroy existing data.

## Project structure

```text
app/          Application code, controllers, repositories, and models
config/       Laravel configuration
database/     Migrations, factories, and seeders
public/       Public entry point and assets
resources/    Blade views and frontend resources
routes/       Web and API routes
docker-compose.yml
Dockerfile
```

## Git workflow

Keep local runtime files such as `.env`, `vendor`, `storage`, and session notes out of commits. Review changes before committing:

```bash
git status
git diff --check
git diff
```
