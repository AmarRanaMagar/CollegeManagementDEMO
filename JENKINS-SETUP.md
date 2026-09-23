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
