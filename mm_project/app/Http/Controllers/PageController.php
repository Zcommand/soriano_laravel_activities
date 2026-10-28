<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function about()
    {
        $data = [
            'course' => 'IT0049',
            'module' => 'Laravel Foundations',
        ];

        return view('about', $data);
    }

    public function task($id)
    {
        $data = [
            'id' => $id,
            'title' => 'Fave Meal: Lunch!',
        ];

        return view('task', $data);
    }
}
