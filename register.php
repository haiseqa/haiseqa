<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helper\authUser;
use App\Database\tbusers;
use App\Database\tbplan;
use App\Database\tbotp;
use App\Helper\otp;
use App\Helper\plan;
use App\Helper\transaksi;
use Hash;
use Carbon\Carbon;

class registerController extends Controller
{
    //
    function index(Request $req){
        if(authUser::isLogin()){
            return redirect()->route('home');
        }

        return view('Auth.register', [
            'plan'  => tbplan::where('nama', '<>', 'Admin')
            ->orderBy('harga', 'asc')
            ->get()
        ]);
    }

    function register_post(Request $req){
        $this->validate($req, [
            'nama'      => 'required|max:100',
            'email'     => 'required|email',
            'username'  => 'required|alpha_num|max:100|regex:/^[A-Za-z0-9\/\-]+$/',
            'telepon'   =>  ,
            'otp'       => 'required|numeric',
            'password'  => 'required|same:password2',
            'password2' => 'required',
            'paket'     => 'required'
        ]);

        //retrive data from field
        $data = $req->all();

        //check telepon
        if(substr($data['telepon'], 0, 1) === '0'){
            return back()->with('message', 'Format Nomor Salah')->withInput();
        }

        //check exist plan
        if(!plan::checkPlan($data['paket'])){
            return back()->with('message', 'Paket Tidak Tersedia')->withInput();
        }

        //check otp code
        if(!otp::checkOtp($data['otp'], $data['email'])){
            return back()->with('message', 'Code OTP tidak Valid')->withInput();
        }

        //check user
        if(authUser::checkUser($data['email'], $data['username'])){
            return back()->with('message', 'Email Atau Username Sudah Pernah Digunakan')->withInput();
        }

        //create user
        $user = authUser::createUser($data);
        if($user === "FAILED"){
            return back()->with('message', 'Register Failed')->withInput();
        }

        //get plan
        $plan = plan::getPlan($data['paket']);

        transaksi::createTransaksi(plan::getPlanByName('Bronze'), $user);

        if($plan->nama !== 'Bronze'){
            transaksi::createTransaksi($plan, $user);
        }

        return redirect()->Route('login')->with('message', 'Register Berhasil');
    }

    function register_otp(Request $req){
        $this->validate($req, [
            'email'   => 'required|email'
        ]);

        if(otp::createOtp($req->input('email'))){
            return json_encode(array(
                'status'    => 1,
                'message'   => 'Kode OTP Berhasil Terkirim'
            ));
        }
        else{
            return json_encode(array(
                'status'    => 0,
                'message'   => 'Kode OTP Gagal Terkirim'
            ));
        }
    }
}