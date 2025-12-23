<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use App\Models\User;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = Audit::with(['user', 'auditable']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                  ->orWhere('auditable_type', 'like', "%{$search}%")
                  ->orWhere('old_values', 'like', "%{$search}%")
                  ->orWhere('new_values', 'like', "%{$search}%");
            });
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('event')) {
            $query->where('event', $request->input('event'));
        }
        
        if ($request->has('model')) {
            $query->where('auditable_type', 'like', '%' . $request->input('model'));
        }

        $audits = $query->latest()->paginate(20);
        
        $users = User::all();

        return view('audits.index', compact('audits', 'users'));
    }
    
    public function show($id)
    {
        $audit = Audit::with(['user', 'auditable'])->findOrFail($id);
        return view('audits.show', compact('audit'));
    }
}
