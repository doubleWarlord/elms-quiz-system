# ELMS Quiz System

An E-Learning Management System (ELMS) with quiz functionality built with Laravel and Vue.js

## Features

✅ **User Management**
- Student and Teacher roles
- Secure authentication with Sanctum tokens
- Profile management

✅ **Quiz Creation & Management**
- Create unlimited quizzes with multimedia support
- Configure pass percentage and attempt limits
- Publish/unpublish quizzes
- Organize questions with proper ordering

✅ **Question Management**
- Multiple question types (Multiple Choice, True/False, Short Answer)
- Add media (images, videos, documents, text)
- Include explanations for answers
- Flexible question ordering

✅ **Quiz Taking**
- Interactive quiz interface with progress tracking
- Real-time feedback on answers
- Media display in questions
- Configurable pass percentage

✅ **Results & Analytics**
- Detailed score calculations
- Pass/fail determination
- View explanations after completion
- Attempt history

✅ **Technical Features**
- RESTful API
- Real-time authentication
- Docker containerization
- Responsive design with Bootstrap 5
- Vue 3 Composition API

## Tech Stack

### Backend
- Laravel 11.x
- Laravel Sanctum (API authentication)
- MySQL 8.0
- PHP 8.2+

### Frontend
- Vue.js 3.x
- Vue Router 4.x
- Axios
- Bootstrap 5
- Vite

## Quick Start

### Using Docker

```bash
# Clone repository
git clone https://github.com/doubleWarlord/elms-quiz-system.git
cd elms-quiz-system

# Start containers
docker-compose up -d

# Install dependencies
docker exec elms_app composer install
docker exec elms_node npm install

# Setup environment and database
docker exec elms_app php artisan migrate

# Access application
# Frontend: http://localhost:5173
# API: http://localhost:8000/api
```

### Local Setup

```bash
# Clone repository
git clone https://github.com/doubleWarlord/elms-quiz-system.git
cd elms-quiz-system

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Database setup (update .env first)
php artisan migrate

# Start servers
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

## Project Structure

```
elms-quiz-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   └── Middleware/
│   ├── Models/
│   └── Policies/
├── database/
│   └── migrations/
├── resources/
│   └── js/
│       ├── router/
│       ├── views/
│       └── app.js
├── routes/
│   └── api.php
├── docker-compose.yml
├── Dockerfile
├── vite.config.js
├── package.json
└── composer.json
```

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register user
- `POST /api/auth/login` - Login user
- `POST /api/auth/logout` - Logout
- `GET /api/auth/profile` - Get profile

### Quizzes
- `GET /api/quizzes` - List quizzes
- `POST /api/quizzes` - Create quiz
- `GET /api/quizzes/{id}` - Get quiz
- `PUT /api/quizzes/{id}` - Update quiz
- `DELETE /api/quizzes/{id}` - Delete quiz

### Student Quiz
- `POST /api/quizzes/{id}/start` - Start quiz
- `GET /api/quizzes/{id}/current-question` - Get question
- `POST /api/quizzes/{id}/submit-answer` - Submit answer
- `GET /api/quizzes/{id}/results` - Get results

## Documentation

- [Setup Guide](./SETUP.md) - Detailed installation and configuration
- [Contributing](./CONTRIBUTING.md) - Contribution guidelines

## CI/CD

This project now includes a GitHub Actions workflow at `.github/workflows/ci-cd.yml`.

### CI (on pull requests and pushes to `main`)
- Installs PHP 8.2 and Node 20
- Installs Composer and NPM dependencies
- Creates `.env` and app key
- Runs database migrations against a MySQL service container
- Runs backend tests with `php artisan test`
- Builds frontend assets with `npm run build`

### CD (on push to `main`)
- Connects to your server through SSH
- Pulls latest code
- Rebuilds and starts Docker services
- Runs production Composer install
- Runs migrations and Laravel caches
- Builds frontend assets

### Required GitHub repository secrets
- `DEPLOY_HOST` (example: `203.0.113.10`)
- `DEPLOY_PORT` (example: `22`)
- `DEPLOY_USER` (server SSH user)
- `DEPLOY_SSH_KEY` (private key content)
- `DEPLOY_PATH` (absolute path to project on server)

Make sure your target server already has Docker and Docker Compose installed, and that the repository is available at `DEPLOY_PATH`.

## License

MIT License - See [LICENSE](LICENSE) file

## Author

Muhammad Asyraf (@doubleWarlord)

## Support

For issues and feature requests, please open an issue on GitHub.
