<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\Pengumuman_m;

    class Pengumuman_c extends Controller{

        public function index(){
            
        }

        public function getDataPengumuman(Request $request){
            $data = array(
                "id_pengumuman"         => $request->get('id_pengumuman'),
                "id_company"            => $request->get('id_company'),
                "id_departemen"         => $request->get('id_departemen'),
                "id_cabang"             => $request->get('id_cabang'),
                "id_karyawan"           => $request->get('id_karyawan'),
                "range_tanggal_mulai"   => $request->get('range_tanggal_mulai'),
                "range_tanggal_selesai" => $request->get('range_tanggal_selesai'),
                "limit"                 => $request->get('limit'),
                "offset"                => $request->get('offset'),
                "keywords"              => $request->get('keywords'),
            );

            return Pengumuman_m::getDataPengumuman($data);
        }
    }