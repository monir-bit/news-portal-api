<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;

class EmployeeController extends Controller
{
    public function index(): Collection
    {
        return Employee::query()
            ->orderByRaw('department_position IS NULL')
            ->orderBy('department_position')
            ->orderByRaw('position IS NULL')
            ->orderBy('position')
            ->orderBy('id')
            ->get(['full_name', 'department', 'nick_name', 'designation', 'photo'])
            ->groupBy('department');
    }
}
