<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use App\Http\Helpers\DateFormat;
    use App\Http\Helpers\Users;
    use App\Http\Controllers\Uploads_c;

    class Berita_m extends Model{

        public static function getdataBerita($data = array()){
            $id_berita          = $data['id_berita'];
            $id_company         = $data['id_company'];
            $keywords           = $data['keywords'];
            $limit              = $data['limit'];
            $offset             = $data['offset'];
            $range_tanggal_mulai     = $data['range_tanggal_mulai'];
            $range_tanggal_selesai   = $data['range_tanggal_selesai'];
            $where = array();
            if($id_company!=null) $where[] = array('db.id_company', '=', $id_company);
            if($id_berita!=null) $where[] = array('id_berita', '=', $id_berita);
            /*if($range_tanggal_mulai!=null && $range_tanggal_selesai!=null) {
                 $where[] = array('date_created', '>=', $range_tanggal_mulai);
                 $where[] = array('date_created', '<=', $range_tanggal_selesai);
            }*/
            if($keywords!=null) $where[] = array('judul', 'like', '%'.$keywords.'%');
            $data_berita = DB::table('data_berita as db')->select('db.*', 'dk.nama_lengkap as nama_pembuat')
            ->where($where)->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'db.creator')
            ->orderBy('date_created', 'DESC')->limit($limit)->offset($offset);
            if($range_tanggal_mulai!=null && $range_tanggal_selesai!=null) {
                $data_berita = $data_berita->whereRaw("DATE_FORMAT(date_created, '%Y-%m-%d') >= '$range_tanggal_mulai'");
                $data_berita = $data_berita->whereRaw("DATE_FORMAT(date_created, '%Y-%m-%d') <= '$range_tanggal_selesai'");
            }
            $data_berita = $data_berita->get();
            foreach ($data_berita as $row) {
                $row->tanggal_created = DateFormat::format($row->date_created,'N d M Y');
                $row->waktu_created = date_format(date_create($row->date_created), "H:i");
                $row->gambar = Uploads_c::cekFoto($row->gambar)?Uploads_c::retrieve_file_url($row->gambar, 'file'):'-';
            }
            if (count($data_berita)>0) 
                $response = array('success' => true, 'message' => 'data berita berhasil ditemukan', 'data' => $data_berita);
            else 
                $response = array('success'=>false,'message'=> 'data berita gagal ditemukan');
            return response()->json($response,200);
        }
    }

    