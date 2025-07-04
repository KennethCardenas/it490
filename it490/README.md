# Bark Buddy MVP

This repository contains a minimal implementation of the Bark Buddy platform.
It provides user authentication via RabbitMQ and basic management of dog profiles.

## Features
- User registration, login, profile update and logout using a central MQ worker
- Role based access with an admin dashboard
- Owners can create dog profiles and view their dogs
- All database actions are performed by the MQ worker service

## Database
SQL for the minimal schema is available in `api/schema.sql`.

## Running
The project requires PHP and RabbitMQ. Configure your database connection in
`api/connect.php` and start the MQ worker:

```bash
php workers/mq_worker.php
```

The web pages are located under the `pages/` directory.
