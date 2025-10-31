pipeline {
    agent any

    environment {
        DOCKERHUB_CREDENTIALS = 'dockerhub-creds'   // Jenkins credentials ID
        DOCKER_IMAGE = 'ritikkumarsahu/phpapp'           // your DockerHub repo (change this)
        EC2_USER = 'ubuntu'
        EC2_HOST = '43.204.101.238'
        SSH_KEY = 'ec2-ssh-key'                     // Jenkins credentials ID for private key
        DB_HOST = 'database-1.c5kss0q0uaa8.ap-south-1.rds.amazonaws.com'
        DB_NAME = 'phpapp'
        DB_USER = 'admin'
        DB_PASS = 'admin1234'
    }

    stages {

        stage('Checkout Code') {
            steps {
                checkout scm
            }
        }

        stage('Build Docker Image') {
            steps {
                script {
                    sh 'docker build -t $DOCKER_IMAGE:latest .'
                }
            }
        }

        stage('Push to DockerHub') {
            steps {
                script {
                    withCredentials([usernamePassword(credentialsId: "${DOCKERHUB_CREDENTIALS}", usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                        sh '''
                          echo "Logging in to DockerHub..."
                          echo $DOCKER_PASS | docker login -u $DOCKER_USER --password-stdin
                          docker push $DOCKER_IMAGE:latest
                          docker logout
                        '''
                    }
                }
            }
        }

        stage('Deploy to EC2') {
            steps {
                script {
                    sshagent([SSH_KEY]) {
                        sh '''
                        ssh -o StrictHostKeyChecking=no ${EC2_USER}@${EC2_HOST} '
                          echo "Stopping old container if exists..." &&
                          docker stop phpapp || true 
                          docker rm phpapp || true 
                          echo "Pulling latest image..." 
                          docker pull ${DOCKER_IMAGE}:latest 
                          echo "Running new container..." 
                          docker run -d --name phpapp -p 80:80 \
                            -e DB_HOST=${DB_HOST} \
                            -e DB_NAME=${DB_NAME} \
                            -e DB_USER=${DB_USER} \
                            -e DB_PASS=${DB_PASS} \
                            ${DOCKER_IMAGE}:latest
                        '
                        '''
                    }
                }
            }
        }
    }

    post {
        always {
            sh 'docker system prune -af || true'
        }
        success {
            echo "✅ Deployment completed successfully!"
        }
        failure {
            echo "❌ Deployment failed. Check Jenkins logs."
        }
    }
}

