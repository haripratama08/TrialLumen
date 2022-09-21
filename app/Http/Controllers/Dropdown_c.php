<?php

    namespace App\Http\Controllers;
    use Illuminate\Http\Request;
    use App\Http\Helpers\TimezoneMapper;
    use App\Models\Dropdown_m;
    use DateTime;
    use DateTimeZone;

    class Dropdown_c extends Controller{
        public $path;

        public function __construct()
        {
            //DEFINISIKAN PATH
            $this->path = storage_path('app/public/images');
        }

        public function index(){
            
        }

        public function get_data_cabang(Request $request){
            return Dropdown_m::get_data_cabang($request->input('id_company'), $request->input('id_karyawan'), $request->input('level_user'));
        }
        public function get_data_departemen(Request $request){
            return Dropdown_m::get_data_departemen($request->input('id_cabang'), $request->input('id_karyawan'), $request->input('level_user'));
        }
        public function get_data_pegawai(Request $request){
            return Dropdown_m::get_data_pegawai(
                $request->input('id_company'),
                $request->input('id_departemen'),
                $request->input('id_cabang'),
                $request->input('id_karyawan'),
                $request->input('level_user'),
                $request->input('keywords')
            );
        }
        public function get_data_status(Request $request){
            return Dropdown_m::get_data_status($request->input('level_user'), $request->input('id_company'));
        }

        public function get_chip(Request $request){
            return Dropdown_m::get_chip(
                $request->input('id_company') 
            );
        }

    }