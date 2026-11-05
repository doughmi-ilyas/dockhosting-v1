<p align="center"><img src="docs/banner.svg" alt="Dock-Hosting" width="100%"></p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white">
  <img src="https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white">
  <img src="https://img.shields.io/badge/license-MIT-green">
</p>

**Dock-Hosting** is a modern Platform-as-a-Service (PaaS) that enables users to deploy PHP applications instantly with automatic subdomain provisioning and free SSL certificates.

 **Live Demo:** [https://www.dockhosting.dev](https://www.dockhosting.dev)

---

##  Key Features

### **Automatic Deployment**
- **Framework Selection:** Choose PHP (more frameworks coming soon)
- **Zero Configuration:** No manual setup required
- **Instant Deployment:** Projects go live in seconds

### **Smart Subdomain Management**
- **Vercel-Style Naming:** Auto-generated unique names (e.g., `my-app-a1b2c3.dockhosting.dev`)
- **Automatic DNS Routing:** Wildcard subdomain support via nginx-proxy
- **Free SSL:** Let's Encrypt certificates auto-generated for all projects

### **User Management**
- **Secure Authentication:** Password hashing with bcrypt
- **Personal Dashboard:** Real-time overview of deployments
- **Project Isolation:** Each project runs in its own Docker container

### **Built-in Code Editor**
- **File Manager:** Browse and edit project files directly in the browser
- **Live Updates:** Changes reflect immediately

---

##  Architecture

![Architecture](docs/architecture.svg)

**Stack:**
- **Backend:** PHP 8.2 (OOP, PDO)
- **Database:** MySQL 8.0
- **Containers:** Docker + nginx-proxy
- **SSL:** Let's Encrypt (acme-companion)
- **Frontend:** TailwindCSS, Vanilla JavaScript

---

##  Production Deployment

### Prerequisites
- Ubuntu/Debian VPS with public IP
- Docker & Docker Compose installed
- Domain name with DNS access

### Quick Start

```bash
# Clone repository
git clone https://github.com/doughmi-ilyas/dockhosting-v1.git
cd dockhosting-v1

# Create environment file
cp .env.example .env
nano .env
```

**Edit `.env`:**
```ini
DB_PASSWORD=password123
STORAGE_PATH=/home/deployer/users
HOST_BASE_PATH=/home/deployer/users
LETSENCRYPT_EMAIL=your@email.com
```

**Setup:**
```bash
# Create storage directory
sudo mkdir -p /home/deployer/users/Projects
sudo chmod -R 777 /home/deployer/users

# Create proxy network
docker network create proxy_network

# Start nginx-proxy + Let's Encrypt companion
docker compose -f docker-compose-proxy.yml up -d

# Start Dock-Hosting
docker compose up -d --build
```

### DNS Configuration

Add these records to your domain:

| Type | Name | Value |
|------|------|-------|
| A | @ | `YOUR_VPS_IP` |
| A | www | `YOUR_VPS_IP` |
| A | * | `YOUR_VPS_IP` |

The wildcard (`*`) enables automatic subdomains for user projects.

---

##  Local Development

```bash
# Requirements: Docker Desktop, Git

# Clone and setup
git clone https://github.com/doughmi-ilyas/dockhosting-v1.git
cd dockhosting-v1

# Create .env
cp .env.example .env

# Start services
docker compose up -d

# Access
# Main app: http://localhost
# Database: localhost:3306
```

---

##  Project Structure

```
dockhosting-v1/
├── api/              # API endpoints
├── cron/             # Scheduled jobs
├── docs/             # README images
├── proxy/            # Proxy config
├── Classes/          # OOP models (User, Project)
├── components/       # Reusable UI components
├── includes/         # Backend handlers (create-project, login)
├── pages/            # Frontend views (dashboard, file-manager)
├── php/              # Database connection
├── sql/              # Database schema
├── .env.example      # Environment template
├── docker-compose.yml
├── docker-compose-proxy.yml
└── Dockerfile
```

---

##  Database Model

![ERD](Mod%C3%A9lisation/ERD_Database_Model.png)

---

##  How It Works

1. **User creates project** → Selects framework and enters name
2. **Backend generates** → Unique container name with random suffix
3. **Docker launches** → `php:8.2-apache` container with volume mount
4. **nginx-proxy detects** → Auto-configures subdomain routing
5. **Let's Encrypt** → Issues SSL certificate automatically
6. **Live in seconds** → `https://project-abc123.dockhosting.dev` 

---

##  Features Roadmap

- [x] PHP framework support
- [x] Automatic SSL certificates
- [x] Subdomain routing
- [x] File manager / Code editor

---

##  License

MIT License - Feel free to use for learning and personal projects.

---

**Built with ❤️ by [Ilyas Doughmi](https://github.com/doughmi-ilyas)**