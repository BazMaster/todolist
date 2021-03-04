<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Task;
use Auth;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filter = $request->get('filter');

        $tasks = Task::when($filter === 'checked', function ($q) {
                return $q->where('status', true);
            })
            ->when($filter === 'unchecked', function ($q) {
                return $q->where('status', false);
            })
            ->where('user_id', Auth::id())
            ->latest()->get();

        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param TaskRequest $request
     *
     * @return JsonResponse
     */
    public function store(TaskRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        $row = Task::create($data);

        if(!$row) {
            return response()->json(['status' => false], 400);
        }

        return response()->json(['status' => true, 'message' => 'Task is added']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  TaskRequest  $request
     * @param Task $task
     *
     * @return JsonResponse
     */
    public function update(TaskRequest $request, Task $task): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        $row = $task->update($data);

        if(!$row) {
            return response()->json(['status' => false], 400);
        }

        return response()->json(['status' => true, 'message' => 'Task is changed']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Task $task
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Task $task): JsonResponse
    {
        if($task->status === true) {
            $row = $task->delete();

            if(!$row) {
                return response()->json(['status' => false], 400);
            }

            return response()->json(['status' => true, 'message' => 'Task is deleted']);
        }

        return response()->json(['status' => false, 'message' => 'Task is not completed'], 400);
    }

    /**
     * Saving tasks in database from local storage
     * @param Request $request
     *
     * @return mixed
     */
    public function saveStorage(Request $request) {
        $request->validate(['rows' => 'required']);

        $rows = json_decode($request->rows, true);

        foreach($rows as $row) {
            $task = new Task();
            $task->user_id = Auth::id();
            $task->title = $row['title'];
            $task->status = $row['status'];
            $task->offline = true;
            $task->save();
        }

        return response()->json(['success' => true, 'message' => 'Offline tasks are synced successfully.']);
    }
}
