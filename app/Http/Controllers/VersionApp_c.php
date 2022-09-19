<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\VersionApp_m;

    class VersionApp_c extends Controller{

        public function index(){
            
        }
        public function cek_version_apps(Request $request){
            $versi_mobile = $request->get('versi_mobile');
            $os = $request->get('os');
            return VersionApp_m::cek_version_apps($versi_mobile,$os);
        }

        public function cek_php(){
            echo phpinfo();
        }

    }