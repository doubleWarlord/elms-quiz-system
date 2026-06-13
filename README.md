# ELMS - E-Learning Management System
## Quiz Module with Multimedia Support

A comprehensive E-Learning Management System built with **Laravel**, **MySQL**, and **Vue.js**, focused on interactive quiz creation with multimedia content and mandatory question completion.

### 🎯 Key Features

- **Multimedia Question Support**
  - Paragraph/Text content
  - Video integration (YouTube, MP4)
  - Image/Picture inclusion (JPG, PNG, GIF)
  - Mixed media in single question

- **Mandatory Question Completion**
  - Students cannot skip questions
  - Must answer correctly to proceed
  - Unlimited attempts (configurable)
  - Real-time feedback on wrong/correct answers

- **Quiz Management**
  - Create and manage quizzes
  - Define question types and multimedia content
  - Set passing criteria
  - Track student progress

### 🛠 Tech Stack

- **Backend**: Laravel 11
- **Database**: MySQL 8.0+
- **Frontend**: Vue.js 3 / Bootstrap 5
- **Storage**: Local/S3 for media files
- **Authentication**: Laravel Sanctum

### 🚀 Quick Start

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate --seed

# Run server
php artisan serve
npm run dev
```

### 📚 Key Components

- **Users**: Student and teacher accounts
- **Quizzes**: Quiz configuration
- **Questions**: Questions with multimedia
- **StudentProgress**: Tracking quiz completion
- **Multimedia Storage**: Videos, images, documents

### 🔌 API Endpoints

**Authentication**
- `POST /api/auth/register`
- `POST /api/auth/login`

**Quizzes**
- `GET /api/quizzes`
- `POST /api/quizzes` (Teacher)
- `GET /api/quizzes/{id}`

**Questions**
- `GET /api/quizzes/{id}/questions`
- `POST /api/quizzes/{id}/submit-answer`

### 📝 License

MIT
