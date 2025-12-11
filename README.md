# AS System - Service Management Application

> A standalone service management application designed to be integrated as a subdirectory (`/as`) in existing WordPress installations.

## 📋 Project Overview

AS System is a legacy PHP-based AS (After-Sales) and service management platform. This refactored version is optimized for:
- Integration with existing WordPress sites
- Docker containerization
- Modern hosting environments
- Easy deployment and maintenance

## 🏗️ Directory Structure

```
.
├── www/                           # Web root
│   ├── as/                        # AS System (main application)
│   │   ├── config/                # Configuration files
│   │   ├── includes/              # Common functions & utilities
│   │   ├── public/                # Static assets (CSS, JS, images)
│   │   ├── as_center/             # Service center module
│   │   ├── parts/                 # Parts/Materials module
│   │   ├── member/                # Member management
│   │   ├── order/                 # Order management
│   │   └── index.php              # Entry point
│   └── [WordPress files]          # Existing WordPress installation
│
├── database/                      # Database management
│   ├── migrations/                # SQL migration files
│   │   ├── 001_initial.sql
│   │   ├── 002_initial_utf8.sql
│   │   ├── 003_initial_utf8_fixed.sql
│   │   └── zipcode/               # Zipcode data
│   └── seeds/                     # Initial data (optional)
│
├── .docker/                       # Docker configuration
│   ├── Dockerfile                 # PHP-FPM container definition
│   ├── docker-compose.yml         # Orchestration file
│   └── nginx/
│       └── app.conf               # Nginx configuration
│
├── .claude/                       # Claude Code configuration
├── .vscode/                       # VSCode settings
├── .env.example                   # Environment template
├── .gitignore                     # Git ignore rules
├── CLAUDE.md                      # Development guidelines
└── README.md                      # This file
```

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Git

### Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd mic4u
   ```

2. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your values
   ```

3. **Start containers**
   ```bash
   docker-compose -f .docker/docker-compose.yml up -d
   ```

4. **Initialize database**
   ```bash
   # Migrations run automatically on container startup
   # Or manually:
   docker exec as_mysql mysql -u mic4u_user -p < database/migrations/001_initial.sql
   ```

5. **Access the application**
   - AS System: `http://localhost/as`
   - phpMyAdmin (dev): `http://localhost:8080`

### Stop containers
```bash
docker-compose -f .docker/docker-compose.yml down
```

## 📊 Database

### Migrations
Located in `database/migrations/`:
- `001_initial.sql` - Initial schema (EUC-KR)
- `002_initial_utf8.sql` - UTF-8 version
- `003_initial_utf8_fixed.sql` - Fixed UTF-8 version
- `zipcode/` - Zipcode data

### Running migrations manually
```bash
docker exec as_mysql mysql -u mic4u_user -p mic4u < database/migrations/001_initial.sql
```

## 🔧 Configuration

### Environment Variables (.env)
```env
DB_HOST=mysql
DB_PORT=3306
DB_NAME=mic4u
DB_USER=mic4u_user
DB_PASSWORD=your_secure_password
APP_ENV=development
APP_DEBUG=true
```

### Application Config
- Main config: `www/as/config/config.php`
- Database: Update with your credentials

## 📝 Development

### File Encoding
All files use **EUC-KR** encoding (Korean character set)
- VSCode is pre-configured via `.vscode/settings.json`

### Code Style
- See `CLAUDE.md` for detailed development guidelines
- Follow existing code patterns
- No automated tests currently

### Important Notes
- Legacy PHP 5.x code with deprecated `mysql_*` functions
- No modern build pipeline
- SQL injection risks - be careful with user input
- XSS vulnerabilities possible - sanitize output

## 🐳 Docker Commands

### Build images
```bash
docker-compose -f .docker/docker-compose.yml build
```

### View logs
```bash
docker-compose -f .docker/docker-compose.yml logs -f php-fpm
docker-compose -f .docker/docker-compose.yml logs -f nginx
docker-compose -f .docker/docker-compose.yml logs -f mysql
```

### Connect to MySQL
```bash
docker exec -it as_mysql mysql -u mic4u_user -p mic4u
```

### Execute PHP commands
```bash
docker exec as_php php /var/www/html/as/index.php
```

## 📦 Deployment

### For WordPress Integration
1. Place the `www/as` directory in your WordPress root as `/as`
2. Ensure database credentials match WordPress database
3. Configure Nginx to route `/as/*` requests appropriately

### For Standalone Deployment
1. Update `APP_URL` in `.env`
2. Configure your reverse proxy (Nginx/Apache)
3. Point domain to the application

## 🔒 Security

### Files to protect:
- `www/as/config/` - Configuration files
- `www/as/includes/` - Include files
- `database/` - Database scripts

### Recommended practices:
- Change default passwords immediately
- Use HTTPS in production
- Implement rate limiting
- Add authentication middleware
- Sanitize all user input
- Escape output for HTML

## 📚 Documentation

- `CLAUDE.md` - Development guidelines and architecture
- Inline code comments - Legacy code documentation
- `database/migrations/` - Schema reference

## ⚠️ Known Issues

- Legacy PHP code uses deprecated functions
- No automated testing framework
- Potential SQL injection vulnerabilities
- No input validation/sanitization layer
- XSS risks in output

## 🎯 Future Improvements

- [ ] Migrate to modern PHP version (7.4+)
- [ ] Replace `mysql_*` functions with MySQLi/PDO
- [ ] Add automated tests
- [ ] Implement input validation/sanitization
- [ ] Add API layer
- [ ] Modern frontend framework migration
- [ ] Database abstraction layer

## 📄 License

[License information here]

## 👥 Contributors

[Contributor information here]

## 📧 Support

For issues and questions, please contact the development team.

---

**Last Updated**: October 30, 2024
**Version**: 1.0
