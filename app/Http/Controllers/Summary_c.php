<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Helpers\TimezoneMapper;
    use App\Models\Summary_m;
    use DateTime;
    use DateTimeZone;

    class Summary_c extends Controller{
        public $path;

        public function __construct(){
            $this->path = storage_path('app/public/images');
        }

        public function index(){
            
        }


        public function getDataSummary(Request $request){
            $id_karyawan = $request->get('id_karyawan');
            $month_year = $request->get('month_year');

            return Summary_m::getDataSummary($id_karyawan, $month_year);
        }
    }