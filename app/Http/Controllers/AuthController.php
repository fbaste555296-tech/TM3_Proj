<?php
namespace App\Http\Controllers;
use App\Models\User; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth; use Illuminate\Support\Facades\Hash;
class AuthController extends Controller {
 public function create(){return view('auth.login');}
 public function adminLogin(){return view('auth.login',['adminLogin'=>true]);}
 public function store(Request $request){$data=$request->validate(['email'=>'required|email','password'=>'required']); if(!Auth::attempt($data,$request->boolean('remember'))){return back()->withErrors(['email'=>'The supplied credentials do not match our records.'])->onlyInput('email');} $request->session()->regenerate(); return redirect()->intended(Auth::user()->isManagement() ? route('dashboard') : route('home'));}
 public function registerForm(){return view('auth.register');}
 public function register(Request $request){$data=$request->validate(['name'=>'required|string|max:255','email'=>'required|email|unique:users','phone'=>'required|string|max:30','password'=>'required|confirmed|min:8']); $user=User::create([...$data,'role'=>'customer','password'=>Hash::make($data['password'])]); Auth::login($user); return redirect()->route('appointments.create');}
 public function destroy(Request $request){Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('login');}
}
