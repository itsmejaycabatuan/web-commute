<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Mail\EmailVerification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
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
        //
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
        //
    }

    public function register(Request $request) {
// dd($request->all());
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'confirm-password' => 'required|same:password',
            'terms' => 'required'
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        if($user) {
            Auth::login($user);
            event(new Registered($user));
            return redirect()->route('commuter.dashboard')->with('success', 'User Successfully Registered!');
        }
        return back()->with('error', 'User Failed to Register.');
    }

    public function login(Request $request) {
    
        // dd($request->all());
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8'
        ]);

        if(Auth::attempt($validated)) {
            $request->session()->regenerate();
            return redirect()->route('commuter.dashboard')->with('success','Logged in Successfully!');
        }

        throw ValidationException::withMessages([
            'credentials' => "Sorry, invalid credentials"
        ]);

        // $user = User::where('email', $request->email)->first();

        // if(!$user) {
        //     return redirect()->back()->with('error', 'User not found.');
        // }

        // if(Hash::check($request->password, $user->password)) {
        //     Auth::login($user);
        //     return redirect()->route('commuter.dashboard')->with('success','Logged in Successfully!');
        // }
        // return back()->with('error', 'Password does not match.');
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success','Successfully Logged out!');
    }

    public function emailVerification() {
        $userEmail = Auth::user()->email;
        Mail::to($userEmail)->send(new EmailVerification());
        return view('activate');
    }

}
