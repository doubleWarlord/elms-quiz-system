# ELMS Quiz System - Setup Guide

## Prerequisites

- Docker & Docker Compose
- PHP 8.2+
- Node.js 18+
- Composer
- MySQL 8.0+

## Installation

### Using Docker (Recommended)

1. **Clone the repository**
   ```bash
   git clone https://github.com/doubleWarlord/elms-quiz-system.git
   cd elms-quiz-system
   ```

2. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```

3. **Install dependencies**
   ```bash
   docker exec elms_app composer install
   docker exec elms_node npm install
   ```

4. **Setup environment**
   ```bash
   docker exec elms_app cp .env.example .env
   docker exec elms_app php artisan key:generate
   ```

5. **Run migrations**
   ```bash
   docker exec elms_app php artisan migrate --seed
   ```

6. **Access the application**
   - Frontend: http://localhost:5173
   - Backend API: http://localhost:8000/api

### Local Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/doubleWarlord/elms-quiz-system.git
   cd elms-quiz-system
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   - Update DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env

6. **Run migrations**
   ```bash
   php artisan migrate --seed
   ```

7. **Start development servers**
   ```bash
   # Terminal 1: Laravel server
   php artisan serve

   # Terminal 2: Vite dev server
   npm run dev
   ```

8. **Access the application**
   - Frontend: http://localhost:5173
   - Backend API: http://localhost:8000/api

## Database Schema

### Users Table
- id, name, email, password, role (student/teacher/admin), timestamps

### Quizzes Table
- id, user_id, title, description, pass_percentage, attempts_allowed, is_published, timestamps

### Questions Table
- id, quiz_id, question_text, type, order, explanation, timestamps

### Question Media Table
- id, question_id, media_type (text/image/video/document), media_path, media_url, description, order, timestamps

### Answers Table
- id, question_id, answer_text, is_correct, order, timestamps

### Student Quizzes Table
- id, student_id, quiz_id, started_at, completed_at, score, total_questions, correct_answers, status, passed, timestamps

### Student Answers Table
- id, student_quiz_id, question_id, answer_id, student_answer, is_correct, attempt_number, timestamps

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login user
- `POST /api/auth/logout` - Logout user
- `GET /api/auth/profile` - Get user profile

### Quizzes
- `GET /api/quizzes` - List all quizzes
- `POST /api/quizzes` - Create new quiz (teacher only)
- `GET /api/quizzes/{id}` - Get quiz details
- `PUT /api/quizzes/{id}` - Update quiz
- `DELETE /api/quizzes/{id}` - Delete quiz

### Questions
- `POST /api/quizzes/{id}/questions` - Add question to quiz
- `PUT /api/questions/{id}` - Update question
- `DELETE /api/questions/{id}` - Delete question
- `POST /api/questions/{id}/media` - Add media to question

### Answers
- `POST /api/questions/{id}/answers` - Add answer option
- `PUT /api/answers/{id}` - Update answer
- `DELETE /api/answers/{id}` - Delete answer

### Student Quiz
- `POST /api/quizzes/{id}/start` - Start taking quiz
- `GET /api/quizzes/{id}/current-question` - Get current question
- `POST /api/quizzes/{id}/submit-answer` - Submit answer
- `GET /api/quizzes/{id}/results` - Get quiz results

## Features

✅ User authentication (Student/Teacher/Admin)
✅ Quiz creation with multimedia support
✅ Mandatory question completion (no skip)
✅ Unlimited attempts (configurable)
✅ Real-time feedback on answers
✅ Progress tracking
✅ Score calculation and pass/fail determination
✅ Media support (images, videos, documents, text)
✅ RESTful API
✅ Responsive Vue.js frontend
✅ Bootstrap 5 styling

## Development

### Running Tests
```bash
php artisan test
```

### Building for Production
```bash
npm run build
```

### Database Seeding
```bash
php artisan migrate:fresh --seed
```

## License

MIT License - See LICENSE file for details

## Support

For issues and questions, please open an issue on GitHub.
