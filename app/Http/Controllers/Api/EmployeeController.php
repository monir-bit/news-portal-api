<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;

class EmployeeController extends Controller
{
    public function index()
    {
        return Employee::query()
            ->orderByRaw('department_position IS NULL')
            ->orderBy('department_position')
            ->orderByRaw('position IS NULL')
            ->orderBy('position')
            ->orderBy('id')
            ->get(['full_name', 'department', 'nick_name', 'designation', 'photo'])->groupBy('department');
    }
}
