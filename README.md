# NexusThesis

NexusThesis is a Laravel application built with Livewire. These steps set it up locally on Windows or macOS using PHP's built-in development server. Valet and Docker are not required.

## Requirements

- PHP 8.3 or newer, with `pdo_sqlite` and `sqlite3` enabled
- Composer 2
- Node.js and npm
- Git (only if cloning the repository or pulling updates with Git)

### Windows

Install PHP from [windows.php.net](https://windows.php.net/download/), and add its folder to your `PATH`. Install [Composer](https://getcomposer.org/download/) with the Windows installer and [Node.js LTS](https://nodejs.org/en/download). Install [Git for Windows](https://git-scm.com/download/win) only if you plan to clone the repository or pull updates with Git.

Make sure the `php.ini` used by your command line PHP has the `pdo_sqlite` and `sqlite3` extensions enabled. You can check which `php.ini` is active with:

```powershell
php --ini
```

### macOS

Install [Homebrew](https://brew.sh/) if it is not already installed, then install the tools in Terminal:

```sh
brew install php composer node
```

## Setup

### Download as a ZIP

1. Open the [NexusThesis GitHub repository](https://github.com/Syeilreyzan/NexusThesis), select **Code**, then **Download ZIP**.
2. Extract the ZIP archive. On Windows, right-click it and select **Extract All**. On macOS, double-click the ZIP file.
3. Open PowerShell or Terminal in the extracted project folder. Make sure this folder contains `artisan` and `composer.json`; do not run the setup from an outer folder that only contains another `NexusThesis` folder.

### Clone with Git

If Git is installed, you can instead clone the repository and enter its folder:

```sh
git clone https://github.com/Syeilreyzan/NexusThesis.git
cd NexusThesis
```

The remaining steps are the same whether you downloaded the ZIP or cloned with Git. Run them from the project folder that contains `artisan` and `composer.json`.

Create the SQLite database file, then run the project setup. Use the command for your terminal.

**Windows (PowerShell):**

```powershell
if (!(Test-Path "database/database.sqlite")) {
	New-Item -ItemType File -Path "database/database.sqlite" | Out-Null
}
composer run setup
```

**macOS (Terminal):**

```sh
touch database/database.sqlite
composer run setup
```

The setup command installs PHP and JavaScript dependencies, creates `.env` from `.env.example` if needed, generates the application key, runs database migrations, and builds the frontend assets. The application uses SQLite by default. Keep local settings in `.env`; do not commit that file.

## Run the Application

Start Laravel's built-in development server from the project folder:

```sh
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000) in your browser. Stop the server with `Ctrl+C`.

For frontend hot reload while developing, open a second terminal in the project folder and run:

```sh
npm run dev
```

## Run Database Migrations

The setup command already runs migrations. To apply any new migrations later, run:

```sh
php artisan migrate
```

To drop all tables, recreate the database schema, and run the default seeders from scratch, use:

```sh
php artisan migrate:fresh --seed
```

**Warning:** `migrate:fresh --seed` deletes all existing data in the database. Do not use it if you need to keep your data.

## Seed the Database

After setup has created the database, run the default seeders to add the project's roles, permissions, and sample user and thesis data:

```sh
php artisan db:seed
```

## Run Tests

Run the complete test suite from the project folder:

```sh
php artisan test
```

To run one test file, pass its path:

```sh
php artisan test tests/Feature/Auth/RegistrationTest.php
```

## Getting Updates

If you cloned the repository with Git, update it from the project folder:

```sh
git pull
```

If you downloaded a ZIP, download and extract the latest ZIP to update the project. Keep your existing `.env` and `database/database.sqlite` so your local configuration and data are preserved.
