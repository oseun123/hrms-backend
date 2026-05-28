<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\Requests\RequestTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = RequestTemplate::where('tenant_id', Auth::user()->tenant_id)
            ->with('workflow.levels.approver');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('is_active')) {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:predefined,custom',
            'template_key' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:100',
            'fields' => 'nullable|array',
            'is_active' => 'boolean',
            'request_workflow_id' => 'nullable|exists:request_workflows,id',
        ]);

        $template = RequestTemplate::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'template_key' => $request->template_key,
            'icon' => $request->icon,
            'fields' => $request->fields,
            'is_active' => $request->is_active ?? true,
            'request_workflow_id' => $request->request_workflow_id,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Template created successfully',
            'data' => $template->load('workflow')
        ], 201);
    }

    public function show($id)
    {
        $template = RequestTemplate::where('tenant_id', Auth::user()->tenant_id)
            ->with('workflow.levels.approver')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $template
        ]);
    }

    public function update(Request $request, $id)
    {
        $template = RequestTemplate::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'request_workflow_id' => 'nullable|exists:request_workflows,id',
            'fields' => 'nullable|array',
        ]);

        $template->update($request->only([
            'name', 'description', 'is_active', 'request_workflow_id', 'fields'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Template updated successfully',
            'data' => $template->load('workflow')
        ]);
    }

    public function destroy($id)
    {
        $template = RequestTemplate::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        
        // Soft delete
        $template->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Template deleted successfully'
        ]);
    }
}
