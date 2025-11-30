<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserRouteAssignment;
use App\Models\User;
use App\Models\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserAssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $assignments = UserRouteAssignment::with(['user', 'route'])->orderBy('created_at', 'desc')->paginate(20);
        return Inertia::render('Admin/Assignments/Index', [
            'assignments' => $assignments,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Assignments/Form', [
            'users' => User::whereIn('role', ['seller', 'supervisor'])->orderBy('name')->get(['id', 'name', 'email', 'role']),
            'routes' => Route::orderBy('name')->get(['id', 'name'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'route_id' => 'required|uuid|exists:routes,id',
            'active' => 'boolean',
        ]);
        UserRouteAssignment::create($data);
        return redirect()->route('admin.assignments.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(UserRouteAssignment $userRouteAssignment)
    {
        return redirect()->route('admin.assignments.edit', $userRouteAssignment);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserRouteAssignment $userRouteAssignment)
    {
        return Inertia::render('Admin/Assignments/Form', [
            'assignment' => $userRouteAssignment,
            'users' => User::whereIn('role', ['seller', 'supervisor'])->orderBy('name')->get(['id', 'name', 'email', 'role']),
            'routes' => Route::orderBy('name')->get(['id', 'name'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserRouteAssignment $userRouteAssignment)
    {
        $data = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'route_id' => 'required|uuid|exists:routes,id',
            'active' => 'boolean',
        ]);
        $userRouteAssignment->update($data);
        return redirect()->route('admin.assignments.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserRouteAssignment $userRouteAssignment)
    {
        $userRouteAssignment->delete();
        return back();
    }
}
