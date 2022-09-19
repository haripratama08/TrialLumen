<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Helpers\TimezoneMapper;
    use App\Models\Gaji_m;
    use DateTime;
    use DateTimeZone;

    class Gaji_c extends Controller{
        public $path;

        public function __construct(){
            $this->path = storage_path('app/public/images');
        }

        public function index(){
            
        }



        public function getDataGaji(Request $request){
            $token_fcm = $request->get('token_fcm');
            $id_karyawan = $request->get('id_karyawan');
            $id_company = $request->get('id_company');
            $month_year = $request->get('month_year');
            return Gaji_m::getDataGaji($id_karyawan, $month_year);
        }
    }