<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use h1g\Proxmox\ProxmoxFacade;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use ProxmoxVE\Exception\AuthenticationException;
use ProxmoxVE\Proxmox;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        $domains = \Proxmox::get('access/domains');

        foreach ($domains['data'] as $d => $data) {
            if (empty($data['comment'])) {
                $data['comment'] = $data['realm'];
            }
            $realms[$data['realm']] = $data['comment'];
        }

        return view('auth.login', compact('realms'));
    }

    public function login(Request $request)
    {
        $realm = $request->get('realm');
        $username = $request->get('username');
        $password = $request->get('password');

        $data = ['hostname' => config('proxmox.server.hostname'), 'username' => $username, 'password' => $password, 'realm' => $realm];

        try {
            $proxmox = new Proxmox($data);

            $request->session()->put('loggedin', true);

            return redirect('/');
        } catch (AuthenticationException $e) {
            return back()->withErrors(['Username, Password or Realm failed']);
        }
    }
}
