<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=tasks-simple-2">
    <title>Task List</title>
</head>
<body>
    <main class="task-page">
        <header class="page-heading">
            <h1>My Tasks</h1>
            <span class="task-count">{{ $tasks->count() }} {{ $tasks->count() === 1 ? 'task' : 'tasks' }}</span>
        </header>

        @if (session('success'))
            <p class="success-message" role="status">{{ session('success') }}</p>
        @endif

        <div class="task-layout">
            <section class="task-form" aria-labelledby="form-heading">
                <h2 id="form-heading">Add Task</h2>

                <form action="{{ route('tasks.store') }}" method="POST">
                    @csrf

                    <div class="field">
                        <label for="task_title">Task title</label>
                        <input type="text" id="task_title" name="task_title"
                               value="{{ old('task_title') }}" maxlength="255" required
                               @error('task_title') aria-invalid="true" aria-describedby="title-error" @enderror>
                        @error('task_title')
                            <p class="field-error" id="title-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="description">Description <span>(optional)</span></label>
                        <textarea id="description" name="description" rows="5"
                                  @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description') }}</textarea>
                        @error('description')
                            <p class="field-error" id="description-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit">Add Task</button>
                </form>
            </section>

            <section class="task-records" aria-labelledby="records-heading">
                <h2 id="records-heading">Saved Tasks</h2>

                @if ($tasks->isEmpty())
                    <p class="empty-message">No tasks have been added yet.</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Task title</th>
                                <th scope="col">Description</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tasks as $task)
                                <tr>
                                    <td class="task-title">{{ $task->task_title }}</td>
                                    <td class="task-description">{{ $task->description ?: 'No description provided.' }}</td>
                                    <td>
                                        <span class="status {{ $task->completed ? 'completed' : 'pending' }}">
                                            {{ $task->completed ? 'Completed' : 'Not completed' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        </div>
    </main>
</body>
</html>

