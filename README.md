
# 🚀 CI/CD Pipeline using Jenkins, Docker, and AWS EC2 with PHP + MySQL (RDS)

This guide explains how to set up a complete CI/CD pipeline that:
- Builds and pushes a Docker image of a PHP web application to Docker Hub.
- Deploys it automatically to an AWS EC2 instance using Jenkins.
- Connects the PHP app to a MySQL database hosted on AWS RDS.

---

## 🧰 Prerequisites

1. **AWS Account**
   - EC2 instance (Ubuntu preferred)
   - RDS MySQL database (publicly accessible)
2. **Docker Hub Account**
3. **Jenkins Installed on EC2**
4. **GitHub Repository** (for your PHP source code)
5. **Security Groups**:
   - Allow ports `22`, `80`, and `8080` on EC2
   - Allow inbound MySQL (3306) from EC2 instance for RDS

---

## ⚙️ Step 1: Install Jenkins on EC2

```bash
sudo apt update
sudo apt install openjdk-11-jre -y
wget -q -O - https://pkg.jenkins.io/debian/jenkins.io.key | sudo apt-key add -
sudo sh -c 'echo deb http://pkg.jenkins.io/debian/ stable main > /etc/apt/sources.list.d/jenkins.list'
sudo apt update
sudo apt install jenkins -y
sudo systemctl start jenkins
sudo systemctl enable jenkins
```

Access Jenkins at: `http://<EC2_PUBLIC_IP>:8080`

Get the initial admin password:
```bash
sudo cat /var/lib/jenkins/secrets/initialAdminPassword
```

Install recommended plugins and create an admin user.

---

## 🐳 Step 2: Install Docker on EC2

```bash
sudo apt update
sudo apt install docker.io -y
sudo usermod -aG docker jenkins
sudo usermod -aG docker ubuntu
sudo systemctl restart docker
sudo systemctl restart jenkins
docker --version
```

---

## 🌐 Step 3: Setup RDS MySQL Database

1. Go to **AWS RDS Console**
2. Create a **MySQL** instance.
3. Enable **Public Accessibility**
4. Note down:
   - Endpoint (e.g., `db-instance.xxxxx.ap-south-1.rds.amazonaws.com`)
   - Username and Password

Create a test database:
```bash
mysql -h <RDS_ENDPOINT> -u <USERNAME> -p
CREATE DATABASE php_app;
exit;
```

---

## 🧩 Step 4: PHP Application Setup

**Project Structure:**
```
php-app/
├── index.php
├── db.php
├── Dockerfile
└── .env
```

**index.php**
```php
<?php
include('db.php');
echo "<h2>Connected to RDS Successfully!</h2>";
?>
```

**db.php**
```php
<?php
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db = getenv('DB_NAME');

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
```

**.env**
```
DB_HOST=<RDS_ENDPOINT>
DB_USER=<RDS_USERNAME>
DB_PASS=<RDS_PASSWORD>
DB_NAME=php_app
```

**Dockerfile**
```dockerfile
FROM php:8.0-apache
COPY . /var/www/html/
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
EXPOSE 80
CMD ["apache2-foreground"]
```

---

## 🔧 Step 5: Jenkins Pipeline Configuration

### In Jenkins Dashboard:
1. Click **New Item** → **Pipeline**
2. Add GitHub Repo URL
3. In **Pipeline Script**, add:

```groovy
pipeline {
    agent any

    environment {
        DOCKERHUB_USER = credentials('dockerhub-user')
        DOCKERHUB_PASS = credentials('dockerhub-pass')
        EC2_USER = 'ubuntu'
        EC2_HOST = '<EC2_PUBLIC_IP>'
        PEM_KEY = credentials('ec2-pem-key')
    }

    stages {
        stage('Clone Repo') {
            steps {
                git 'https://github.com/<your-repo>/php-app.git'
            }
        }

        stage('Build Docker Image') {
            steps {
                sh 'docker build -t php-app .'
            }
        }

        stage('Login to DockerHub') {
            steps {
                sh 'echo $DOCKERHUB_PASS | docker login -u $DOCKERHUB_USER --password-stdin'
            }
        }

        stage('Push to DockerHub') {
            steps {
                sh 'docker tag php-app $DOCKERHUB_USER/php-app:latest'
                sh 'docker push $DOCKERHUB_USER/php-app:latest'
            }
        }

        stage('Deploy to EC2') {
            steps {
                sh '''
                ssh -o StrictHostKeyChecking=no -i $PEM_KEY ${EC2_USER}@${EC2_HOST} <<EOF
                sudo docker pull $DOCKERHUB_USER/php-app:latest
                sudo docker stop php-app || true
                sudo docker rm php-app || true
                sudo docker run -d -p 80:80                     -e DB_HOST=<RDS_ENDPOINT>                     -e DB_USER=<USERNAME>                     -e DB_PASS=<PASSWORD>                     -e DB_NAME=php_app                     --name php-app $DOCKERHUB_USER/php-app:latest
                EOF
                '''
            }
        }
    }
}
```

---

## ✅ Step 6: Test Deployment

Access your app in a browser:
```
http://<EC2_PUBLIC_IP>
```

If everything is correct, you’ll see:
```
Connected to RDS Successfully!
```

---

## 🧹 Cleanup

To avoid unwanted billing:
```bash
aws ec2 terminate-instances --instance-ids <ID>
aws rds delete-db-instance --db-instance-identifier <ID> --skip-final-snapshot
```

---

## 🏁 Summary

✅ Jenkins CI/CD pipeline configured  
✅ Docker image pushed to Docker Hub  
✅ Deployed PHP app automatically to EC2  
✅ Connected to MySQL on AWS RDS  

---

**Author:** Ritik Kumar Sahu  
**Role:** DevOps & Cloud Engineer  
