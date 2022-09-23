<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Helpers\TimezoneMapper;
use App\Models\Absensi_m;
use App\Models\Shift_m;
    use App\Models\Sos_m;
use App\Models\User_m;
use DateTime;
    use DateTimeZone;

    class Sos_c extends Controller{
        public $path;

        public function __construct(){
            $this->path = storage_path('app/public/images');
        }

        public function index(){
            
        }

        public function getDataSos(Request $request){
            $data = array(
                'id_company' => $request->get('id_company'),
                'id_cabang' => $request->get('id_cabang'),
                'id_departemen' => $request->get('id_departemen'),
                'id_karyawan' => $request->get('id_karyawan'),
                'id_karyawan_select' => $request->get('id_karyawan_select'),
                'range_tanggal_mulai' => $request->get('range_tanggal_mulai'),
                'range_tanggal_selesai' => $request->get('range_tanggal_selesai'),
                'month_year'    => $request->get('month_year'),
                'konteks'       => $request->get('konteks'),
                'id_sos' => $request->get('id_sos'),
                'level' => $request->get('level'),
                'limit' => $request->get('limit'),
                'offset' => $request->get('offset'),
            );
            return Sos_m::getDataSos($data);
        }

        public function add_sos(Request $request){
            $id_karyawan = $request->input('id_karyawan');
            $level_user = $request->input('level_user');
            $id_company = $request->input('id_company');
            $id_cabang = $request->input('id_cabang');
            $id_departemen = $request->input('id_departemen');
            $keterangan = $request->input('keterangan');
            $image = $request->input('image');
            $image_count = $request->input('image_count');
            $data_insert['id_karyawan'] = $id_karyawan;
            $data_insert['keterangan'] = $keterangan;
            $data_insert['id_company'] = $id_company;
            $data_insert['tanggal'] = date('Y-m-d');
            $id = Absensi_m::getId($id_company, 'data_sos');
            $data_insert['id'] = $id;
            $insert = Sos_m::pengajuan_sos($data_insert);
            for($i=1;$i<=$image_count;$i++){
                Sos_m::insert_file(
                    $id, 
                    Uploads_c::upload_file(
                        $request->input('image'.$i), 
                        "/sos/".env('NAME_APPLICATION')."/",
                        $id_company."/".date("Ym"),
                        $id_karyawan.date('YmdHis').$i.".jpg"
                    ), 
                    $id_company);
            }
            if($insert){
                //LIHAT LEVEL NYA
                //3 => 7 => 2 => 5 => 4
                // 2 => 5 => 4
                $list_penerima = [];
                if($level_user == '3'){
                    $get_spv =  User_m::get_data_user_by_id($id_karyawan);
                    $id_spv = $get_spv->supervisi;
                    if($id_spv != '' || $id_spv != NULL){
                        array_push($list_penerima, $id_spv);
                    }
                    // $id_departemen = $
                }
                $response = array(
                    'success' => true, 
                    'message' => 'SOS berhasil terkirim',
                );
            }else{
                $response = array(
                    'success' => false, 
                    'message' => 'SOS gagal terkirim',
                );
            }
            return response()->json($response,200);
        }
    }