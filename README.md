# NexusThesis

NexusThesis is a Laravel application built with Livewire.

## Requirements

- PHP 8.3 or newer, with SQLite support enabled
- Composer
- Node.js and npm
- Git

## Clone the Repository

Run these commands in a terminal:

```sh
git clone https://github.com/Syeilreyzan/NexusThesis.git
cd NexusThesis
```

The project uses SQLite by default. The setup command installs PHP and JavaScript dependencies, creates `.env` if it does not exist, generates the application key, runs database migrations, and builds the frontend assets. Keep local settings in `.env`; do not commit that file.

## Windows (PowerShell)

Create the SQLite database file if it does not exist, then install the project:

```powershell
if (!(Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite }
composer run setup
```

Start the Laravel development server:

```powershell
php artisan serve
```

Open `http://127.0.0.1:8000`. Stop the server with `Ctrl+C`.

## macOS (Laravel Valet)

From the project directory, create the SQLite database and install the project:

```sh
touch database/database.sqlite
composer run setup
valet link nexus-thesis
```

Set `APP_URL` in `.env` to `http://nexus-thesis.test`, then visit that address in your browser. Valet starts the web server automatically. If you prefer Laravel's built-in server instead of Valet, run:

```sh
php artisan serve
```

For the built-in server, set `APP_URL` in `.env` to `http://127.0.0.1:8000`.

## Getting Updates

Cloning the repository configures GitHub as the `origin` remote. To pull the latest changes later, run this from the project directory:

```sh
git pull
```
