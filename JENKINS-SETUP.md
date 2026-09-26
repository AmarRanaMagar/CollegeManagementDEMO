# Jenkins Setup Plan

This guide describes the planned manual Jenkins setup for the Laravel Docker
application. Jenkins will run on the local Windows PC first. It will build and
verify the application; AWS deployment will be added only after the local
pipeline is reliable.

## Planned flow

```text
GitHub repository
        |
        v
Jenkins on the Windows PC
        |
        v
Checkout -> Validate Compose -> Build Docker image
        |
        v
Start test services -> Migrate -> Verify -> Clean up
```

The Jenkins workspace is a temporary checkout on the Jenkins agent. It is not
the local application directory and it does not modify GitHub unless a
pipeline explicitly commits and pushes changes.

## Requirements

Install and verify:

- Java 17
- Jenkins LTS
- Git
- Docker Desktop with Docker Compose

Verify from PowerShell:

```powershell
java -version
git --version
docker --version
docker compose version
```

The local application already uses port `8080`, so configure Jenkins to use
port `8081`:

```text
http://localhost:8081
```

## Install Jenkins on Windows

1. Install Java 17.
2. Install the Jenkins LTS Windows installer.
3. Run Jenkins as a Windows service.
4. Select port `8081`.
5. Open `http://localhost:8081`.
6. Enter the initial administrator password.
7. Install the suggested plugins.
8. Create a Jenkins administrator account.

The Jenkins service account must be able to run Docker Desktop commands. If
Jenkins reports that Docker is not found, add the Docker CLI directory to the
system `PATH` and restart the service:

```powershell
Restart-Service jenkins
```

## Create a Pipeline job

In Jenkins:

1. Select **New Item**.
2. Enter `CollegeManagementDEMO`.
3. Select **Pipeline**.
4. Select **Pipeline script from SCM**.
5. Select **Git**.
6. Use repository URL:

   ```text
   https://github.com/AmarRanaMagar/CollegeManagementDEMO.git
   ```

7. Use branch `*/main`.
8. Set the script path to `Jenkinsfile`.
9. Save the job.

For a private repository, add a GitHub credential in **Manage Jenkins >
Credentials**. Do not put a GitHub token in this file or in the repository.

### Docker Hub credential for private images

The Compose project uses private images from `mewton123/unifiedtransform`.
Before running the pipeline, add a Jenkins **Username with password**
credential using a Docker Hub access token with **read-only** access:

- ID: `dockerhub-credentials`
- Username: the Docker Hub account that can read the private repository
- Password: a Docker Hub read-only access token

The Jenkinsfile uses this credential for `docker login --password-stdin`,
pulls the private Nginx and MySQL images, and logs out during cleanup. The app
image is built from the checked-out Dockerfile for each CI build.

### GitHub Actions secrets for private images

Add these repository secrets under **Settings > Secrets and variables >
Actions**:

- `DOCKERHUB_USERNAME`: the Docker Hub account name.
- `DOCKERHUB_READ_TOKEN`: a read-only Docker Hub access token used by the
  public demo-preview workflow to pull private service images.
- `DOCKERHUB_WRITE_TOKEN`: a Docker Hub access token with write permission,
  used only by the `main` image-publishing workflow to push the PHP image.

The image workflow does not log in or push on pull requests. Do not put tokens
in workflow files or commit them to the repository.

## Automated repository pipeline

The repository includes a `Jenkinsfile` that:

- Uses a unique Compose project for each build.
- Uses ports `18080` and `13307`, avoiding the local application ports.
- Uses an isolated Jenkins MySQL volume.
- Builds and starts the application, Nginx, and MySQL.
- Waits for MySQL, runs migrations and seeders, and verifies the login page.
- Removes only the Jenkins build containers and volumes after the build.

It does not remove the local `docker-data/` directory.

## First manual build

The initial build should be started manually:

1. Open the `CollegeManagementDEMO` job.
2. Select **Build Now**.
3. Open the build number.
4. Select **Console Output**.
5. Confirm that checkout, Docker build, migration, seeding, and HTTP verification pass.

The first pipeline should only validate the application locally. It should not
push images to a registry or deploy to AWS.

## Safe pipeline behavior

The `Jenkinsfile`:

