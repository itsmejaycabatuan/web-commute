<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RouteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        activity()->event('Index')->log('Action performed: index');
        $routes = Route::get();

        return view('route.index', [
            'routes' => $routes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        activity()->event('Create')->log('Action performed: create');

        return view('route.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        activity()->event('Store')->log('Action performed: store');
        $request->validate([
            'starting_point' => 'required|string',
            'destination' => 'required|string',
        ]);

        $route = Route::create([
            'starting_point' => $request->starting_point,
            'destination' => $request->destination,
        ]);

        if ($route) {
            return redirect()->route('routes.index')->with('message', 'Route added successfully!');
        }

        return back()->with('error', 'Route failed to add');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        activity()->event('Edit')->log('Action performed: edit');
        $route = Route::find($id);

        if ($route) {
            return view('route.edit', [
                'route' => $route,
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        activity()->event('Update')->log('Action performed: update');
        $route = Route::find($id);

        $request->validate([
            'starting_point' => 'required|string',
            'destination' => 'required|string',
        ]);

        $route->update([
            'starting_point' => $request->starting_point,
            'destination' => $request->destination,
        ]);

        if ($route) {
            return redirect()->route('routes.index')->with('message', 'Route updated successfully!');
        }

        return back()->with('error', 'Route failed to update');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        activity()->event('Destroy')->log('Action performed: destroy');
        $route = Route::find($id);

        if ($route) {
            Route::destroy($id);

            return redirect()->route('routes.index')->with('message', 'Route deleted successfully!');
        }

        return redirect()->route('routes.index')->with('message', 'Route failed deleted!');
    }
}
