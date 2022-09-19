<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use URL;
    use App\Http\Helpers\DateFormat;
    use App\Http\Helpers\Convertion;
    use App\Http\Controllers\Uploads_c;

    class Aktivitas_m extends Model{

        /*public static function addAktivitas($data = null){

            $insert_absen = DB::table('data_aktivitas')->insert($data);

            if($insert_absen){
                $response = array('success'=>true,
                                    'message'=>'Anda berhasil menambah aktivitas');

                $json = response()->json($response,200);
            }else{
                $response = array('success'=>false,
                                    'message'=>'Anda gagal menambahkan aktivitas');

                $json = response()->json($response,401);
            }

            return $json;
        }

        public static function getId($id_company = null){
            $curent_date = date('Ymd');

            $select = collect(DB::select("SELECT MAX(id_aktivitas) AS id
                                        FROM data_aktivitas
                                        WHERE id_company = '$id_company'
                                        AND SUBSTR(id_aktivitas,10,6) = DATE_FORMAT(CURRENT_DATE(),'%Y%m')"))->first();
            
            if(!empty($select->id)){
                $maxid = substr($select->id,-5);
                $nextid = $id_company.$curent_date.sprintf("%05d", ($maxid+1));
            }else{
                $nextid = $id_company.$curent_date.'00001';
            }

            return $nextid;
            
        }

        public static function dataAktivitas($id_company=null, $id_karyawan=null, $filter_bulan=null, $limit_start=null, $limit_end=null){
            $data_aktivitas = DB::select("SELECT data_karyawan.nama AS nama_karyawan,
                                                    data_aktivitas.waktu,
                                                    data_aktivitas.latitude,
                                                    data_aktivitas.longitude,
                                                    data_aktivitas.keterangan,
                                                    data_aktivitas.image
                                            FROM data_aktivitas
                                            LEFT JOIN data_karyawan ON data_karyawan.id_karyawan = data_aktivitas.id_karyawan
                                            WHERE data_aktivitas.id_company = '$id_company'
                                            AND data_aktivitas.id_karyawan = '$id_karyawan'
                                            AND DATE_FORMAT(data_aktivitas.waktu,'%Y-%m') = '$filter_bulan'
                                            ORDER BY waktu DESC
                                            LIMIT $limit_start,$limit_end");

            $total_data = collect(DB::select("SELECT id_aktivitas
                                            FROM data_aktivitas
                                            WHERE id_company = '$id_company'
                                            AND id_karyawan = '$id_karyawan'
                                            AND DATE_FORMAT(waktu,'%Y-%m') = '$filter_bulan'"))->count();
            
            if(count($data_aktivitas) > 0){

                $data = array();
                foreach($data_aktivitas as $rows){
                    $data[] = array("nama_karyawan" => $rows->nama_karyawan,
                                    "waktu" => $rows->waktu,
                                    "latitude" => $rows->latitude,
                                    "longitude" => $rows->longitude,
                                    "keterangan" => $rows->keterangan,
                                    "image" => URL::to($rows->image));
                }

                if(count($data) > 0){
                    $response = array('success'=>true,
                                    'data'=>array('jml_semua_data' => $total_data,
                                                'jml_tampil' => ($limit_start + count($data_aktivitas)),
                                                'list_data' => $data));
                    $json = response()->json($response,200);
                }else{
                    $response = array('success'=>false,
                                    'message'=>'Tidak ada data untuk ditampilkan');
                    $json = response()->json($response,401);
                }

            }else{
                $response = array('success'=>false,
                                    'message'=>'Tidak ada data untuk ditampilkan');
                $json = response()->json($response,401);
            }

            return $json;
        }*/

         public static function addAktivitas($data=null){
            $insert_lembur = DB::table('data_aktivitas')->insert($data);
            if($insert_lembur){
                $response = array(
                    'success'=>true,
                    'message'=>'Anda berhasil melakukan absen aktivitas');
            }else{
                $response = array(
                    'success'=>false,
                    'message'=>'Anda gagal melakukan absen aktivitas, silahkan dicoba lagi');
            }
            return response()->json($response,200);
        }

        public static function getDataAktivitas($data = array()){
            $id_aktivitas      = $data['id_aktivitas'];
            $id_karyawan        = $data['id_karyawan'];
            $id_karyawan_select = $data['id_karyawan_select'];
            $id_departemen = $data['id_departemen'];
            $id_cabang     = $data['id_cabang'];
            $id_company    = $data['id_company'];
            $limit         = $data['limit'];
            $offset        = $data['offset'];
            $konteks       = $data['konteks'];
            $month_year         = $data['month_year'];
            $range_tanggal_mulai    = $data['range_tanggal_mulai'];
            $range_tanggal_selesai  = $data['range_tanggal_selesai'];

            $where = array();
            //FILTER
            if($id_karyawan_select!=null) $where[] = array('dk.id_karyawan', '=', $id_karyawan_select);
            if($id_cabang!=null) $where[] = array('dk.id_cabang', '=', $id_cabang);
            if($id_company!=null) $where[] = array('dk.id_company', '=', $id_company);
            if($id_departemen!=null) $where[] = array('dk.id_departemen', '=', $id_departemen);
            if($month_year!=null) $where[] = array('da.waktu', 'like', '%'.$month_year.'%');
            //
            DB::enableQueryLog();
            $data_aktivitas = DB::table('data_aktivitas as da')->select('da.*', 'md.nama as nama_departemen', 'mc.nama as nama_cabang', 'dk.nama_lengkap as nama_karyawan')
                ->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'da.id_karyawan')
                ->leftJoin('master_departemen as md', 'md.id_departemen', '=', 'dk.id_departemen')
                ->leftJoin('master_cabang as mc', 'mc.id_cabang', '=', 'dk.id_cabang')
                ->leftJoin('master_jabatan as mj', 'mj.id_jabatan', '=','dk.id_jabatan')
                ->join('usergroup as ug', 'ug.id', '=', 'mj.level_user')
                ->limit($limit)->offset($offset)
                ->orderBy('waktu', 'desc')
                ->where($where);

             if($range_tanggal_mulai!=null && $range_tanggal_selesai!=null) {
                $data_aktivitas = $data_aktivitas->whereRaw("DATE_FORMAT(da.waktu, '%Y-%m-%d') >= '$range_tanggal_mulai'");
                $data_aktivitas = $data_aktivitas->whereRaw("DATE_FORMAT(da.waktu, '%Y-%m-%d') <= '$range_tanggal_selesai'");
            }

            if($konteks=='aktivitasSaya') $data_aktivitas = $data_aktivitas->where('dk.id_karyawan', $id_karyawan);
            else if($konteks=='detailAktivitas') $data_aktivitas = $data_aktivitas->where('da.id_aktivitas', $id_aktivitas);
            else if($konteks=='aktivitasPegawai'){//JIKA REKAP PEGAWAI
                if($data['level_user']=='7'){//JIKA SPV REQUEST
                    $data_aktivitas = $data_aktivitas->whereIn('ug.id',['3']); //Staff
                    $data_aktivitas = $data_aktivitas->where('dk.supervisi', $id_karyawan);
                }
                else if($data['level_user']=='2'){//JIKA KEDEP REQUEST
                    $data_aktivitas = $data_aktivitas->whereIn('ug.id',['7','3']); //SPV dan Staff
                    $data_aktivitas = $data_aktivitas->where('dk.id_departemen', self::getDataKaryawan($id_karyawan)->id_departemen);
                }
                else if($data['level_user']=='5'){//JIKA KACAB REQUEST
                    $data_aktivitas = $data_aktivitas->whereIn('ug.id',['7','3','2']); //Kedep, SPV dan Staff
                    $data_aktivitas = $data_aktivitas->where('dk.id_cabang', self::getDataKaryawan($id_karyawan)->id_cabang);
                }
                else if($data['level_user']=='4'){//JIKA DIREKSI REQUEST
                    $data_aktivitas = $data_aktivitas->whereIn('ug.id',['5','2','7','3']); //Kacab, Kedep, SPV dan Staff
                    $data_aktivitas = $data_aktivitas->where('dk.id_company', self::getDataKaryawan($id_karyawan)->id_company);
                }
            }
            $data_aktivitas = $data_aktivitas->get();
            // dd(DB::getQueryLog());exit;
            $path_foto = "absensi/".env('NAME_APPLICATION')."/";
            foreach ($data_aktivitas as $row) {
                $row->tanggal = DateFormat::format($row->waktu,'N d M Y');
                $row->nama_departemen = $row->nama_departemen??'';
                $row->nama_cabang = $row->nama_cabang??'';
                $row->waktu = date_format(date_create($row->waktu), "H:i");
                $row->image = ($row->image == ''?'-':(Uploads_c::cekFoto($path_foto.$row->image)?Uploads_c::retrieve_file_url($row->image, 'photo'):'-'));
            }
            if (count($data_aktivitas)>0) 
                $response = array('success' => true, 'message' => 'data aktivitas berhasil ditemukan', 'data' => $data_aktivitas);
            else 
                $response = array('success'=>false,'message'=> 'data aktivitas gagal ditemukan');
            return response()->json($response,200);
        }

        public static function getDataKaryawan($id_karyawan){
            return DB::table('data_karyawan')->select('id_company', 'id_departemen', 'id_cabang')->where('id_karyawan', $id_karyawan)->first();
        }

         public static function get_id_aktivitas($id_company){
            $nomor_urut_terakhir = DB::table('data_aktivitas')
                                    ->where('id_company', '=', $id_company)
                                    ->where('tgl_input', 'like', '%'.date('Y-m-d').'%')
                                    ->count();
            return $id_company.date('Ymd').sprintf("%04d", $nomor_urut_terakhir+1);
        }

        public static function cekLokasi($data = array()){

            
            $lokasi_kantor = DB::table('data_lokasi_kantor as dlk')->select('nama_kantor', 'lat_asli', 'id_lokasi_kantor', 'lat_min', 'lat_max', 'long_asli', 'long_min', 'long_max')->join('data_karyawan as dk', 'dk.id_cabang', '=', 'dlk.id_cabang')->where(['dlk.id_company' => $data['id_company'], 'dk.id_karyawan' => $data['id_karyawan']])->get();

            $lokasi = false;
            if(count($lokasi_kantor) > 0){
                foreach($lokasi_kantor as $rows){
                    $lat_min_kantor = $rows->lat_min;
                    $lat_max_kantor = $rows->lat_max;
                    $long_min_kantor = $rows->long_min;
                    $long_max_kantor = $rows->long_max;
                    $nama_lokasi = $rows->nama_kantor;
                    $id_lokasi = $rows->id_lokasi_kantor;
                    if($lokasi == false){
                        if(($data['lat_absen'] >= $lat_min_kantor && $data['long_absen'] <= $long_min_kantor) && ($data['lat_absen'] <= $lat_max_kantor && $data['long_absen'] >= $long_max_kantor)){
                            $lokasi = true;
                            $id_lokasi_absen = $id_lokasi;
                            $lokasi_absen = $nama_lokasi;
                        }
                    }                    
                }
                if($lokasi){
                    $response_lokasi = array(
                        'success'=>true,
                        'id_lok' => $id_lokasi_absen,
                        'nama_kantor'=>$lokasi_absen);
                }else{
                    $response_lokasi = array('success'=>true,
                                    'nama_kantor'=>'Anda berada di luar radius kantor');
                }

            }else{
                $response_lokasi = array('success'=>false,
                                    'nama_kantor'=>'Lokasi kantor belum disetting');

                
                // return $json;
            }

            

            return $json = response()->json(array(
                'data_lokasi' => $response_lokasi,
                'tanggal_hari_ini' => DateFormat::format($data['tanggal'],'N d M Y'),
            ));
        }
    }