- Uses a separate Compose project name for Jenkins.
- Build the `app` image from the repository Dockerfile.
- Start `app`, `webserver`, and `db`.
- Wait for MySQL before running commands.
- Runs migrations and seeders against an isolated Jenkins database.
- Verifies `http://localhost:18080`.
- Print service logs when a build fails.
- Stops test containers and removes only Jenkins volumes in a
  `post { always { ... } }` block.
- Cleans the Jenkins workspace after the build.

The pipeline uses `docker compose down -v` only for its unique Jenkins project.
Do not run these destructive commands manually against the local project:

```text
docker compose down -v
docker volume prune
docker system prune -a
Remove-Item docker-data
```

The project database is stored under `docker-data/` for local persistence and
must not be removed by a Jenkins test build.

## Manual versus webhook builds

Manual builds do not require a public Jenkins URL. Start Jenkins, open the job,
and select **Build Now**.

Automatic GitHub webhook builds can be added later. They require a secure,
public HTTPS endpoint, such as a properly configured Cloudflare Tunnel, and
GitHub webhook authentication. A changing temporary tunnel URL is not suitable
for a permanent webhook.

## Future AWS stages

After the local pipeline is consistently green, add separate stages to:

1. Authenticate to AWS using Jenkins credentials or OIDC.
2. Build an immutable image tag using the Git commit SHA.
3. Push the image to Amazon ECR.
4. Run migrations against the intended environment.
5. Update ECS or the selected AWS deployment target.

Production credentials, `APP_KEY`, database passwords, and mail credentials must
be stored in Jenkins Credentials or AWS Secrets Manager, never in this guide,
the repository, or a Docker image.

## Current implementation status

The repository currently supports these environments:

| Environment | Application runtime | Database | Public URL |
| --- | --- | --- | --- |
| Local development | Docker Compose | Persistent local MySQL bind mount | `http://localhost:8080` |
| GitHub demo preview | Temporary Docker Compose stack | Fresh temporary MySQL | Temporary Cloudflare URL |
| Jenkins validation | Isolated Docker Compose stack | Jenkins workspace database directory | `http://localhost:18080` during a build |
| AWS production | ECR images on ECS or EC2 | Amazon RDS for MySQL | Production HTTPS domain |

The local persistent database is stored in:

```text
docker-data/unifiedtransform/mysql
```

Jenkins must never use or delete that directory. Jenkins uses an isolated
workspace directory:

```text
jenkins-mysql
```

The Jenkins pipeline removes its own temporary containers and named volumes
after validation. It does not delete local development database data.

## Docker image responsibilities

The production deployment uses two application images:

```text
Laravel/PHP-FPM image
Nginx image
```

The PHP image is built by the multi-stage `Dockerfile` and contains:

- Laravel application source.
- PHP 8.1-FPM.
- Required PHP extensions.
- Production Composer dependencies.
- Faker as a runtime dependency because the current seeders use factories.
- Optimized Composer autoload files.

The Nginx image serves `public/` and forwards PHP requests to PHP-FPM.
MySQL is used as a local and CI service only. Production should use Amazon RDS
instead of deploying the MySQL Docker image.

Production images should be pushed to ECR with immutable Git commit tags:

```text
<account>.dkr.ecr.<region>.amazonaws.com/college-management/php:<commit-sha>
<account>.dkr.ecr.<region>.amazonaws.com/college-management/nginx:<commit-sha>
```

Do not deploy production using only the `latest` tag. A commit tag makes
rollback and release identification reliable.

## AWS production architecture

The recommended architecture is:

```text
Route 53 domain
        |
Application Load Balancer with HTTPS
        |
ECS/Fargate service
  Nginx + Laravel/PHP containers
        |
Amazon RDS for MySQL
```

The two application containers can also run on one EC2 host with Docker, but
that requires manually managing the host, Docker upgrades, deployments,
restarts, health checks, and scaling. ECS/Fargate is preferred for a managed
container deployment.

Production must not use these local-only Compose mounts:

```yaml
- ./:/var/www
- ./docker-data/unifiedtransform/mysql:/var/lib/mysql
```

Application source and Composer dependencies should remain inside the
immutable image. Database persistence belongs to RDS. User-uploaded files
should use S3 or another durable storage service rather than a container
filesystem.

## Creating and configuring Amazon RDS

1. Create an Amazon RDS MySQL instance in the same region and VPC as the
   application runtime.
