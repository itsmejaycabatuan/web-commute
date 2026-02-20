<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $routes = Route::get();
        return view('route.index', [
            'routes' => $routes
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('route.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'starting_point' => 'required|string',
            'destination' => 'required|string'
        ]);

        $route = Route::create([
            'name' => $request->name,
            'starting_point' => $request->starting_point,
            'destination' => $request->destination
        ]);

        if($route) {
            return redirect()->route('routes.index')->with('message', 'Route added successfully!');
        }
        return back()->with('error', 'Route failed to add');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $route = Route::find($id);

        if($route) {
            return view('route.edit',[
                'route' => $route
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $route = Route::find($id);

        if($route) {
            Route::destroy($id);
            return redirect()->route('routes.index')->with('message','Route deleted successfully!');
        }
        return redirect()->route('routes.index')->with('message','Route failed deleted!');
    }
}
