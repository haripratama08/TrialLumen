<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Helpers\TimezoneMapper;
    use App\Models\Absensi_m;
    use App\Models\Patroli_m;

    class Patroli_c extends Controller{
        public $path;

        public function __construct(){
            $this->path = storage_path('app/public/images');
        }

        public function index(){
            
        }

        public function getDataPatroli(Request $request){
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
                'id_patroli' => $request->get('id_patroli'),
                'level' => $request->get('level'),
                'limit' => $request->get('limit'),
                'offset' => $request->get('offset'),
            );
            return Patroli_m::getDataPatroli($data);
        }

        public function add_patroli(Request $request){
            $id_karyawan    = $request->input('id_karyawan');
            $id_company     = $request->input('id_company');
            $keterangan     = $request->input('keterangan');
            $image          = $request->input('image');
            $nama_lokasi    = $request->input('nama_lokasi');
           
            $id = Absensi_m::getId($id_company, 'data_patroli');
            $data_insert = array(
                'id_patroli'   => $id,
                'id_karyawan'   => $id_karyawan,
                'nama_lokasi'   => $nama_lokasi,
                'keterangan'    => $keterangan,
                'id_company'    => $id_company, 
                'tanggal'    => date('Y-m-d'), 
            );
            if($image != null || $image != ''){
                $data_insert['image'] = Uploads_c::upload_file(
                    $image,
                    "/absensi/".env('NAME_APPLICATION')."/",
                    $id_company."/patroli/".date("Ym"),
                    $id_karyawan.date('YmdHis').".jpg"
                );
            }
            $insert = Patroli_m::pengajuan_patroli($data_insert);
            if($insert){
                $response = array(
                    'success' => true, 
                    'message' => 'Patroli berhasil terkirim',
                );
            }else{
                $response = array(
                    'success' => false, 
                    'message' => 'Patroli gagal terkirim',
                );
            }
            return response()->json($response,200);
        }

        public function list_kartu(Request $request){
            $id_karyawan    = $request->get('id_karyawan');
            $id_company     = $request->get('id_company');
            return Patroli_m::getListKartu($id_karyawan, $id_company);
        }
    }