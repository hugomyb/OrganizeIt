# Organize It: Project Management Tool

This project is a task management tool built on Laravel 11, designed to easily create, organize, and manage tasks.

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & npm
- MySQL or MariaDB database
- Algolia (for real-time search)

## Installation

Follow the steps below to install and configure the project.

### 1. Clone the repository

```bash
git clone https://github.com/hugomyb/OrganizeIt.git
cd OrganizeIt
```

### 2. Install backend dependencies

```bash
composer install
```

### 3. Install frontend dependencies

```bash
npm install
```

### 4. Configure the `.env` file

Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
```

Then configure your database settings and Algolia API keys.

You will need to create an account on [Algolia](https://www.algolia.com/) and retrieve your `ALGOLIA_APP_ID` and `ALGOLIA_SECRET` to add to the `.env` file:

```
ALGOLIA_APP_ID=your_algolia_app_id
ALGOLIA_SECRET=your_algolia_secret_key
```

### 5. Run migrations and seed the database

```bash
php artisan migrate --seed
```

### 6. Link the storage folder

```bash
php artisan storage:link
```

### 7. Create the first user account

Use the following command to create an administrator user to access the management interface:

```bash
php artisan make:filament-user
```

### 8. Build frontend assets

```bash
npm run build
```

### 9. Start the development server

You can start the development server with:

```bash
php artisan serve
```
