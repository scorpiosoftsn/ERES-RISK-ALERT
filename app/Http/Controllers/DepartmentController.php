<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    // Ajouter un département
    public function store(Request $request)
    {
        $request->merge(['name' => trim((string) $request->name)]);

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:departments,name',
        ]);

        $department = Department::create(['name' => $data['name']]);

        return response()->json(['success' => true, 'department' => $department]);
    }

    // Supprimer un département (refusé s'il est encore utilisé)
    public function destroy(Department $department)
    {
        if (User::where('department', $department->name)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce département est encore attribué à des utilisateurs.',
            ], 409);
        }

        $department->delete();

        return response()->json(['success' => true]);
    }
}
