<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\Berita_m;

    class Berita_c extends Controller{

        public function index(){
            
        }

        public function getDataBerita(Request $request){
            $data = array(
                "id_berita"            => $request->get('id_berita'),
                "id_company"            => $request->get('id_company'),
                "id_company"            => $request->get('id_company'),
                "range_tanggal_mulai"   => $request->get('range_tanggal_mulai'),
                "range_tanggal_selesai" => $request->get('range_tanggal_selesai'),
                "limit"                 => $request->get('limit'),
                "offset"                => $request->get('offset'),
                "keywords"              => $request->get('keywords'),
            );

            return Berita_m::getDataBerita($data);
        }
    }