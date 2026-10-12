<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the activity logs.
     */
    public function index(): View
    {
        $activityLogs = ActivityLog::with('user')
            ->latest()
            ->paginate(15);

        return view('activity-logs.index', [
            'activityLogs' => $activityLogs,
            'logs' => $activityLogs,
        ]);
    }
}

