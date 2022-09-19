<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Helpers\TimezoneMapper;
    use App\Models\Shift_m;
    use DateTime;
    use DateTimeZone;

    class Shift_c extends Controller{
        public $path;

        public function __construct(){
            $this->path = storage_path('app/public/images');
        }

        public function index(){
            
        }

        public function getDataShift(Request $request){
            $data = array(
                'id_company' => $request->get('id_company'),
                'id_cabang' => $request->get('id_cabang'),
                'id_departemen' => $request->get('id_departemen'),
                'id_karyawan' => $request->get('id_karyawan'),
                'range_tanggal_mulai' => $request->get('range_tanggal_mulai'),
                'range_tanggal_selesai' => $request->get('range_tanggal_selesai'),
                'month_year'    => $request->get('month_year'),
                'konteks'       => $request->get('konteks'),
                'level' => $request->get('level'),
                'limit' => $request->get('limit'),
                'offset' => $request->get('offset'),
            );
            return Shift_m::getDataShift($data);
        }

        public function getDataShiftTeman(Request $request){
            $data = array(
                'id_master_shift' => $request->get('id_master_shift'),
                'id_company' => $request->get('id_company'),
                'id_cabang' => $request->get('id_cabang'),
                'id_departemen' => $request->get('id_departemen'),
                'id_karyawan' => $request->get('id_karyawan'),
                'limit' => $request->get('limit'),
                'offset' => $request->get('offset'),
                'level' => $request->get('level'),
                'tanggal' => $request->get('tanggal'),
            );
            return Shift_m::getDataShiftTeman($data);
        }

        public function getDataShiftKaryawan(Request $request){
            $data = array(
                'id_master_shift' => $request->get('id_master_shift'),
                'id_company' => $request->get('id_company'),
                'id_cabang' => $request->get('id_cabang'),
                'id_departemen' => $request->get('id_departemen'),
                'id_karyawan' => $request->get('id_karyawan'),
                'limit' => $request->get('limit'),
                'offset' => $request->get('offset'),
                'level' => $request->get('level'),
                'id_karyawan_select' => $request->get('id_karyawan_select'),
                'tanggal' => $request->get('tanggal'),
            );
            return Shift_m::getDataShiftKaryawan($data);
        }
    }