# NexusThesis Setup Guide

This guide is for developers and IT staff who need to install, run, or update NexusThesis. If you just want to understand or use the system, see the [User Guide](USER_GUIDE.md). Back to the [main page](../README.md).

## Contents

- [Requirements](#requirements)
- [Install the tools](#install-the-tools)
- [Get the code](#get-the-code)
- [Prepare the application](#prepare-the-application)
- [Run the application](#run-the-application)
- [Demo accounts](#demo-accounts)
- [Getting updates](#getting-updates)
- [Troubleshooting](#troubleshooting)

## Requirements

| Tool         | What it is for                                                   | Version                                             |
| ------------ | ---------------------------------------------------------------- | --------------------------------------------------- |
| **PHP**      | The language the application is written in                       | 8.3 or newer, with `pdo_sqlite` and `sqlite3` enabled |
| **Composer** | PHP's package installer, which downloads the libraries PHP needs | 2.x                                                 |
| **Node.js**  | Runs the tools that build the frontend (and includes `npm`)      | Current LTS                                         |
| **Git**      | Downloads the code and pulls updates                             | Only if you clone or pull with Git                  |

The application stores its data in **SQLite**, a database that lives in a single file, so you don't need to install a separate database server.

## Install the tools

### Windows

1. Install PHP from [windows.php.net](https://windows.php.net/download/) and add its folder to your `PATH`. (`PATH` is the list of folders Windows searches when you type a command. Search "environment variables" in the Start menu, edit **Path**, and add the folder where you put PHP.)
2. Install [Composer](https://getcomposer.org/download/) with the Windows installer.
3. Install [Node.js LTS](https://nodejs.org/en/download).
4. Install [Git for Windows](https://git-scm.com/download/win) only if you plan to clone the repository or pull updates with Git.

Close and reopen PowerShell afterward so it picks up the changes, then check that everything is installed:

```powershell
php -v
composer -V
node -v
```

Each command should print a version number. If one says "not recognized", see [Troubleshooting](#troubleshooting).

### macOS

Install [Homebrew](https://brew.sh/) if you don't have it, then run this in Terminal:

```sh
brew install php composer node
```

Check the installation:

```sh
php -v
composer -V
node -v
```

## Get the code

Choose one option.

### Option 1: Download as a ZIP

1. Open the [NexusThesis GitHub repository](https://github.com/Syeilreyzan/NexusThesis), select **Code**, then **Download ZIP**.
2. Extract the ZIP. On Windows, right-click it and select **Extract All**. On macOS, double-click it.
3. Open a terminal in the extracted folder:
   - **Windows:** open the folder in File Explorer, click the address bar, type `powershell`, and press Enter.
   - **macOS:** open Terminal, type `cd ` (with a trailing space), drag the folder into the Terminal window, and press Enter.
4. Make sure this folder contains the files `artisan` and `composer.json`. Don't run the setup from an outer folder that only contains another `NexusThesis` folder.

### Option 2: Clone with Git

```sh
git clone https://github.com/Syeilreyzan/NexusThesis.git
cd NexusThesis
```

The remaining steps are the same for both options. Run them from the project folder that contains `artisan` and `composer.json`.

## Prepare the application

First, make sure the `php.ini` used by your command-line PHP has the `pdo_sqlite` and `sqlite3` extensions enabled. (`php.ini` is PHP's settings file.) You can see which one is active with:

```sh
php --ini
```

`composer setup` runs the dependency installation commands as part of setup. If you only need to install or reinstall dependencies without running the full setup, use:

```sh
composer install
npm install
```

Then install the dependencies, create the database, and seed the default users:

```sh
composer setup
php artisan db:seed
```

To also load the demo users, run the `UserSeeder`:

```sh
php artisan db:seed --class=UserSeeder
```

What `composer setup` does:

- installs the PHP and JavaScript dependencies
- creates `.env` from `.env.example` if needed
- generates the application key
- runs the database migrations (creates the tables)
- builds the frontend assets

The application uses SQLite by default. Keep local settings in `.env` and do not commit that file.

The setup script is defined in `composer.json`, and the seeder is in `database/seeders/DatabaseSeeder.php`, and `database/seeders/UserSeeder`.

## Run the application

Start Laravel's built-in development server from the project folder:

```sh
php artisan serve
```

You should see a line similar to `Server running on [http://127.0.0.1:8000]`. Open [http://127.0.0.1:8000](http://127.0.0.1:8000) in your browser. Stop the server with `Ctrl+C`.

For frontend hot reload while developing (optional), open a second terminal in the project folder and run:

```sh
npm run dev
```

## Demo accounts

> **Warning:** these accounts and the shared password are for local demos and testing only. Do not seed them in production.

All demo accounts use the password `Password123!1`.

| Role      | Accounts                                                        |
| --------- | --------------------------------------------------------------- |
| Admin     | `admin@nexusthesis.com`                                         |
| Lecturers | `lecturer01@nexusthesis.com` up to `lecturer20@nexusthesis.com` |
| Students  | `student01@nexusthesis.com` up to `student10@nexusthesis.com`   |

Students 01 to 05 come with pre-linked theses, and students 06 to 10 are clean accounts for testing new submissions. The full list is in the [User Guide](USER_GUIDE.md#trying-a-demo).

## Getting updates

If you cloned the repository with Git, update it from the project folder:

```sh
git pull
```

If you downloaded a ZIP, download and extract the latest ZIP. Keep your existing `.env` and `database/database.sqlite` so your local configuration and data are preserved.

## Troubleshooting

**`php`, `composer`, or `node` is "not recognized" or "command not found"**
The tool isn't installed, or its folder isn't in your `PATH`. On Windows, re-check the `PATH` step and reopen PowerShell. On macOS, re-run the `brew install` command.

**`Could not open input file: artisan`**
You're in the wrong folder. Run `dir` (Windows) or `ls` (macOS) and confirm you can see `artisan` and `composer.json`. If you only see another `NexusThesis` folder, `cd` into it.

**`could not find driver` or `Class "PDO" not found`**
The SQLite extensions aren't enabled. Run `php --ini` to find the active `php.ini` file. On Windows, if it says "(none)", copy `php.ini-development` to `php.ini` in the PHP folder first. Then open `php.ini` in a text editor and make sure these two lines don't start with a semicolon:

```ini
extension=pdo_sqlite
extension=sqlite3
```

Save the file, open a new terminal, and check with `php -m`. The list should include `pdo_sqlite` and `sqlite3`.

**`Database file at path [...] does not exist`**
Create an empty database file, then run the setup again:

```sh
# macOS
touch database/database.sqlite

# Windows (PowerShell)
New-Item database/database.sqlite -ItemType File
```

**The port is already in use**
Start the server on another port, then open that address in your browser:

```sh
php artisan serve --port=8080
```

**The page loads but looks unstyled or broken**
The frontend assets may not have been built. Run `npm install` and `npm run build`, then reload the page.

**I downloaded a ZIP and `git pull` doesn't work**
A ZIP download isn't a Git repository. Download the latest ZIP instead, or clone the project with `git clone`.
