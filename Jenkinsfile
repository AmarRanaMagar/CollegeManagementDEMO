pipeline {
    agent any

    environment {
        COMPOSE_PROJECT_NAME = "college-management-jenkins-${env.BUILD_NUMBER}"
        APP_PORT = "18080"
        DB_PORT = "13307"
        HTTPS_PORT = "14443"
        MYSQL_DATA_DIR = "./jenkins-mysql"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Prepare CI environment') {
            steps {
                powershell '''
                    Copy-Item .env.example .env -Force
                    $bytes = New-Object byte[] 32
                    $random = [Security.Cryptography.RandomNumberGenerator]::Create()
                    $random.GetBytes($bytes)
                    $random.Dispose()
                    $appKey = "base64:" + [Convert]::ToBase64String($bytes)
                    $envFile = Get-Content .env -Raw
                    $replacements = @{
                        '^APP_ENV=.*' = 'APP_ENV=local'
                        '^APP_DEBUG=.*' = 'APP_DEBUG=false'
                        '^APP_URL=.*' = 'APP_URL=http://localhost:18080'
                        '^APP_KEY=.*' = "APP_KEY=$appKey"
                        '^DB_HOST=.*' = 'DB_HOST=db'
                        '^DB_DATABASE=.*' = 'DB_DATABASE=unifiedtransform'
                        '^DB_USERNAME=.*' = 'DB_USERNAME=root'
                        '^DB_PASSWORD=.*' = 'DB_PASSWORD=your_mysql_root_password'
                        '^MAIL_MAILER=.*' = 'MAIL_MAILER=log'
                    }
                    foreach ($pattern in $replacements.Keys) {
                        $envFile = $envFile -replace "(?m)$pattern", $replacements[$pattern]
                    }
                    Set-Content .env $envFile -NoNewline
                '''
            }
        }

        stage('Validate Compose') {
            steps {
                bat 'docker compose config --quiet'
            }
        }

        stage('Prepare base images') {
            steps {
                bat 'docker pull nginx:alpine'
                bat 'docker tag nginx:alpine college-management-demo/nginx:alpine'
                bat 'docker pull mysql:5.7.22'
                bat 'docker tag mysql:5.7.22 college-management-demo/mysql:5.7.22'
            }
        }

        stage('Build application image') {
            steps {
                bat 'docker compose build app'
            }
        }

        stage('Start services') {
            steps {
                bat 'docker compose up -d app webserver db'
            }
        }

        stage('Wait for MySQL') {
            steps {
                powershell '''
                    $ready = $false
                    for ($attempt = 1; $attempt -le 30; $attempt++) {
                        docker compose exec -T db mysqladmin ping -h 127.0.0.1 -uroot -pyour_mysql_root_password --silent 2>$null
                        if ($LASTEXITCODE -eq 0) {
                            $ready = $true
                            break
                        }
                        Start-Sleep -Seconds 2
                    }
                    if (-not $ready) {
                        docker compose logs db
                        exit 1
                    }
                '''
            }
        }

        stage('Migrate and seed') {
            steps {
                bat 'docker compose exec -T app php artisan migrate --force'
                bat 'docker compose exec -T app php artisan db:seed --force'
            }
        }

        stage('Verify application') {
            steps {
                powershell '''
                    $response = Invoke-WebRequest http://localhost:18080/login -UseBasicParsing
                    if ($response.StatusCode -ne 200) {
                        throw "Login page returned HTTP $($response.StatusCode)"
                    }
                    if ($response.Content -notmatch '/css/app.css') {
                        throw "Login page does not reference the application stylesheet"
                    }
                '''
            }
        }
    }

    post {
        always {
            bat 'docker compose logs --no-color || exit /b 0'
            bat 'docker compose down -v --remove-orphans || exit /b 0'
        }
        cleanup {
            deleteDir()
        }
    }
}
