<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Helpers\TimezoneMapper;
    use App\Models\Gaji_m;
use App\Models\Reumbursement_m;
use DateTime;
    use DateTimeZone;

    class Reumbursement_c extends Controller{
        public $path;

        public function __construct(){
            $this->path = storage_path('app/public/images');
        }

        public function index(){
            
        }


        public function getDataReumbursement(Request $request){
            $data['token_fcm'] = $request->get('token_fcm');
            return Reumbursement_m::getDataReumbursement($data);
        }
        public function getDataGaji(Request $request){
            $token_fcm = $request->get('token_fcm');
            $month_year = $request->get('month_year');
            return Gaji_m::getDataGaji($token_fcm, $month_year);
        }
    }