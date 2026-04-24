# Gym Management System with AI Assistants

This repository combines a PHP-based gym management web application with two AI-powered assistant layers:

- A natural-language database chatbot for gym operations
- A FastAPI bot for Facebook Messenger and Telegram

The project is designed around a MySQL `gym_management` database and focuses on membership operations, bookings, attendance, payments, and conversational access to gym data.

## Project Overview

The repository contains three main parts:

1. `web/`  
   A gym management website built with PHP, PDO/MySQL, session-based authentication, OTP login verification, and role-aware dashboards for admins, trainers, and clients.

2. `server.php` + `index.php`  
   A standalone AI database chatbot interface that converts natural language into SQL using Groq, executes the query, and returns a human-friendly response.

3. `business_facebook_bot/`  
   A FastAPI bot that receives Facebook Messenger and Telegram webhook messages, queries the same gym database, and replies using Groq-generated responses. It also supports voice transcription and optional audio replies.

## Core Features

- User registration and login
- OTP-based login verification by email
- Role-based access for `admin`, `trainer`, and `client`
- Membership package management
- Booking and trainer session scheduling
- Attendance check-in and check-out
- Payment tracking and revenue visibility
- AI web chatbot for gym database operations
- AI Messenger and Telegram bot with shared database access
- Voice message transcription using Groq Whisper
- Optional AI-generated voice replies with gTTS
- Facebook post scheduling flow via natural-language requests in the chatbot

## Tech Stack

### Web application

- PHP
- MySQL
- PDO / MySQLi
- HTML, CSS, JavaScript
- PHPMailer

### AI and bot services

- Python
- FastAPI
- Uvicorn
- Groq API
- gTTS
- Requests
- MySQL Connector/Python
- python-dotenv

## Repository Structure

```text
.
|-- business_facebook_bot/
|   |-- main.py              # Facebook + Telegram bot server
|   |-- db.py                # Python DB connection and chat history storage
|   |-- groq_ai.py           # Intent detection, response generation, transcription
|   |-- telegram.py          # Telegram webhook helper / launcher
|   |-- requirements.txt
|   `-- .env
|-- web/
|   |-- bootstrap.php        # Shared app bootstrap, auth helpers, DB access
|   |-- index.php            # Main website landing page
|   |-- dashboard.php        # Role-based dashboard
|   |-- members.php          # Admin user directory
|   |-- packages.php         # Package management
|   |-- bookings.php         # Booking management
|   |-- attendance.php       # Attendance records
|   |-- payments.php         # Payment management
|   |-- login.php
|   |-- register.php
|   |-- verify_otp.php
|   `-- chat_api.php         # Website fitness chatbot endpoint
|-- Database/
|   `-- gym_management.sql   # Main SQL dump
|-- index.php                # Standalone AI DB chatbot UI
|-- server.php               # Standalone AI DB chatbot backend
|-- setup_db.php             # SQL import utility
|-- db_connect.php           # Database bootstrap for chatbot flow
|-- config.php               # Groq config for PHP chatbot
`-- generate_report.py       # DOCX architecture report generator
```

## How It Works

### 1. Gym management web app

The `web/` application provides the traditional product experience:

- Admins can view members, manage packages, record payments, and monitor bookings
- Trainers can review and update assigned sessions
- Clients can register, log in, book sessions, track attendance, and view payments

The app uses `web/bootstrap.php` for:

- Shared database access
- Authentication and session helpers
- CSRF protection
- Flash messages
- OTP email sending

### 2. Natural-language database chatbot

The root chatbot flow works like this:

1. The user sends a message from the chat UI in `index.php`
2. `server.php` reads the current database schema
3. Groq generates SQL from the user's natural-language request
4. The SQL is executed against MySQL
5. Groq turns the result into a human-readable answer

This chatbot can also detect Facebook post scheduling requests and insert them into a `scheduled_posts` table.

### 3. Facebook and Telegram bot

