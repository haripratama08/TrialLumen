<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use App\Models\Izin_m;
    use App\Models\User_m;
    use App\Models\Token_m;
    use App\Models\Absensi_m;
    use App\Http\Controllers\Notifikasi_c;
    use App\Http\Controllers\Uploads_c;
    use DateTime;


    class Izin_c extends Controller{
        public $path;

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

        public function dataIzinByKaryawan(Request $request){
            $id_company = $request->input('id_company');
            $id_karyawan = $request->input('id_karyawan');
            $filter_bulan = $request->input('filter_bulan');
            $limit_start = $request->input('limit_start');
            $limit_end = $limit_start + 10;
            
            return Izin_m::dataIzinByKaryawan($id_company, $id_karyawan, $filter_bulan, $limit_start, $limit_end);
        }

        public function dataIzinByDepartemen(Request $request){
            $id_company = $request->input('id_company');
            $id_karyawan = $request->input('id_karyawan');
            $filter_karyawan = $request->input('filter_karyawan');
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            $limit_start = $request->input('limit_start');
            $limit_end = $limit_start + 10;

            return Izin_m::dataIzinByDepartemen($id_company, $id_karyawan, $filter_karyawan, $start_date, $end_date, $limit_start, $limit_end);
        }

        public function data(Request $request){
            $data = array("id_company"       => $request->get('id_company'),
                        "id_karyawan"       => $request->get('id_karyawan'),
                        "level_user"        => $request->get('level_user'),
                        "filter_karyawan"   => $request->get('filter_karyawan'),
                        "filter_bulan"      => $request->get('filter_bulan'),
                        "start_date"        => $request->get('start_date'),
                        "end_date"          => $request->get('end_date'),
                        "limit_start"       => $request->get('limit_start'),
                        "limit_end"         => ($request->get('limit_start') + 10));

            return Izin_m::data($data);
        }

        //-----------------------------------------------SURYA-------------------------------------------------------//

        public function getJenisIzin(Request $request){
            $id_company = $request->get('id_company');
            return Izin_m::getJenisIzin($id_company);
        }

        public function getDataIzin(Request $request){
            $data = array(
                "id_company"            => $request->get('id_company'),
                "id_karyawan"           => $request->get('id_karyawan'),
                "id_karyawan_select"    => $request->get('id_karyawan_select'),
                "id_departemen"         => $request->get('id_departemen'),
                "level_user"            => $request->get('level_user'),
                "id_cabang"             => $request->get('id_cabang'),
                "limit"                 => $request->get('limit'),
                "offset"                => $request->get('offset'),
                "konteks"               => $request->get('konteks'),
                "month_year"            => $request->get('month_year'),
                "range_tanggal_mulai"   => $request->get('range_tanggal_mulai'),
                "range_tanggal_selesai" => $request->get('range_tanggal_selesai'),
                "id_izin"               => $request->get('id_izin'),
            );

            return Izin_m::getDataIzin($data);
        }

        public function batalkan($id_izin = null, $id_karyawan = null){
            return Izin_m::batalkan($id_izin, $id_karyawan);
        }

        public function test_notifikasi(){
            Notifikasi_c::notifikasi_khusus('1', 'notif_hrd', 'Test notif hrd', 'body test notif hrd');
        }

        public function pengajuan(Request $request){
            $id_karyawan = $request->input('id_karyawan');
            $level_user = $request->input('level_user');
            $id_company = $request->input('id_company');
            $id_cabang = $request->input('id_cabang');
            $id_departemen = $request->input('id_departemen');
            $kode_izin = $request->input('kode_izin');
            $tgl_mulai_izin = $request->input('tgl_mulai_izin');
            $tgl_selesai_izin = $request->input('tgl_selesai_izin');
            $nama_izin = $request->input('nama_izin');
            $ket_izin = $request->input('ket_izin');
            $nama_izin = $request->input('nama_izin');
            $jenis_izin = $request->input('jenis_izin');
            $sisa_cuti = $request->input('sisa_cuti');
            $flag = $request->input('flag');
            $id_izin = Izin_m::get_id_izin($id_company);
            $image_count = $request->input('image_count');
            $jumlah_izin = $request->input('jumlah_izin');

            $data_insert = array(); 
            $data_insert['id_karyawan'] = $id_karyawan;
            $data_insert['id_company'] = $id_company;
            $data_insert['id_cabang'] = $id_cabang;
            $data_insert['id_departemen'] = $id_departemen;
            $data_insert['kode_izin'] = $kode_izin;
            $data_insert['tgl_mulai_izin'] = $tgl_mulai_izin;
            $data_insert['tgl_selesai_izin'] = $tgl_selesai_izin;
            $data_insert['nama_izin'] = $nama_izin;
            $data_insert['ket_izin'] = $ket_izin;
            $data_insert['jenis_izin'] = $jenis_izin;
            $data_insert['file'] = '';
            $data_insert['status'] = '1';
            $data_insert['user_kedep'] = '';
            $data_insert['user_kacab'] = '';
            $data_insert['user_hrd'] = '';
            $data_insert['user_direksi'] = '';
            $data_insert['user_spv'] = '';
            $data_insert['id_izin'] = $id_izin;
            // $data_insert['jml_izin'] = ((strtotime($tgl_selesai_izin) - strtotime($tgl_mulai_izin))/60*60*24)+1;
            $data_insert['jml_izin'] = $jumlah_izin;

            //CEK FLAG DAN SISA CUTI
            if($flag=='1'&& $sisa_cuti < $jumlah_izin){
                $response = array('success'=>false, 'message'=>'Sisa Cuti tidak mencukupi'); 
                return response()->json($response,200);
            }
            //

            //CEK MAX PER PENGAJUAN IZIN && FLAG PENGURANG CUTI
            $data_jenis_izin = Izin_m::cekMaxPengajuanIzin($kode_izin, $id_company);
            $data_insert['flag'] = $data_jenis_izin->flag;

            DB::beginTransaction();
            try{
                if($data_insert['jml_izin']>$data_jenis_izin->max_izin && $data_jenis_izin->max_izin!=0){
                    $response = array('success'=>false, 'message'=>'Maksimal pengajuan '.$data_jenis_izin->nama.' '.$data_jenis_izin->max_izin.' hari'); 
                }
                else{
                    //CEK APAKAH PERNAH IZIN DI TGL TSB
                    if(Izin_m::cekTanggalIzin($id_karyawan, $tgl_mulai_izin, $tgl_selesai_izin)==0){
                        //CEK SISA CUTI
                        if (Izin_m::getFlagPengurangCuti($kode_izin, $id_company) && !User_m::get_data_karyawan($id_karyawan)->jatah_cuti>=$data_insert['jml_izin']) {
                            $response = array('success'=>false, 'message'=>'Sisa cuti tidak mencukupi'); 
                        }
                        else{
                            //MAPPING COLUMN APPROVAL DAN VALUE COLUMN APPROVAL
                            $data_penerima_notifikasi = array();
                            $data_p_approval = Izin_m::get_p_approval($level_user, $id_company, $id_cabang);
    
                            //CEK SPV 13 Agustus 2021
                            $get_spv =  User_m::get_data_user_by_id($id_karyawan);
                            $id_spv = $get_spv->supervisi;
                            $exploded_data_p_approval = explode(',',$data_p_approval);
                            $ada_supervisi = array_search("7",$exploded_data_p_approval);
                            //
                            
                            if($data_p_approval==null && $level_user!='1') $response = array('success'=>false, 'message'=>'Pengaturan izin belum dibuat, silahkan hubungi atasan Anda');
                            else if(empty($ada_supervisi)  && count($exploded_data_p_approval)==0){
                                //JIKA ADA SUPERVISI DIDALAM P APPROVAL DAN SPVNYA KOSONG DAN ISI P APPROVAL HANYA SPV
                                $response = array('success'=>false, 'message'=>'Pengaturan izin belum dibuat, silahkan hubungi atasan Anda');
                            }
                            else{
                                $data_master_jabatan = Izin_m::get_master_jabatan($data_p_approval, $id_company, $id_departemen); //GET LEVEL USER YG MENGAPROVE
                                if(count($data_master_jabatan)==0 && $level_user=='1'){
                                    $data_insert['status'] = '4';
                                }
                                else{
                                    $mapping = array();
                                    foreach ($exploded_data_p_approval as $row) {
                                        $detail_mapping = array();
                                        if ($row=='1') {
                                            //NOTIF KHUSUS 07062021
                                            if($data_penerima_notifikasi==null){
                                                Notifikasi_c::notifikasi_khusus($id_company, 'notif_hrd', 'Pengajuan Izin',User_m::get_data_karyawan($id_karyawan)->nama_lengkap.' mengajukan '.$nama_izin);
                                                $data_penerima_notifikasi = User_m::get_data_user_by_where(
                                                    [
                                                        'usergroup.id' => $row,
                                                        'users.id_company' => $id_company,
                                                    ]);
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
                                                    ]);
                                            $detail_mapping['column_approval'] = 'approval_kedep';
                                            
                                        }
                                        else if ($row=='4') {
                                        if($data_penerima_notifikasi==null)
                                                $data_penerima_notifikasi = User_m::get_data_user_by_where(
                                                    [
                                                        'usergroup.id' => $row,
                                                        'users.id_company' => $id_company,
                                                    ]);
                                            $detail_mapping['column_approval'] = 'approval_direksi';
                                        }
                                        else if ($row=='5') {
                                            if($data_penerima_notifikasi==null)
                                                $data_penerima_notifikasi = User_m::get_data_user_by_where(
                                                    [
                                                        'usergroup.id' => $row,
                                                        'users.id_company' => $id_company,
                                                        'data_karyawan.id_cabang' => $id_cabang,
                                                    ]);
                                            $detail_mapping['column_approval'] = 'approval_kacab';
                                        }
                                        else if ($row=='7') {
                                            if($data_penerima_notifikasi==null && $id_spv!=null){
                                                $data_penerima_notifikasi = User_m::get_data_user_by_where(
                                                [
                                                    'usergroup.id' => $row,
                                                    'users.id_company' => $id_company,
                                                    'data_karyawan.id_departemen' => $id_departemen,
                                                    'data_karyawan.id_karyawan' => $id_spv 
                                                ]);
                                            }
                                            if($id_spv==null) continue;//SKIP SUPERVISI
                                            $detail_mapping['column_approval'] = 'approval_spv';
                                        }
                                        $mapping[] = $detail_mapping;
                                    }
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
                                }
                                
                                //
    
    
                                //UPLOAD GAMBAR
                                // $mkdir = "izin/".date("Ym")."/".$id_company."/".$jenis_izin;
                                // if(!is_dir(base_path("../web/public/".$mkdir))){ 
                                //     mkdir(base_path("../web/public/".$mkdir),0777,TRUE); 
                                // }
                                for($i=1;$i<=$image_count;$i++){
                                    Izin_m::insert_file(
                                        $id_izin, 
                                        Uploads_c::upload_file(
                                            $request->input('image'.$i), 
                                            "/absensi/".env('NAME_APPLICATION')."/",
                                            $id_company."/".$jenis_izin."/".date("Ym"),
                                            $id_karyawan.date('YmdHis').$i.".jpg"
                                        ), 
                                        $id_company);
                                }
                                //
                                $insert = Izin_m::pengajuan($data_insert);
                                if($insert){
                                    if($data_insert['status']=='4'){
                                        Izin_m::insertAbsensiIzin($id_izin);
                                        Izin_m::updateSisaCuti($id_izin);
                                    }
                                //SEND NOTIF
                                    foreach ($data_penerima_notifikasi as $row_penerima) {
                                        Notifikasi_c::send_fcm($row_penerima->token_fcm, 'Pengajuan Izin', User_m::get_data_karyawan($id_karyawan)->nama_lengkap.' mengajukan '.$nama_izin, '/detail_izin_pegawai', $id_izin);
    
                                        # NOTIF WEB - NC 22-05-2021
                                        $arr_penerima[] = $row_penerima->id_karyawan;
                                        # NOTIF WEB - NC 22-05-2021
                                    }
    
                                    # NOTIF WEB - NC 22-05-2021
                                    if(isset($arr_penerima) && count($arr_penerima) > 0)
                                    {
                                        $prm['id_karyawan']      = $arr_penerima;
                                        $prm['id_data']          = $id_izin;
                                        $prm['jenis']            = 'I';
                                        Notifikasi_c::send_web_notif($prm);
                                    }
                                    # NOTIF WEB - NC 22-05-2021
                                //
                                    $response = array('success'=>true, 'message'=>'Izin berhasil diajukan');
                                    DB::commit();
                                } 
                                else $response = array('success'=>false, 'message'=>'Izin gagal diajukan');
                            }
    
                            
                        }
                        //
                    }
                    else $response = array('success'=>false, 'message'=>'Anda sudah mengajukan izin pada tanggal tersebut'); 
    
                }
            }
            catch(\Exception $e){
                DB::rollback();
                $response = array('success'=>false, 'message'=>'Terjadi kesalahan, harap coba beberapa saat lagi'); 
            }
            return response()->json($response,200);

        }

         public function setujui($id_izin = null, $id_karyawan = null, $id_company = null){
            $data_izin = Izin_m::getDataIzinById($id_izin);
            //
            $update = false;
            DB::beginTransaction();
            try{
                if(Izin_m::cek_sisa_cuti(['id_izin' => $id_izin]) || $data_izin->flag == '0'){//SISA CUTI MASIH ADA ATAU TIDAK MENGURANGI CUTI
                    foreach ($this->column_setting_approval as $row) {
                        //SEND NOTIF BAWAHAN YG TERKAIT
                        if(Izin_m::cek_approval_per_column(['id_izin' => $id_izin, $row['column_approval'] => '4'])>0){
                            $data_penerima_notifikasi = Notifikasi_c::GetBawahanPenerimaNotifikasi($row['level_user'], $id_company, $data_izin->id_cabang, $data_izin->id_departemen, $data_izin->user_spv);
                            foreach ($data_penerima_notifikasi as $row_penerima) {
                                 Notifikasi_c::send_fcm($row_penerima->token_fcm, 'Pengajuan Izin', 'Pengajuan '.$data_izin->nama_izin.' '.User_m::get_data_karyawan($data_izin->id_karyawan)->nama_lengkap.' telah disetujui oleh '.User_m::get_data_karyawan($id_karyawan)->nama_lengkap, '/detail_izin_pegawai', $id_izin);
                            }
                        }
                        //
                        if(Izin_m::cek_approval_per_column(['id_izin' => $id_izin, $row['column_approval'] => '1'])>0){
                            $update = Izin_m::setujui(
                                [
                                    $row['column_approval'] => '4',
                                    $row['column_tgl_approval'] => date("Y-m-d H:i:s"),
                                    $row['column_user_approval'] => $id_karyawan
                                ]
                                , $id_izin);
                            break;
                        }
                     }
                     if ($update) {
                        //SEND NOTIF PEMBUAT IZIN
                        Notifikasi_c::send_fcm(User_m::get_data_karyawan($data_izin->id_karyawan)->token_fcm, 'Pengajuan Izin', 'Pengajuan '.$data_izin->nama_izin.'mu telah disetujui oleh '.User_m::get_data_karyawan($id_karyawan)->nama_lengkap, '/detail_izin_saya', $id_izin);
                        //
                        $index = 0;
                         foreach ($this->column_setting_approval as $row) {
                            $index++;
                            if(Izin_m::cek_approval_per_column(['id_izin' => $id_izin, $row['column_approval'] => '5'])>0){
                                //SEND NOTIF ATASAN
                                $data_penerima_notifikasi = Notifikasi_c::GetAtasanPenerimaNotifikasi($row['level_user'], $id_company, $data_izin->id_cabang, $data_izin->id_departemen);
                                foreach ($data_penerima_notifikasi as $row_penerima) {
                                    
                                    //NOTIFIKASI KHUSUS 07062021
                                    if($row_penerima->level_user=='1'){
                                        Notifikasi_c::notifikasi_khusus($id_company, 'notif_hrd', 'Pengajuan Izin',User_m::get_data_karyawan($data_izin->id_karyawan)->nama_lengkap.' mengajukan '.$data_izin->nama_izin);
                                    }
                                    else if($row_penerima->level_user=='6'){
                                        Notifikasi_c::notifikasi_khusus($id_company, 'notif_finance', 'Pengajuan Izin',User_m::get_data_karyawan($data_izin->id_karyawan)->nama_lengkap.' mengajukan '.$data_izin->nama_izin);
                                    }
                                    //
    
                                     Notifikasi_c::send_fcm($row_penerima->token_fcm, 'Pengajuan Izin', User_m::get_data_karyawan($data_izin->id_karyawan)->nama_lengkap.' mengajukan '.$data_izin->nama_izin, '/detail_izin_pegawai', $id_izin);
    
                                    # NOTIF WEB - NC 22-05-2021
                                    $arr_penerima[] = $row_penerima->id_karyawan;
                                    # NOTIF WEB - NC 22-05-2021
                                }
    
                                # NOTIF WEB - NC 22-05-2021
                                if(isset($arr_penerima) && count($arr_penerima) > 0)
                                {
                                    $prm['id_karyawan']      = $arr_penerima;
                                    $prm['id_data']          = $id_izin;
                                    $prm['jenis']            = 'I';
                                    Notifikasi_c::send_web_notif($prm);
                                }
                                # NOTIF WEB - NC 22-05-2021
                                //
                                Izin_m::setujui([$row['column_approval'] => '1'], $id_izin);
                                break;
                            }
                            if($index==count($this->column_setting_approval)) {
                                Izin_m::setujui(['status' => '4'], $id_izin);
                                Izin_m::insertAbsensiIzin($id_izin);
                                Izin_m::updateSisaCuti($id_izin);
                            }
                        }
    
                        $response = array('success'=>true, 'message'=>'Izin berhasil disetujui');
                        DB::commit();
                     }
                     else $response = array('success'=>false, 'message'=>'Izin gagal disetujui'); 
                }
                else $response = array('success'=>false, 'message'=>'Sisa cuti tidak mencukupi'); 
            }
            catch(\Exception $e){
                DB::rollback();
                $response = array('success'=>false, 'message'=>'Terjadi kesalahan, harap coba beberapa saat lagi'); 
            }
            
            return response()->json($response,200);
        }

        public function hrd_mewakili_setujui($id_izin = null, $id_karyawan = null, $id_company = null){
            $data_izin = Izin_m::getDataIzinById($id_izin);
            //
            $update = false;
            DB::beginTransaction();
            try{
                if(Izin_m::cek_sisa_cuti(['id_izin' => $id_izin]) || $data_izin->flag == '0'){//SISA CUTI MASIH ADA ATAU TIDAK MENGURANGI CUTI
                    $update_data_batch = array();
                    foreach ($this->column_setting_approval as $row) {
                        if(Izin_m::cek_approval_per_column_by_hrd($id_izin, $row['column_approval'])>0){
                            $update_data_batch[$row['column_approval']] = '4';
                            $update_data_batch[$row['column_tgl_approval']] = date("Y-m-d H:i:s");
                            $update_data_batch[$row['column_user_approval']] = $id_karyawan;
                        }
                     }
                     $update = Izin_m::setujui($update_data_batch
                        , $id_izin);
                     if ($update) {
                        //SEND NOTIF PEMBUAT IZIN
                        Notifikasi_c::send_fcm(User_m::get_data_karyawan($data_izin->id_karyawan)->token_fcm, 'Pengajuan Izin', 'Pengajuan '.$data_izin->nama_izin.'mu telah disetujui oleh '.User_m::get_data_karyawan($id_karyawan)->nama_lengkap, '/detail_izin_saya', $id_izin);
                        //
                        $index = 0;
                         foreach ($this->column_setting_approval as $row) {
                            $index++;
                            if(Izin_m::cek_approval_per_column(['id_izin' => $id_izin, $row['column_approval'] => '4'])>0){
                                //SEND NOTIF ALL ACC
                                $data_penerima_notifikasi = Notifikasi_c::GetAtasanPenerimaNotifikasi($row['level_user'], $id_company, $data_izin->id_cabang, $data_izin->id_departemen);
                                foreach ($data_penerima_notifikasi as $row_penerima) {
                                    
                                    //NOTIFIKASI KHUSUS 07062021
                                    if($row_penerima->level_user=='1'){
                                        Notifikasi_c::notifikasi_khusus($id_company, 'notif_hrd', 'Pengajuan Izin',User_m::get_data_karyawan($data_izin->id_karyawan)->nama_lengkap.' mengajukan '.$data_izin->nama_izin);
                                    }
                                    else if($row_penerima->level_user=='6'){
                                        Notifikasi_c::notifikasi_khusus($id_company, 'notif_finance', 'Pengajuan Izin',User_m::get_data_karyawan($data_izin->id_karyawan)->nama_lengkap.' mengajukan '.$data_izin->nama_izin);
                                    }
                                    //
    
                                     Notifikasi_c::send_fcm($row_penerima->token_fcm, 'Pengajuan Izin', User_m::get_data_karyawan($data_izin->id_karyawan)->nama_lengkap.' mengajukan '.$data_izin->nama_izin, '/detail_izin_pegawai', $id_izin);
    
                                    # NOTIF WEB - NC 22-05-2021
                                    $arr_penerima[] = $row_penerima->id_karyawan;
                                    # NOTIF WEB - NC 22-05-2021
                                }
    
                                # NOTIF WEB - NC 22-05-2021
                                if(isset($arr_penerima) && count($arr_penerima) > 0)
                                {
                                    $prm['id_karyawan']      = $arr_penerima;
                                    $prm['id_data']          = $id_izin;
                                    $prm['jenis']            = 'I';
                                    Notifikasi_c::send_web_notif($prm);
                                }
                                # NOTIF WEB - NC 22-05-2021
                                //
                            }
                            if($index==count($this->column_setting_approval)) {
                                Izin_m::setujui(['status' => '4'], $id_izin);
                                Izin_m::insertAbsensiIzin($id_izin);
                                Izin_m::updateSisaCuti($id_izin);
                            }
                        }
    
                        $response = array('success'=>true, 'message'=>'Izin berhasil disetujui');
                        DB::commit();
                     }
                     else $response = array('success'=>false, 'message'=>'Izin gagal disetujui'); 
                }
                else $response = array('success'=>false, 'message'=>'Sisa cuti tidak mencukupi'); 
            }
            catch(\Exception $e){
                DB::rollback();
                $response = array('success'=>false, 'message'=>'Terjadi kesalahan, harap coba beberapa saat lagi'); 
            }

            return response()->json($response,200);
        }

        public function hrd_mewakili_tolak($id_izin = null, $id_karyawan = null){
            $update = false;
            $update_data_batch = array();
            DB::beginTransaction();
            try{
                foreach ($this->column_setting_approval as $row) {
                    if(Izin_m::cek_approval_per_column_by_hrd($id_izin, $row['column_approval'])>0){
                        $update_data_batch[$row['column_approval']] = '3';
                        $update_data_batch[$row['column_tgl_approval']] = date("Y-m-d H:i:s");
                        $update_data_batch[$row['column_user_approval']] = $id_karyawan;
                        $update_data_batch['status'] = '3';
                        
                    }
                 }
                 $update = Izin_m::setujui(
                   $update_data_batch
                    , $id_izin);
                 if ($update) {
                    //SEND NOTIF
                    $data_izin = Izin_m::getDataIzinById($id_izin);
                    Notifikasi_c::send_fcm(User_m::get_data_karyawan($data_izin->id_karyawan)->token_fcm, 'Pengajuan Izin', 'Pengajuan '.$data_izin->nama_izin.'mu telah ditolak oleh '.User_m::get_data_karyawan($id_karyawan)->nama_lengkap, '/detail_izin_saya', $id_izin);
                    //
                    $response = array('success'=>true, 'message'=>'Izin berhasil ditolak');
                    DB::commit();
                }
                 else $response = array('success'=>false, 'message'=>'Izin gagal ditolak');
            }
            catch(\Exception $e){
                DB::rollback();
                $response = array('success'=>false, 'message'=>'Terjadi kesalahan, harap coba beberapa saat lagi'); 
            }
             return response()->json($response,200);
        }

        public function tolak($id_izin = null, $id_karyawan = null){
            $update = false;
            DB::beginTransaction();
            try{
                foreach ($this->column_setting_approval as $row) {
                    if(Izin_m::cek_approval_per_column(['id_izin' => $id_izin, $row['column_approval'] => '1'])>0){
                        $update = Izin_m::setujui(
                            [
                                $row['column_approval'] => '3',
                                $row['column_tgl_approval'] => date("Y-m-d H:i:s"),
                                $row['column_user_approval'] => $id_karyawan,
                                'status' => '3'
                            ]
                            , $id_izin);
                        break;
                    }
                 }
                 if ($update) {
                    //SEND NOTIF
                    $data_izin = Izin_m::getDataIzinById($id_izin);
                    Notifikasi_c::send_fcm(User_m::get_data_karyawan($data_izin->id_karyawan)->token_fcm, 'Pengajuan Izin', 'Pengajuan '.$data_izin->nama_izin.'mu telah ditolak oleh '.User_m::get_data_karyawan($id_karyawan)->nama_lengkap, '/detail_izin_saya', $id_izin);
                    //
                    $response = array('success'=>true, 'message'=>'Izin berhasil ditolak');
                    DB::commit();
                }
                 else $response = array('success'=>false, 'message'=>'Izin gagal ditolak');
            }
            catch(\Exception $e){
                DB::rollback();
                $response = array('success'=>false, 'message'=>'Terjadi kesalahan, harap coba beberapa saat lagi'); 
            }
            
             return response()->json($response,200);
        }

        public function test(Request $request){
            $tanggal_selesai = $request->get('tanggal_selesai');
            $tanggal_mulai = $request->get('tanggal_mulai');
            $id_karyawan = $request->get('id_karyawan');
            echo Izin_m::cekTanggalIzin($id_karyawan, $tanggal_mulai, $tanggal_selesai);
        }

        public function cekSisaCutiKaryawan(Request $request){
            $data_karyawan = Token_m::get_data_karyawan_by_token($request->get('token_fcm'));
            if($data_karyawan==null) $response = array('success' => false, 'message' => 'Unauthorized Access');
            else{
                $sisa_cuti = Izin_m::cekSisaCutiKaryawan($request->get('id_karyawan'));
                $response = array(
                    'success' => true, 
                    'message' => 'Data berhasil ditemukan',
                    'data'    => strval($sisa_cuti->jatah_cuti) 
                );
            }
            return response()->json($response,200);
        }

        public function cekJumlahIzinKaryawan(Request $request){
            $data_karyawan = Token_m::get_data_karyawan_by_token($request->get('token_fcm'));
            if($data_karyawan==null) $response = array('success' => false, 'message' => 'Unauthorized Access');
            else{
                $tanggal_mulai = $request->get('tanggal_mulai');
                $tanggal_selesai = $request->get('tanggal_selesai');
                // $jumlah_hari = date("d", strtotime($tanggal_selesai))-date("d", strtotime($tanggal_mulai))+1;
                $date_tanggal_mulai= new DateTime($tanggal_mulai);
                $date_tanggal_selesai= new DateTime($tanggal_selesai);
                $jumlah_hari = ($date_tanggal_selesai->diff($date_tanggal_mulai))->days+1;
                $jumlah_izin = 0;
                for($i=0;$i<$jumlah_hari;$i++){
                    if(Absensi_m::cekHariKerjaKaryawan(
                        $data_karyawan->id_karyawan, $data_karyawan->id_cabang, $data_karyawan->id_company, 
                        date('Y-m-d',strtotime($tanggal_mulai . "+".$i." days"))
                    )) $jumlah_izin++;
                }
                $response = array(
                    'success' => true, 
                    'message' => 'Data berhasil ditemukan',
                    'data' => strval($jumlah_izin)
                );
            }
            return response()->json($response,200);
        }
    }

 