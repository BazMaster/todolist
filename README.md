# ToDo List app


## Install

To install the ToDo List app, follow the instructions:

```
# SSH
cd folder_name
git clone git@github.com:BazMaster/todolist.git .
```

Create database.
Create `.env` file from `.env.example` in the project root.
Fill following constants: `SANCTUM_STATEFUL_DOMAINS` and database vars.

```
# SSH
composer install
npm run prod
php artisan migrate
php artisan serve
```

## Features

- Working with the task list
    - Creating new tasks
    - Updating tasks title
    - Deleting task
    - Check tasks as completed
    - Working in "unauthorized mode" with Local Storage
    - Working in "offline mode" with Local Storage, when internet connection is interrupted
    - Authorization to save tasks in database
    - Synchronisation database tasks with tasks from Local Storage
    
## API documentation (need user token from `personal_access_tokens` table)

| Method     | URI                    | Parameters                                                         | Description                                                                        |
|--------|------------------------|--------------------------------------------------------------------|------------------------------------------------------------------------------------|
| GET    | /api/tasks             | filter - `checked` or `unchecked` (optional)                       | Display a listing of the tasks. When filter option is empty - displaing all tasks. |
| POST   | /api/tasks             | title - `required,max:100`; status - `boolean`; offline - `boolean`; | Store a newly created task in database.                                            |
| PUT    | /api/tasks/{task_id}   | title - `required,max:100`; status - `boolean`; offline - `boolean`; | Update the specified task in database.                                             |
| DELETE | /api/tasks/{task_id}   |                                                                    | Remove the specified task from database.                                           |
| POST   | /api/tasks/saveStorage | rows = `required,json`                                            | Saving tasks in database from local storage                                        |
