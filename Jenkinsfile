pipeline {
    agent any

    environment {
        COMPOSE_PROJECT_NAME = "college-management-jenkins-${env.BUILD_NUMBER}"
        APP_PORT = "18080"
        DB_PORT = "13307"
        MYSQL_DATA_DIR = "jenkins-mysql"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
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
                        docker compose exec -T db mysqladmin ping -h 127.0.0.1 -uroot -pyour_mysql_root_password --silent
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
            bat 'docker compose logs --no-color'
            bat 'docker compose down -v --remove-orphans'
        }
        cleanup {
            deleteDir()
        }
    }
}
