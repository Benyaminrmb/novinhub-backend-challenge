# Novinhub Backend API - Postman Testing Guide

This guide will help you set up and test the Novinhub Backend API using the provided Postman collection.

## 📁 Files Included

1. **`Novinhub_Backend_API.postman_collection.json`** - Complete API collection with all endpoints
2. **`Novinhub_Backend_Environment.postman_environment.json`** - Environment variables and settings

## 🚀 Quick Setup

### 1. Import Files into Postman

1. Open Postman
2. Click **Import** button (top left)
3. Upload both JSON files:
   - `Novinhub_Backend_API.postman_collection.json`
   - `Novinhub_Backend_Environment.postman_environment.json`

### 2. Select Environment

1. Click the environment dropdown (top right)
2. Select **"Novinhub Backend Environment"**

### 3. Configure Base URL

The environment is pre-configured with `base_url = http://localhost:8000`. If your Laravel app runs on a different port, update this in the environment variables.

## 📋 API Endpoints Overview

### 🔐 Authentication Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/register` | Register new user (client/consultant) | ❌ |
| POST | `/api/login` | Login user | ❌ |
| GET | `/api/user` | Get user profile | ✅ |
| POST | `/api/logout` | Logout user | ✅ |

### ⏰ Time Slot Endpoints

| Method | Endpoint | Description | Auth Required | Role |
|--------|----------|-------------|---------------|------|
| GET | `/api/timeslots/available` | Get available time slots (public) | ❌ | Any |
| GET | `/api/timeslots` | Get consultant's own time slots | ✅ | Consultant |
| POST | `/api/timeslots` | Create new time slot | ✅ | Consultant |
| GET | `/api/timeslots/{id}` | Get time slot details | ✅ | Any |
| PUT | `/api/timeslots/{id}` | Update time slot | ✅ | Consultant (owner) |
| DELETE | `/api/timeslots/{id}` | Delete time slot | ✅ | Consultant (owner) |

### 📅 Reservation Endpoints

| Method | Endpoint | Description | Auth Required | Role |
|--------|----------|-------------|---------------|------|
| GET | `/api/reservations` | Get user's reservations | ✅ | Client |
| POST | `/api/reservations` | Create reservation | ✅ | Client |
| GET | `/api/reservations/{id}` | Get reservation details | ✅ | Owner/Consultant |
| DELETE | `/api/reservations/{id}` | Cancel reservation | ✅ | Owner |
| GET | `/api/reservations/future` | Get future reservations | ✅ | Client |
| GET | `/api/consultant/reservations` | Get consultant's reservations | ✅ | Consultant |

## 🧪 Testing Workflow

### Step 1: Register Users

1. **Register a Client:**
   - Use "Register Client" request
   - Role: `"client"`

2. **Register a Consultant:**
   - Use "Register Consultant" request  
   - Role: `"consultant"`

### Step 2: Login

1. Use "Login" request with client credentials
2. The auth token will be automatically saved to environment variables
3. Repeat for consultant user

### Step 3: Test Time Slot Management (as Consultant)

1. **Create Time Slots:**
   - Use "Create Time Slot" request
   - Time slot ID will be automatically saved

2. **View Available Time Slots:**
   - Use "Get Available Time Slots (Public)" - no auth needed

3. **Manage Own Time Slots:**
   - Get, Update, Delete using consultant auth

### Step 4: Test Reservations (as Client)

1. **Create Reservation:**
   - Use "Create Reservation" request
   - Uses the saved `time_slot_id` from Step 3

2. **View Reservations:**
   - Get all reservations
   - Get future reservations only

3. **Cancel Reservation:**
   - Use "Cancel Reservation" request

## 🔧 Environment Variables

The collection automatically manages these variables:

| Variable | Description | Auto-Set |
|----------|-------------|----------|
| `base_url` | API base URL | Manual |
| `auth_token` | Authentication token | ✅ Login |
| `user_id` | Current user ID | ✅ Login |
| `user_role` | Current user role | ✅ Login |
| `time_slot_id` | Last created time slot ID | ✅ Create Time Slot |
| `reservation_id` | Last created reservation ID | ✅ Create Reservation |

## 📝 Request Examples

### Register Client
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "client"
}
```

### Create Time Slot
```json
{
    "start_time": "2024-12-31 14:00:00",
    "end_time": "2024-12-31 15:00:00"
}
```

### Create Reservation
```json
{
    "time_slot_id": 1
}
```

## ⚠️ Important Notes

1. **Time Validation:**
   - All times must be in the future
   - End time must be after start time

2. **Authorization:**
   - Clients can only create/view reservations
   - Consultants can only manage their own time slots
   - Users can only access their own data

3. **Business Rules:**
   - Can't reserve past time slots
   - Can't update/delete reserved time slots
   - Can't cancel past reservations
   - One reservation per user per time slot

## 🐛 Troubleshooting

### Common Issues

1. **401 Unauthorized:**
   - Make sure you're logged in
   - Check if auth token is set in environment

2. **403 Forbidden:**
   - Wrong user role for the operation
   - Trying to access someone else's data

3. **422 Validation Error:**
   - Check request body format
   - Ensure all required fields are present
   - Verify time constraints

4. **404 Not Found:**
   - Check if the resource exists
   - Verify the ID in the URL

### Environment Issues

- If requests fail, check the `base_url` environment variable
- Ensure the Laravel app is running on the correct port
- Verify the environment is selected in Postman

## 🎯 Test Scenarios

### Happy Path
1. Register consultant → Login → Create time slots
2. Register client → Login → View available slots → Create reservation
3. Consultant views their reservations
4. Client cancels reservation

### Error Cases
1. Try to create reservation with invalid time_slot_id
2. Try to update someone else's time slot
3. Try to cancel past reservation
4. Try to access protected endpoints without auth

This collection provides comprehensive testing coverage for all API functionality. Happy testing! 🚀 