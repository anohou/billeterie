<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SupervisorDashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboards/Supervisor');
    }
}
