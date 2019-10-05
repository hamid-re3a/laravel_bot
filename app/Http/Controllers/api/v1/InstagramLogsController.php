<?php

namespace App\Http\Controllers;

use App\InstagramLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InstagramLogsController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return response()->json(InstagramLog::all());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $log           = new InstagramLog();
        $log->username = $request->username;
        $log->time     = $request->time;
        $log->save();
        return response()->json(['success' => true], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        return response()->json(InstagramLog::where('id', $id)->firstOrFail());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $log           = InstagramLog::where('id', $id)->firstOrFail();
        $log->username = $request->username;
        $log->time     = $request->time;
        $log->save();
        return response()->json(['success' => true], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $log = InstagramLog::where('id', $id)->firstOrFail();
        try {
            $log->delete();
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
        return response()->json(['success' => true], 200);
    }
}
