<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use App\Http\Controllers\Uploads_c;
    class Sos_m extends Model{


        public static function pengajuan_sos($data = array()){
            $insert = DB::table('data_sos')->insert($data);
            return $insert;
        }

        public static function insert_file($id_sos, $alamat_foto, $id_company){
            $insert = DB::table('file_sos')->insert(['id_sos' => $id_sos, 'file' => $alamat_foto, 'id_company' => $id_company]);
            return $insert;
        }

        public static function getDataSos($data = array()){
            $id_company             = $data['id_company'];
            $id_karyawan            = $data['id_karyawan'];
            $id_karyawan_select     = $data['id_karyawan_select'];
            $id_cabang              = $data['id_cabang'];
            $id_departemen          = $data['id_departemen'];
            $level_user             = $data['level'];
            $limit                  = $data['limit'];
            $offset                 = $data['offset'];
            $konteks                = $data['konteks'];
            $id_sos                 = $data['id_sos'];
            $month_year             = $data['month_year'];
            $range_tanggal_mulai    = $data['range_tanggal_mulai'];
            $range_tanggal_selesai  = $data['range_tanggal_selesai'];
            DB::enableQueryLog();
            $data_sos = DB::table('data_sos as DS')
            ->select('DS.*', 'DK.nama_lengkap')
            ->join('data_karyawan as DK', 'DS.id_karyawan', '=', 'DK.id_karyawan')
            ->join('master_jabatan as MJ', 'MJ.id_jabatan', '=', 'DK.id_jabatan')
            ->join('usergroup as UG', 'UG.id', '=', 'MJ.level_user')
            ->orderBy('tgl_input', 'desc')
            ->limit($limit)->offset($offset)
            ->where('DK.id_company', $id_company);
            

            //KONTEKS PENGAJUAN, REKAP, ATAU KARYAWAN
            if($konteks=='dataSaya') $where = [array('DS.id_karyawan', '=', $id_karyawan)];
            else if($konteks=='dataPegawai'){
                    $where = array();
                    $data_sos = $data_sos->where('UG.urutan', '>',  User_m::get_data_user_by_id($id_karyawan)->urutan)
                        ->where('DK.id_karyawan', '!=', $id_karyawan);
                    if($level_user=='1') {
                        // $where[] = array('approval_hrd', '<>', '5');
                        // $where[] = array('approval_hrd', '<>', '0');
                        // $data_izin = $data_izin->where(function ($query) {
                        //     $query = $query->whereNotIn('approval_hrd', ['0'])
                        //         ->orWhere('DI.status', '4');  
                        // });
                    }
                    else if($level_user=='2') {
                       
                        $p_kedep = User_m::getPKedep($id_karyawan);
                        if (count($p_kedep)==0) {//HANYA MENJABAT 1 KEPALA DEPARTEMEN
                            $where[] = array('DK.id_departemen', '=', User_m::get_data_karyawan($id_karyawan)->id_departemen);
                        }
                        else{//BISA MENJABAT LEBIH DARI 1 KEPALA DEPARTEMEN
                            $list_id_departemen = array();
                            foreach ($p_kedep as $row_kedep) {
                                $list_id_departemen[] = $row_kedep->id_departemen;   
                            } 
                            $data_izin = $data_sos->whereIn('DK.id_departemen', $list_id_departemen); 
                        }
                    }
                    else if($level_user=='4') {
                        // $where[] = array('approval_direksi', '<>', '5');
                        // $where[] = array('approval_direksi', '<>', '0');
                        // $data_izin = $data_izin->where(function ($query) {
                        //     $query = $query->whereNotIn('approval_direksi', ['5','0'])
                        //         ->orWhere('DI.status', '4');  
                        // });
                    }
                    else if($level_user=='5') {
                        // $where[] = array('approval_kacab', '<>', '5');
                        // $where[] = array('approval_kacab', '<>', '0');
                        $p_kacab = User_m::getPKacab($id_karyawan);
                        if (count($p_kacab)==0) {//HANYA MENJABAT 1 KEPALA CABANG
                            $where[] = array('DK.id_cabang', '=', User_m::get_data_karyawan($id_karyawan)->id_cabang);
                        }
                        else{//BISA MENJABAT LEBIH DARI 1 KEPALA CABANG
                            $list_id_cabang = array();
                            foreach ($p_kacab as $row_kacab) {
                                $list_id_cabang[] = $row_kacab->id_cabang;   
                            } 
                            $data_sos = $data_sos->whereIn('DK.id_cabang', $list_id_cabang);
                        }
                    }
                    else if($level_user=='7') {
                        // $where[] = array('approval_spv', '<>', '5');
                        // $where[] = array('approval_spv', '<>', '0');
                        $where[] = array('DK.supervisi', '=', $id_karyawan);
                    }
            }
            else if($konteks=='dataDetail') $where[] = array('DS.id', '=', $id_sos);
            //
            if($id_cabang!=null) $where[] = array('DK.id_cabang', '=', $id_cabang);
            if($id_departemen!=null) $where[] = array('DK.id_departemen', '=', $id_departemen);
            if($id_karyawan_select!=null) $where[] = array('DK.id_karyawan', '=', $id_karyawan_select);
            if($month_year!=null) $data_izin = $data_izin->where(DB::raw("DATE_FORMAT(DS.tgl_input,'%Y-%m')"), $month_year);

            if($range_tanggal_mulai!=null && $range_tanggal_selesai!=null) {
                 $where[] = array('DS.tanggal', '>=', $range_tanggal_mulai);
                 $where[] = array('DS.tanggal', '<=', $range_tanggal_selesai);
            }
            
            $data_sos = $data_sos->where($where)->get();
            // dd(DB::getQueryLog());exit;

            $respon = array();
            foreach ($data_sos as $row) {
                    
                $respon[] = array(
                    'id_sos'           => $row->id,
                    'id_karyawan'       => $row->id_karyawan,
                    'tgl_pengajuan'     => date_format(date_create($row->tanggal), "d-m-Y"),
                    'keterangan'         => $row->keterangan,
                    'nama'              => $row->nama_lengkap,
                    'list_image'        => self::getListImage($row->id),
                );   
            }

            if (count($data_sos)>0) 
                $response = array('success' => true, 'message' => 'data sos berhasil ditemukan', 'data_sos' => $respon);
            else 
                $response = array('success'=>false,'message'=> 'data sos gagal ditemukan');
            return response()->json($response,200);
        }
        public static function getListImage($id_sos){
            $data =  DB::table('file_sos')->select('file')->where('id_sos', $id_sos)->get();
            $path_foto = "absensi/".env('NAME_APPLICATION')."/";
            foreach($data as $rows){
                // $rows->file = Convertion::cekFoto($rows->file)?url('../web').$rows->file:'-';
                $rows->file = ($rows->file == ''?'-':(Uploads_c::cekFoto($path_foto.$rows->file)?Uploads_c::retrieve_file_url($rows->file, 'file'):'-'));
            }
            return $data;
        }
    }