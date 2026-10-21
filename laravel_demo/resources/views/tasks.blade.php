<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task List</title>
</head>
<body>
    <h1>My Tasks</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <form action="{{ route('tasks.store') }}" method="POST">
        @csrf

        <label for="task_title">Task title:</label><br>
        <input type="text" id="task_title" name="task_title"
               value="{{ old('task_title') }}"><br>
        @error('task_title')
            <p>{{ $message }}</p>
        @enderror

        <label for="description">Description:</label><br>
        <textarea id="description" name="description">{{ old('description') }}</textarea><br>

        <button type="submit">Add Task</button>
    </form>

    <h2>Saved Tasks</h2>

    @forelse ($tasks as $task)
        <article>
            <h3>{{ $task->task_title }}</h3>
            <p>{{ $task->description ?: 'No description provided.' }}</p>
            <p>Status: {{ $task->completed ? 'Completed' : 'Not completed' }}</p>
        </article>
        <hr>
    @empty
        <p>No tasks have been added yet.</p>
    @endforelse
</body>
</html>