2. Create the `unifiedtransform` database.
3. Use a dedicated application database user instead of `root`.
4. Enable automated backups and define a retention period.
5. Keep RDS private for production when possible.
6. Configure the RDS security group to allow TCP port `3306` only from the
   ECS task or EC2 security group.
7. Do not allow `0.0.0.0/0` access to MySQL.

Example database setup:

```sql
CREATE DATABASE unifiedtransform;
CREATE USER 'college_app'@'%' IDENTIFIED BY 'use-a-secret-password';
GRANT ALL PRIVILEGES ON unifiedtransform.* TO 'college_app'@'%';
FLUSH PRIVILEGES;
```

The actual password must be generated securely and stored in AWS Secrets
Manager. It must not be placed in this file or committed to Git.

Production Laravel settings should resolve to:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-production-domain.example
APP_KEY=base64:stable-production-key

DB_CONNECTION=mysql
DB_HOST=your-rds-endpoint
DB_PORT=3306
DB_DATABASE=unifiedtransform
DB_USERNAME=college_app
DB_PASSWORD=secret-from-secrets-manager
```

The RDS endpoint is the value of `DB_HOST`. It must not be `db`, `localhost`,
or `127.0.0.1`. The production `APP_KEY` must remain stable across releases;
generating a new key on every deployment invalidates encrypted sessions and
other encrypted data.

## Migrating existing local data to RDS

If the current local data must be preserved, create a dump without committing
it to Git:

```powershell
docker compose exec db mysqldump `
  -uroot -pyour_mysql_root_password `
  unifiedtransform > unifiedtransform-production.sql
```

Import it only from a machine that can reach the private RDS endpoint:

```bash
mysql -h your-rds-endpoint \
  -P 3306 \
  -u college_app \
  -p unifiedtransform < unifiedtransform-production.sql
```

For a large or live database, use AWS Database Migration Service instead of a
manual dump. The SQL dump is sensitive and must remain outside the repository.

## Production deployment sequence

The intended release flow is:

```text
Git push
    |
    v
Jenkins checkout
    |
    v
Build PHP and Nginx images
    |
    v
Run CI validation against temporary MySQL
    |
    v
Authenticate to ECR
    |
    v
Tag and push both images with the Git commit SHA
    |
    v
Update ECS service or EC2 deployment
    |
    v
Run php artisan migrate --force once
    |
    v
Health-check the HTTPS application
```

The current Jenkinsfile stops after local validation. It does not yet
authenticate to AWS, push to ECR, run production migrations, or update ECS.
Those stages should be added only after AWS credentials, repositories, network
access, and deployment targets are configured.

Run production migrations as a controlled release operation:

```bash
php artisan migrate --force
```

Do not run the current demo seeder automatically in production. It creates
demo permissions and a predictable account:

```text
admin@ut.com / password
```

Use a secure production-only administrator creation process instead.

## Secrets and mail

Store these values in AWS Secrets Manager or the ECS task definition's secret
references:

- `APP_KEY`
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- SMTP, Amazon SES, or other mail credentials

The local and preview environments use Laravel's `log` mailer when no mail
service is available. Production must use a real mail transport such as
Amazon SES or an approved SMTP provider. Do not point production at `mailhog`.

Never copy a personal `.env` into Jenkins or an AWS image. CI creates a
temporary `.env` with a temporary key and an isolated database. Production
configuration must be injected at runtime.

## Manual steps still required for AWS

The following infrastructure tasks cannot be completed safely by the current
local Jenkins pipeline without AWS-specific decisions and credentials:

1. Create ECR repositories for PHP and Nginx.
2. Create the RDS instance, database user, subnet group, backups, and security
   groups.
3. Choose ECS/Fargate or EC2 as the runtime.
4. Create the ECS cluster, task definition, service, load balancer, and HTTPS
   certificate, or configure the EC2 host and Docker runtime.
5. Configure Secrets Manager values and IAM permissions.
6. Configure Jenkins AWS credentials or OIDC.
7. Decide how uploads are stored and configure S3 if required.
8. Configure the production domain, DNS, monitoring, logs, and alerts.
9. Perform the initial data migration and production migration review.
10. Add and test ECR push and deployment stages.

Until these steps are completed, Jenkins is a local build and validation
system, not an AWS deployment system.
