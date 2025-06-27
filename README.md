# Consultation Booking API

A Laravel-based RESTful API system for managing consultation time slots and reservations. This project allows consultants to create time slots and clients to make reservations.

## Features

- **User Authentication**: Registration and login with role-based access (Consultant/Client)
- **Time Slot Management**: Consultants can create, update, and delete time slots
- **Reservation System**: Clients can view available slots and make reservations
- **Overlap Validation**: Prevents consultants from creating overlapping time slots
- **Email Notifications**: Queued email confirmations for successful reservations
- **Redis Caching**: Improved performance for available time slots
- **Comprehensive Testing**: Unit and feature tests with 100% business logic coverage

## Technology Stack

- **Laravel 12**: PHP framework
- **MySQL**: Database
- **Redis**: Queue processing and caching
- **Laravel Sanctum**: API authentication
- **PHPUnit**: Testing framework

## API Endpoints

### Authentication

- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout
- `GET /api/user` - Get authenticated user info

### Time Slots

- `GET /api/timeslots/available` - View available time slots (public)
- `POST /api/timeslots` - Create time slot (consultant only)
- `GET /api/timeslots` - View consultant's time slots (consultant only)
- `PUT /api/timeslots/{id}` - Update time slot (consultant only)
- `DELETE /api/timeslots/{id}` - Delete time slot (consultant only)

### Reservations

- `POST /api/reservations` - Create reservation (client only)
- `GET /api/reservations` - View user's reservations (client only)
- `GET /api/reservations/future` - View future reservations (client only)
- `DELETE /api/reservations/{id}` - Cancel reservation (client only)
- `GET /api/consultant/reservations` - View consultant's reservations (consultant only)

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL
- Redis (for queue and cache)
- Node.js and npm (for front-end assets)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd novinhub-backend-challenge
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure your `.env` file**
   Update the following variables in your `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=consultation_booking
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   CACHE_STORE=redis
   QUEUE_CONNECTION=redis

   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Install Node.js dependencies**
   ```bash
   npm install
   ```

## Running the Application

### Start the Laravel development server
```bash
php artisan serve
```
The API will be available at `http://localhost:8000`

### Start the queue worker (in a separate terminal)
```bash
php artisan queue:work
```

### Start Redis server
Make sure Redis is running on your system:
```bash
redis-server
```

### Build front-end assets (if needed)
```bash
npm run dev
```

## Running Tests

Run all tests:
```bash
php artisan test
```

Run specific test types:
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature

# Specific test file
php artisan test tests/Feature/ReservationApiTest.php
```

## API Usage Examples

### Authentication

#### Register a new consultant
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Dr. John Doe",
    "email": "consultant@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "consultant"
  }'
```

#### Register a new client
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Smith",
    "email": "client@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "client"
  }'
```

#### Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "consultant@example.com",
    "password": "password123"
  }'
```

### Time Slot Management

#### Create a time slot (consultant only)
```bash
curl -X POST http://localhost:8000/api/timeslots \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "start_time": "2024-06-27T09:00:00Z",
    "end_time": "2024-06-27T10:00:00Z"
  }'
```

#### View available time slots (public)
```bash
curl -X GET http://localhost:8000/api/timeslots/available
```

### Reservation Management

#### Make a reservation (client only)
```bash
curl -X POST http://localhost:8000/api/reservations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "time_slot_id": 1
  }'
```

#### View your reservations (client only)
```bash
curl -X GET http://localhost:8000/api/reservations \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Business Rules

### Time Slot Rules
- Consultants can only create time slots for future dates
- Time slots cannot overlap with existing ones for the same consultant
- Start time must be before end time
- Reserved time slots cannot be modified or deleted

### Reservation Rules
- Only clients can make reservations
- Only future time slots can be reserved
- Each time slot can only be reserved once
- Users cannot reserve the same time slot multiple times
- Past reservations cannot be cancelled

### Role-based Access
- **Consultants**: Can create, update, delete their own time slots
- **Clients**: Can view available slots and make/cancel reservations
- **Public**: Can view available time slots without authentication

## Queue Processing

The application uses Redis queues for processing email notifications:

1. When a reservation is created, a `ReservationCreated` event is dispatched
2. The `SendReservationConfirmation` listener queues a `SendReservationEmail` job
3. The job simulates sending an email confirmation (logged for demonstration)

To process queued jobs:
```bash
php artisan queue:work
```

## Caching

Available time slots are cached using Redis for improved performance:
- Cache key: `available_time_slots`
- Cache duration: 5 minutes
- Automatically cleared when time slots or reservations are modified

## Testing Strategy

### Unit Tests
- `TimeSlotTest`: Tests overlap validation logic and model relationships
- `ReservationTest`: Tests reservation business logic and event dispatching

### Feature Tests
- `AuthApiTest`: Tests authentication endpoints
- `TimeSlotApiTest`: Tests time slot CRUD operations and validation
- `ReservationApiTest`: Tests reservation operations and access control

### Test Coverage
- Business logic validation (overlapping time slots)
- Role-based access control
- API endpoint functionality
- Event dispatching
- Database relationships

## Development Notes

### Default Test Users
The seeder creates test users with default credentials:
- **Consultant**: `consultant@example.com` / `password`
- **Client**: `client@example.com` / `password`

### Error Handling
The API returns consistent JSON error responses with appropriate HTTP status codes:
- `422`: Validation errors
- `403`: Authorization errors
- `401`: Authentication errors
- `404`: Resource not found

### Performance Considerations
- Redis caching for frequently accessed data
- Database indexes on foreign keys and search columns
- Efficient query design with eager loading

## Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## License

This project is licensed under the MIT License.