The Python service in `business_facebook_bot/` exposes webhook endpoints for:

- `GET /webhook` for Facebook verification
- `POST /webhook` for Facebook messages
- `POST /telegram-webhook` for Telegram messages

The bot:

- Detects intent from user messages
- Reads live data from the gym database
- Generates concise natural-language replies
- Stores recent chat history in a `chat_history` table
- Can transcribe voice messages
- Can send voice replies using generated MP3 audio

## Database

The system expects a MySQL database named `gym_management`.

Included SQL dumps:

- `Database/gym_management.sql`
- `web/Database/gym_management.sql`

Important tables referenced in the code include:

- `users`
- `packages`
- `bookings`
- `attendance`
- `payments`
- `scheduled_posts`
- `chat_history`

## Local Setup

### Prerequisites

- XAMPP or another PHP + MySQL local stack
- PHP 8.x recommended
- MySQL / MariaDB
- Python 3.10+
- Internet access for Groq, Facebook, Telegram, and Google services

### 1. Clone or place the project

Place the project inside your XAMPP web root, for example:

```powershell
c:\xampp\htdocs\god
```

### 2. Import the database

Option A: run the setup script in the browser:

```text
http://localhost/god/setup_db.php
```

Option B: import `Database/gym_management.sql` manually into MySQL.

### 3. Run the PHP web application

Open:

```text
http://localhost/god/web/
```

### 4. Run the standalone AI database chatbot

Open:

```text
http://localhost/god/
```

### 5. Set up the Python bot

Create and activate a virtual environment, then install dependencies:

```powershell
cd business_facebook_bot
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
```

Start the FastAPI app:

```powershell
uvicorn main:app --host 0.0.0.0 --port 8000 --reload
```

Or use:

```powershell
python telegram.py
```

This helper can set the Telegram webhook and then launch the FastAPI server.

## Environment Variables for `business_facebook_bot`

The Python bot reads configuration from `business_facebook_bot/.env`.

Expected variables:

```env
PAGE_ACCESS_TOKEN=
PAGE_ID=
VERIFY_TOKEN=
TELEGRAM_BOT=
GROQ_API_KEY=
MODEL_NAME=
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=gym_management
EMAIL_SENDER=
EMAIL_PASSWORD=
```

## Suggested Usage

### Web app

- Register a client account
- Log in and verify OTP
- Browse packages
- Create bookings
- Record attendance
- Track payments

### Root AI chatbot

Example prompts:

- `Show all members`
- `How many trainers are there?`
- `Show total payments`
- `Create a Facebook post for a discount offer tomorrow at 8 pm`

### Facebook / Telegram bot

Example prompts:

- `What packages do you offer?`
- `How many trainers are available?`
- `What is the price of the premium package?`
- `Give me a beginner workout tip`

## Security Notes

This project is functional as a local prototype, but several credentials are currently hardcoded in the codebase instead of being fully externalized. Before deploying publicly, you should:

- Move all API keys and email credentials into environment variables
- Rotate any keys or app passwords already committed to the repository
- Remove secrets from tracked source files
- Restrict destructive SQL actions in AI-generated query flows
- Add stronger validation and authorization around admin-only operations

## Known Design Notes

- The repo currently mixes a production-style web app and prototype chatbot utilities in the same root
- Both PHP and Python services depend on the same MySQL schema
- The AI SQL flow is powerful, but it should be hardened before real production use
- `connect.py` appears to be a minimal earlier webhook experiment, while `main.py` is the active bot server

## Future Improvements

- Move all secrets to `.env` files outside the web root
- Add Composer support for PHP dependencies
- Add migrations instead of raw SQL import only
- Add tests for booking, attendance, and payment flows
- Add Docker support for PHP, MySQL, and FastAPI services
- Add role/permission middleware around all mutable operations
- Add webhook deployment instructions for Facebook and Telegram

## License

No license file is currently included in this repository. Add one if you want to define usage and redistribution terms.
