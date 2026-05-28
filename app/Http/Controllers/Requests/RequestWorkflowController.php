<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\Requests\RequestWorkflow;
use App\Models\Requests\RequestWorkflowLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RequestWorkflowController extends Controller
{
    public function index()
    {
        $workflows = RequestWorkflow::where('tenant_id', Auth::user()->tenant_id)
            ->with('levels.approver')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $workflows
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'levels' => 'required|array|min:1',
            'levels.*.level' => 'required|integer',
            'levels.*.approver_type' => 'required|in:manager,team_lead,secondary_manager,hr,specific_employee',
            'levels.*.approver_id' => 'required_if:levels.*.approver_type,specific_employee|nullable|exists:users,id',
        ]);

        return DB::transaction(function () use ($request) {
            $workflow = RequestWorkflow::create([
                'tenant_id' => Auth::user()->tenant_id,
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
            ]);

            foreach ($request->levels as $levelData) {
                $workflow->levels()->create([
                    'level' => $levelData['level'],
                    'approver_type' => $levelData['approver_type'],
                    'approver_id' => $levelData['approver_id'],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Workflow created successfully',
                'data' => $workflow->load('levels')
            ], 201);
        });
    }

    public function show($id)
    {
        $workflow = RequestWorkflow::where('tenant_id', Auth::user()->tenant_id)
            ->with('levels.approver')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $workflow
        ]);
    }

    public function update(Request $request, $id)
    {
        $workflow = RequestWorkflow::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'levels' => 'sometimes|required|array|min:1',
            'levels.*.level' => 'required|integer',
            'levels.*.approver_type' => 'required|in:manager,team_lead,secondary_manager,hr,specific_employee',
            'levels.*.approver_id' => 'required_if:levels.*.approver_type,specific_employee|nullable|exists:users,id',
        ]);

        return DB::transaction(function () use ($request, $workflow) {
            $workflow->update($request->only(['name', 'description', 'is_active']));

            if ($request->has('levels')) {
                $workflow->levels()->delete();
                foreach ($request->levels as $levelData) {
                    $workflow->levels()->create([
                        'level' => $levelData['level'],
                        'approver_type' => $levelData['approver_type'],
                        'approver_id' => $levelData['approver_id'],
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Workflow updated successfully',
                'data' => $workflow->load('levels')
            ]);
        });
    }

    public function destroy($id)
    {
        $workflow = RequestWorkflow::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        
        // Check if workflow is in use
        if ($workflow->templates()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete workflow as it is assigned to one or more templates.'
            ], 400);
        }

        $workflow->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Workflow deleted successfully'
        ]);
    }
}
