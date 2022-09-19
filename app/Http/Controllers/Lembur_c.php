<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Helpers\Convertion;
    use App\Models\Lembur_m;
    use App\Http\Helpers\TimezoneMapper;
    use App\Models\Absensi_m;
    use App\Http\Controllers\Notifikasi_c;
    use App\Http\Controllers\Uploads_c;
    use DateTime;
    use DateTimeZone;
    use App\Models\User_m;
    use Illuminate\Support\Facades\DB;

    class Lembur_c extends Controller{

        public function __construct()
        {
            //DEFINISIKAN PATH
            $this->path = storage_path('app/public/images');
            $this->column_setting_approval = [
                array(
                    'column_approval' => 'approval_spv',
                    'column_user_approval' => 'user_spv',
                    'column_tgl_approval' => 'tgl_apv_spv',
                    'level_user'          => '7'
                ), 
                array(
                    'column_approval' => 'approval_kedep',
                    'column_user_approval' => 'user_kedep',
                    'column_tgl_approval' => 'tgl_apv_kedep',
                    'level_user'          => '2'
                ), 
                array(
                    'column_approval' => 'approval_kacab',
                    'column_user_approval' => 'user_kacab',
                    'column_tgl_approval' => 'tgl_apv_kacab',
                    'level_user'          => '5'
                ),  
                array(
                    'column_approval' => 'approval_direksi',
                    'column_user_approval' => 'user_direksi',
                    'column_tgl_approval' => 'tgl_apv_direksi',
                    'level_user'          => '4'
                ), 
                array(
                    'column_approval' => 'approval_hrd',
                    'column_user_approval' => 'user_hrd',
                    'column_tgl_approval' => 'tgl_apv_hrd',
                    'level_user'          => '1'
                ),
            ];
        }


        public function index(){
            
        }

        public function dataLemburByKaryawan(Request $request){
            $id_company = $request->input('id_company');
            $id_karyawan = $request->input('id_karyawan');
            $filter_bulan = $request->input('filter_bulan');
            $limit_start = $request->input('limit_start');
            $limit_end = $limit_start + 10;
            return Lembur_m::dataLemburByKaryawan($id_company, $id_karyawan, $filter_bulan, $limit_start, $limit_end);
        }

        public function dataLemburByDepartemen(Request $request){
            $id_company = $request->input('id_company');
            $id_karyawan = $request->input('id_karyawan');
            $level_user = $request->input('level_user');
            $filter_karyawan = $request->input('filter_karyawan');
            $filter_bulan = $request->input('filter_bulan');
            $filter_date = $request->input('filter_tanggal');
            $limit_start = $request->input('limit_start');
            $limit_end = $limit_start + 10;

            $filter_departemen = Convertion::idDepartemen($id_company, $id_karyawan);
            return Lembur_m::dataLemburByDepartemen($id_company, $level_user, $filter_departemen, $filter_karyawan, $filter_bulan, $filter_date, $limit_start, $limit_end);
        }

        public function cekAbsen(Request $request){
            $id_company = $request->get('id_company');
            $id_karyawan = $request->get('id_karyawan');
            $lat = $request->get('latitude');
            $long = $request->get('longitude');
            $get_timezone = TimezoneMapper::latLngToTimezoneString($lat, $long);
            $timezone = new DateTimeZone($get_timezone);
            $date = new DateTime();
            $date->setTimeZone($timezone);
            $current_date = $date->format('Y-m-d');
            return Lembur_m::cekAbsen(array('id_company' => $id_company, 'id_karyawan' => $id_karyawan, 'lat_absen' => $lat, 'long_absen' => $long, 'tanggal' => $current_date));
        }

        public function add_lembur(Request $request){
            $id_company = $request->input('id_company');
            $id_karyawan = $request->input('id_karyawan');
            $id_cabang = $request->input('id_cabang');
            $id_departemen = $request->input('id_departemen');
            $level_user = $request->input('level_user');
            $jenis_lembur = $request->input('jenis_lembur');
            $lat = $request->input('latitude');
            $long = $request->input('longitude');
            $image = $request->input('image');

            DB::beginTransaction();
            try{
                if($id_cabang == '' || $id_cabang == NULL){
                    $id_cabang = DB::table('data_karyawan')
                                ->select('id_cabang')
                                ->where(
                                    [
                                        'id_karyawan' => $id_karyawan, 
                                        'id_company' => $id_company,
                                    ]
                                )->first()->id_cabang;
                }
                
                $get_timezone = TimezoneMapper::latLngToTimezoneString($lat, $long);
                $timezone = new DateTimeZone($get_timezone);
                $date = new DateTime();
                $date->setTimeZone($timezone);
                $gmt = $date->format('P');
    
                $current_date = $date->format('Y-m-d');
                $jam_absen = $date->format('Y-m-d H:i:s');
    
                $data_insert = array(
                    'id_karyawan'   => $id_karyawan,
                    'tgl_absen'     => $current_date,
                    'jam_absen'     => $jam_absen,
                    'lokasi_absen'  => $request->input('lokasi_absen'),
                    'timezone'      => $get_timezone,
                    'gmt'           => $gmt,
                    'latitude'      => $lat,
                    'longitude'     => $long,
                    'keterangan'    => $request->input('keterangan'),
                    'id_company'    => $id_company, 
                    'foto'          => Uploads_c::upload_file(
                        $image,
                        "/absensi/".env('NAME_APPLICATION')."/",
                        $id_company."/".$jenis_lembur."/".date("Ym"),
                        $id_karyawan.date('YmdHis').".jpg"
                    ),
                );
    
                //MAPPING COLUMN APPROVAL DAN VALUE COLUMN APPROVAL
                
                $data_penerima_notifikasi = array();
                $data_p_approval = Lembur_m::get_p_approval($level_user, $id_company, $id_cabang);
    
                //CEK SPV 13 Agustus 2021
                $get_spv =  User_m::get_data_user_by_id($id_karyawan);
                $id_spv = $get_spv->supervisi;
                $exploded_data_p_approval = explode(',',$data_p_approval);
                $ada_supervisi = array_search("7",$exploded_data_p_approval);
                //
    
    
                if($data_p_approval==null && $level_user!='1'){
                    $response = array('success'=>false, 'message'=>'Pengaturan lembur belum dibuat, silahkan hubungi atasan Anda');
                    return response()->json($response,200);exit;
                } 
                else{
                    $data_master_jabatan = Lembur_m::get_master_jabatan($data_p_approval, $id_company);
                    if(count($data_master_jabatan)==0 && $level_user=='1' && $jenis_lembur=='lembur_mulai'){
                        $data_insert['approve'] = '4';
                    }
                    else if(empty($ada_supervisi)  && count($exploded_data_p_approval)==0){
                        //JIKA ADA SUPERVISI DIDALAM P APPROVAL DAN SPVNYA KOSONG DAN ISI P APPROVAL HANYA SPV
                        $response = array('success'=>false, 'message'=>'Pengaturan lembur belum dibuat, silahkan hubungi atasan Anda');
                        return response()->json($response,200);exit;
                    }
                    else{
                        $mapping = array();
                        foreach (explode(',',$data_p_approval) as $row) {
                            $detail_mapping = array();
                            if ($row=='1') {
                                //NOTIF KHUSUS 07062021
                               if($data_penerima_notifikasi==null){
                                Notifikasi_c::notifikasi_khusus($id_company, 'notif_hrd', 'Pengajuan Lembur', User_m::get_data_karyawan($id_karyawan)->nama_lengkap.' mengajukan lembur');
                                $data_penerima_notifikasi = User_m::get_data_user_by_where(
                                    [
                                        'usergroup.id' => $row,
                                        'users.id_company' => $id_company,
                                    ]
                                );
                               }  
                                $detail_mapping['column_approval'] = 'approval_hrd';
                            }
                            else if ($row=='2') {
                                if($data_penerima_notifikasi==null)
                                    $data_penerima_notifikasi = User_m::get_data_user_by_where(
                                        [
                                            'usergroup.id' => $row,
                                            'users.id_company' => $id_company,
                                            'data_karyawan.id_departemen' => $id_departemen,
                                        ]
                                    );
                                $detail_mapping['column_approval'] = 'approval_kedep';
                            }
                            else if ($row=='4') {
                                if($data_penerima_notifikasi==null)
                                    $data_penerima_notifikasi = User_m::get_data_user_by_where(
                                        [
                                            'usergroup.id' => $row,
                                            'users.id_company' => $id_company,
                                        ]
                                    );
                                $detail_mapping['column_approval'] = 'approval_direksi';
                            }
                            else if ($row=='5') {
                                if($data_penerima_notifikasi==null)
                                    $data_penerima_notifikasi = User_m::get_data_user_by_where(
                                        [
                                            'usergroup.id' => $row,
                                            'users.id_company' => $id_company,
                                            'data_karyawan.id_cabang' => $id_cabang,
                                        ]
                                    );
                                $detail_mapping['column_approval'] = 'approval_kacab';
                            }
                            else if ($row=='7') {
                                if($data_penerima_notifikasi==null && $id_spv!=null)
                                    $data_penerima_notifikasi = User_m::get_data_user_by_where(
                                        [
                                            'usergroup.id' => $row,
                                            'users.id_company' => $id_company,
                                            'data_karyawan.id_departemen' => $id_departemen,
                                            'data_karyawan.id_karyawan' => $id_spv 
                                        ]
                                    );
                                if($id_spv==null) continue;//SKIP SUPERVISI
                                $detail_mapping['column_approval'] = 'approval_spv';
                            }
                            $mapping[] = $detail_mapping;
                        }
                    }
                }
                
    
                if($jenis_lembur == 'lembur_mulai'){
                    $data_insert['id_lemmulai'] = Absensi_m::getId($id_company, 'lembur_mulai');
                    $index=0;
                    foreach ($mapping as $row) {
                        if($index==0) {
                            $data_insert[$row['column_approval']] = '1';
                        }
                        else {
                            $data_insert[$row['column_approval']] = '5';
                        }
                        $index++;
                    }
                    $insert_lembur = Lembur_m::add_lembur('lembur_mulai', $data_insert);
                    if($insert_lembur){
                        $response = array(
                            'success'=>true,
                            'message'=>'Anda berhasil melakukan absen lembur');
                    }else{
                        $response = array(
                            'success'=>false,
                            'message'=>'Anda gagal melakukan absen lembur, silahkan dicoba lagi');
                    }
                }
                else{
                    $data_insert['id_lemselesai'] = Absensi_m::getId($id_company, 'lembur_selesai');
                    $data_insert['id_lemmulai'] = Lembur_m::getLastRowILemburMulai($id_karyawan);
                    $insert_lembur = Lembur_m::add_lembur('lembur_selesai', $data_insert);
                    if($insert_lembur){
                        if($level_user=='1') {
                            Lembur_m::setujui(['status' => '4', 'approve' => '3'], $data_insert['id_lemmulai']);
                            Lembur_m::insertRekapLembur($data_insert['id_lemmulai']);
                        }
                    //SEND NOTIF
                        foreach ($data_penerima_notifikasi as $row_penerima) {
                            Notifikasi_c::send_fcm($row_penerima->token_fcm, 'Pengajuan Lembur', User_m::get_data_karyawan($id_karyawan)->nama_lengkap.' mengajukan lembur', '/detail_lembur_pegawai', $data_insert['id_lemmulai']);
    
                            # NOTIF WEB - NC 22-05-2021
                            $arr_penerima[] = $row_penerima->id_karyawan;
                            # NOTIF WEB - NC 22-05-2021
                        }
                        # NOTIF WEB - NC 22-05-2021
                        if(isset($arr_penerima) && count($arr_penerima) > 0)
                        {
                            $prm['id_karyawan']      = $arr_penerima;
                            $prm['id_data']          = $data_insert['id_lemmulai'];
                            $prm['jenis']            = 'L';
                            Notifikasi_c::send_web_notif($prm);
                        }
                        # NOTIF WEB - NC 22-05-2021
                    //
                    $response = array(
                        'success'=>true,
                        'message'=>'Anda berhasil melakukan absen lembur');
                    }else{
                        $response = array(
                            'success'=>false,
                            'message'=>'Anda gagal melakukan absen lembur, silahkan dicoba lagi');
                    }
                }
                DB::commit();
            }
            catch(\Exception $e){
                DB::rollback();
                $response = array('success'=>false, 'message'=>'Terjadi kesalahan, harap coba beberapa saat lagi'); 
            }
            return response()->json($response,200);
        }

        public function batalkan($id_lemmulai = null, $id_karyawan = null){
            return Lembur_m::batalkan($id_lemmulai, $id_karyawan);
        }

        public function setujui($id_lemmulai = null, $id_karyawan = null, $id_company = null){
            $data_lembur = Lembur_m::getDataLemburById($id_lemmulai);
            //
            $update = false;
            DB::beginTransaction();
            try{
                foreach ($this->column_setting_approval as $row) {
                    //SEND NOTIF BAWAHAN YG TERKAIT
                    if(Lembur_m::cek_approval_per_column(['id_lemmulai' => $id_lemmulai, $row['column_approval'] => '4'])>0){
                        $data_penerima_notifikasi = Notifikasi_c::GetBawahanPenerimaNotifikasi($row['level_user'], $id_company, $data_lembur->id_cabang, $data_lembur->id_departemen, $data_lembur->user_spv);
                        foreach ($data_penerima_notifikasi as $row_penerima) {
                             Notifikasi_c::send_fcm($row_penerima->token_fcm, 'Pengajuan Lembur', 'Pengajuan lembur '.User_m::get_data_karyawan($data_lembur->id_karyawan)->nama_lengkap.' telah disetujui oleh '.User_m::get_data_karyawan($id_karyawan)->nama_lengkap, '/detail_lembur_pegawai', $id_lemmulai);
                        }
                    }
                    //
                    if(Lembur_m::cek_approval_per_column(['id_lemmulai' => $id_lemmulai, $row['column_approval'] => '1'])>0){
                        $update = Lembur_m::setujui(
                            [
                                $row['column_approval'] => '4',
                                $row['column_tgl_approval'] => date("Y-m-d H:i:s"),
                                $row['column_user_approval'] => $id_karyawan
                            ]
                            , $id_lemmulai);
                        break;
                    }
                 }
                 if ($update) {
                    //SEND NOTIF
                    Notifikasi_c::send_fcm(User_m::get_data_karyawan($data_lembur->id_karyawan)->token_fcm, 'Pengajuan Lembur', 'Pengajuan Lemburmu telah disetujui oleh '.User_m::get_data_karyawan($id_karyawan)->nama_lengkap, '/detail_lembur_saya', $id_lemmulai);
                    //
                    $index = 0;
                     foreach ($this->column_setting_approval as $row) {
                        $index++;
                        if(Lembur_m::cek_approval_per_column(['id_lemmulai' => $id_lemmulai, $row['column_approval'] => '5'])>0){
                            //SEND NOTIF ATASAN
                            $data_penerima_notifikasi = Notifikasi_c::GetAtasanPenerimaNotifikasi($row['level_user'], $id_company, $data_lembur->id_cabang, $data_lembur->id_departemen);
                            foreach ($data_penerima_notifikasi as $row_penerima) {
    
                                //NOTIFIKASI KHUSUS 07062021
                                if($row_penerima->level_user=='1'){
                                    Notifikasi_c::notifikasi_khusus($id_company, 'notif_hrd', 'Pengajuan Lembur', User_m::get_data_karyawan($data_lembur->id_karyawan)->nama_lengkap.' mengajukan lembur');
                                }
                                else if($row_penerima->level_user=='6'){
                                    Notifikasi_c::notifikasi_khusus($id_company, 'notif_finance', 'Pengajuan Lembur', User_m::get_data_karyawan($data_lembur->id_karyawan)->nama_lengkap.' mengajukan lembur');
                                }
    
                                 Notifikasi_c::send_fcm($row_penerima->token_fcm, 'Pengajuan Lembur', User_m::get_data_karyawan($data_lembur->id_karyawan)->nama_lengkap.' mengajukan lembur', '/detail_lembur_pegawai', $id_lemmulai);
                                 
                                # NOTIF WEB - NC 22-05-2021
                                $arr_penerima[] = $row_penerima->id_karyawan;
                                # NOTIF WEB - NC 22-05-2021
                            }
                            # NOTIF WEB - NC 22-05-2021
                            if(isset($arr_penerima) && count($arr_penerima) > 0)
                            {
                                $prm['id_karyawan']      = $arr_penerima;
                                $prm['id_data']          = $id_lemmulai;
                                $prm['jenis']            = 'L';
                                Notifikasi_c::send_web_notif($prm);
                            }
                            # NOTIF WEB - NC 22-05-2021
                            //
                            Lembur_m::setujui([$row['column_approval'] => '1'], $id_lemmulai);
                            break;
                        }
                        if($index==count($this->column_setting_approval)) {
                            Lembur_m::setujui(['status' => '4', 'approve' => '3'], $id_lemmulai);
                            Lembur_m::insertRekapLembur($id_lemmulai);
                        }
                    }
                    $response = array('success'=>true, 'message'=>'Lembur berhasil disetujui');
                 }
                 else $response = array('success'=>false, 'message'=>'Lembur gagal disetujui'); 
                 DB::commit();
            }
            catch(\Exception $e){
                DB::rollback();
                $response = array('success'=>false, 'message'=>'Terjadi kesalahan, harap coba beberapa saat lagi'); 
            }
            
            return response()->json($response,200);
        }

        public function hrd_mewakili_setujui($id_lemmulai = null, $id_karyawan = null, $id_company = null){
            $data_lembur = Lembur_m::getDataLemburById($id_lemmulai);
            //
            $update = false;
            DB::beginTransaction(); 
            try{
                $update_data_batch = array();
            foreach ($this->column_setting_approval as $row) {
                if(Lembur_m::cek_approval_per_column_by_hrd($id_lemmulai, $row['column_approval'])>0){
                    $update_data_batch[$row['column_approval']] = '4';
                    $update_data_batch[$row['column_tgl_approval']] = date("Y-m-d H:i:s");
                    $update_data_batch[$row['column_user_approval']] = $id_karyawan;
                }
             }
             $update = Lembur_m::setujui(
                $update_data_batch
                , $id_lemmulai);
             if ($update) {
                //SEND NOTIF
                Notifikasi_c::send_fcm(User_m::get_data_karyawan($data_lembur->id_karyawan)->token_fcm, 'Pengajuan Lembur', 'Pengajuan Lemburmu telah disetujui oleh '.User_m::get_data_karyawan($id_karyawan)->nama_lengkap, '/detail_lembur_saya', $id_lemmulai);
                //
                $index = 0;
                 foreach ($this->column_setting_approval as $row) {
                    $index++;
                    if(Lembur_m::cek_approval_per_column(['id_lemmulai' => $id_lemmulai, $row['column_approval'] => '4'])>0){
                        //SEND NOTIF ALL ACC
                        $data_penerima_notifikasi = Notifikasi_c::GetAtasanPenerimaNotifikasi($row['level_user'], $id_company, $data_lembur->id_cabang, $data_lembur->id_departemen);
                        foreach ($data_penerima_notifikasi as $row_penerima) {

                            //NOTIFIKASI KHUSUS 07062021
                            if($row_penerima->level_user=='1'){
                                Notifikasi_c::notifikasi_khusus($id_company, 'notif_hrd', 'Pengajuan Lembur', User_m::get_data_karyawan($data_lembur->id_karyawan)->nama_lengkap.' mengajukan lembur');
                            }
                            else if($row_penerima->level_user=='6'){
                                Notifikasi_c::notifikasi_khusus($id_company, 'notif_finance', 'Pengajuan Lembur', User_m::get_data_karyawan($data_lembur->id_karyawan)->nama_lengkap.' mengajukan lembur');
                            }

                             Notifikasi_c::send_fcm($row_penerima->token_fcm, 'Pengajuan Lembur', User_m::get_data_karyawan($data_lembur->id_karyawan)->nama_lengkap.' mengajukan lembur', '/detail_lembur_pegawai', $id_lemmulai);
                             
                            # NOTIF WEB - NC 22-05-2021
                            $arr_penerima[] = $row_penerima->id_karyawan;
                            # NOTIF WEB - NC 22-05-2021
                        }
                        # NOTIF WEB - NC 22-05-2021
                        if(isset($arr_penerima) && count($arr_penerima) > 0)
                        {
                            $prm['id_karyawan']      = $arr_penerima;
                            $prm['id_data']          = $id_lemmulai;
                            $prm['jenis']            = 'L';
                            Notifikasi_c::send_web_notif($prm);
                        }
                        # NOTIF WEB - NC 22-05-2021
                        //
                    }
                    if($index==count($this->column_setting_approval)) {
                        Lembur_m::setujui(['status' => '4', 'approve' => '3'], $id_lemmulai);
                        Lembur_m::insertRekapLembur($id_lemmulai);
                    }
                }
                $response = array('success'=>true, 'message'=>'Lembur berhasil disetujui');
             }
             else $response = array('success'=>false, 'message'=>'Lembur gagal disetujui');
             DB::commit();
            }
            catch(\Exception $e){
                DB::rollback();
                $response = array('success'=>false, 'message'=>'Terjadi kesalahan, harap coba beberapa saat lagi'); 
            }
             
            return response()->json($response,200);
        }

        public function hrd_mewakili_tolak($id_lemmulai = null, $id_karyawan = null){
            $update = false;
            DB::beginTransaction(); 
            try{
                $update_data_batch = array();
            foreach ($this->column_setting_approval as $row) {
                if(Lembur_m::cek_approval_per_column_by_hrd($id_lemmulai, $row['column_approval'])>0){
                    $update_data_batch[$row['column_approval']] = '3';
                    $update_data_batch[$row['column_tgl_approval']] = date("Y-m-d H:i:s");
                    $update_data_batch[$row['column_user_approval']] = $id_karyawan;
                    $update_data_batch['status'] = '3';
                    $update_data_batch['approve'] = '2';
                }
             }
             $update = Lembur_m::setujui(
                $update_data_batch
                , $id_lemmulai);
             if ($update) {
                //SEND NOTIF
                $data_lembur = Lembur_m::getDataLemburById($id_lemmulai);
                Notifikasi_c::send_fcm(User_m::get_data_karyawan($data_lembur->id_karyawan)->token_fcm, 'Pengajuan Lembur', 'Pengajuan Lemburmu telah ditolak oleh '.User_m::get_data_karyawan($id_karyawan)->nama_lengkap, '/detail_lembur_saya', $id_lemmulai);
                //
                $response = array('success'=>true, 'message'=>'Lembur berhasil ditolak');
             }
             else $response = array('success'=>false, 'message'=>'Lembur gagal ditolak');
             DB::commit();
            }
            catch(\Exception $e){
                DB::rollback();
                $response = array('success'=>false, 'message'=>'Terjadi kesalahan, harap coba beberapa saat lagi'); 
            }
            
             return response()->json($response,200);
        }

        public function tolak($id_lemmulai = null, $id_karyawan = null){
            $update = false;
            DB::beginTransaction();
            try{
                foreach ($this->column_setting_approval as $row) {
                    if(Lembur_m::cek_approval_per_column(['id_lemmulai' => $id_lemmulai, $row['column_approval'] => '1'])>0){
                        $update = Lembur_m::setujui(
                            [
                                $row['column_approval'] => '3',
                                $row['column_tgl_approval'] => date("Y-m-d H:i:s"),
                                $row['column_user_approval'] => $id_karyawan,
                                'status'    => '3',
                                'approve'   => '2'
                            ]
                            , $id_lemmulai);
                        break;
                    }
                 }
                 if ($update) {
                    //SEND NOTIF
                    $data_lembur = Lembur_m::getDataLemburById($id_lemmulai);
                    Notifikasi_c::send_fcm(User_m::get_data_karyawan($data_lembur->id_karyawan)->token_fcm, 'Pengajuan Lembur', 'Pengajuan Lemburmu telah ditolak oleh '.User_m::get_data_karyawan($id_karyawan)->nama_lengkap, '/detail_lembur_saya', $id_lemmulai);
                    //
                    $response = array('success'=>true, 'message'=>'Lembur berhasil ditolak');
                 }
                 else $response = array('success'=>false, 'message'=>'Lembur gagal ditolak');
                 DB::commit();
            }
            catch(\Exception $e){
                DB::rollback();
                $response = array('success'=>false, 'message'=>'Terjadi kesalahan, harap coba beberapa saat lagi'); 
            }
             return response()->json($response,200);
        }

        public function getDataLembur(Request $request){
            $data = array(
                "id_company"        => $request->get('id_company'),
                "id_karyawan"       => $request->get('id_karyawan'),
                "level_user"        => $request->get('level_user'),
                "id_cabang"         => $request->get('id_cabang'),
                "limit"             => $request->get('limit'),
                "offset"            => $request->get('offset'),
                "konteks"           => $request->get('konteks'),
                "range_tanggal_mulai"     => $request->get('range_tanggal_mulai'),
                "range_tanggal_selesai"   => $request->get('range_tanggal_selesai'),
                "id_lemmulai"       => $request->get('id_lemmulai'),
            );

            return Lembur_m::getDataLembur($data);
        }

        public function getDataRekapLembur(Request $request){
            $data = array(
                "id_lembur"         => $request->get('id_lembur'),
                "id_company"        => $request->get('id_company'),
                "id_karyawan"       => $request->get('id_karyawan'),
                "id_karyawan_select"       => $request->get('id_karyawan_select'),
                "level_user"        => $request->get('level_user'),
                "id_cabang"         => $request->get('id_cabang'),
                "id_departemen"         => $request->get('id_departemen'),
                "limit"             => $request->get('limit'),
                "offset"            => $request->get('offset'),
                "konteks"           => $request->get('konteks'),
                "month_year"            => $request->get('month_year'),
                "range_tanggal_mulai"     => $request->get('range_tanggal_mulai'),
                "range_tanggal_selesai"   => $request->get('range_tanggal_selesai'),
                "konteks"       => $request->get('konteks'),
            );

            return Lembur_m::getDataRekapLembur($data);
        }

        public function test($id_lemmulai = null){
            Lembur_m::insertRekapLembur($id_lemmulai);
        }

    }