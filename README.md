# NexusThesis

NexusThesis is a postgraduate research and thesis management platform used by the university for proposal submissions, supervision milestones, thesis uploads, examiner evaluations, and final approvals.

The system handles sensitive academic and research data, including unpublished thesis drafts, confidential examiner reports, and approval records.

## Where to start

| I am...                                                                  | Read this                              |
| ------------------------------------------------------------------------ | -------------------------------------- |
| A student, lecturer, supervisor, examiner, or administrator who wants to understand or use the system | [User Guide](docs/USER_GUIDE.md) |
| A developer or IT person who needs to install, run, or update the system | [Setup Guide](docs/SETUP.md)           |

## Quick start (for developers)

If you already have PHP 8.3+, Composer 2, and Node.js installed:

```sh
git clone https://github.com/Syeilreyzan/NexusThesis.git
cd NexusThesis
composer setup
php artisan db:seed
php artisan serve
```

Then open [http://127.0.0.1:8000](http://127.0.0.1:8000) in your browser.

Missing a tool, using a ZIP download, or hit an error? The full steps, expected output, and troubleshooting are in the [Setup Guide](docs/SETUP.md).

## Documentation

- [User Guide](docs/USER_GUIDE.md): what NexusThesis is, who uses it, how a thesis moves through the system, and how to try a demo. No technical knowledge needed.
- [Setup Guide](docs/SETUP.md): requirements, installation on Windows and macOS, running the app, updating, and troubleshooting.

## Security notes

- Keep local settings in `.env` and never commit that file.
- Never commit `database/database.sqlite`. It contains real data, including password hashes.
- The demo accounts and their shared password are for local testing only. Do not seed them in production.
