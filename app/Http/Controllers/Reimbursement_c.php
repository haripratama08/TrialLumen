<?php

    namespace App\Http\Controllers;
    use App\Http\Helpers\DateFormat;
    use Illuminate\Http\Request;
    use App\Http\Helpers\TimezoneMapper;
    use App\Models\Token_m;
    use App\Models\Reimbursement_m;
    use App\Models\User_m;
    use App\Http\Helpers\Convertion;
    use App\Http\Controllers\Notifikasi_c;
    use DateTime;
    use DateTimeZone;
    use App\Http\Controllers\Uploads_c;

    class Reimbursement_c extends Controller{
        public $path;

        public function __construct(){
            $this->path = storage_path('app/public/images');
            $this->column_setting_approval = [
                array(
                    'column_approval' => 'apv_spv',
                    'column_user_approval' => 'user_spv',
                    'column_tgl_approval' => 'tgl_apv_spv',
                    'column_ket_rev'      => 'ket_rev_spv',
                    'column_ket_tolak'      => 'ket_tolak_spv',
                    'level_user'          => '7'
                ), 
                array(
                    'column_approval' => 'apv_kedep',
                    'column_user_approval' => 'user_kedep',
                    'column_tgl_approval' => 'tgl_apv_kedep',
                    'column_ket_rev'      => 'ket_rev_kedep',
                    'column_ket_tolak'      => 'ket_tolak_kedep',
                    'level_user'          => '2'
                ), 
                array(
                    'column_approval' => 'apv_kacab',
                    'column_user_approval' => 'user_kacab',
                    'column_tgl_approval' => 'tgl_apv_kacab',
                    'column_ket_rev'      => 'ket_rev_kacab',
                    'column_ket_tolak'      => 'ket_tolak_kacab',
                    'level_user'          => '5'
                ), 
                array(
                    'column_approval' => 'apv_finance',
                    'column_user_approval' => 'user_finance',
                    'column_tgl_approval' => 'tgl_apv_finance',
                    'column_ket_rev'      => 'ket_rev_finance',
                    'column_ket_tolak'      => 'ket_tolak_finance',
                    'level_user'          => '6'
                ), 
                array(
                    'column_approval' => 'apv_direksi',
                    'column_user_approval' => 'user_direksi',
                    'column_tgl_approval' => 'tgl_apv_direksi',
                    'column_ket_rev'      => 'ket_rev_direksi',
                    'column_ket_tolak'      => 'ket_tolak_direksi',
                    'level_user'          => '4'
                ), 
            ];

            $this->convertIdStatusToNamaStatus = array(
                '1' => 'Pengajuan',
                '2' => 'Dibatalkan',
                '3' => 'Ditolak',
                '4' => 'Direvisi',
                '5' => 'Disetujui'
            );
        }

        public function index(){
            
        }

        public function getDataReimbursement(Request $request){
            $token_fcm          = $request->get('token_fcm');
            $konteks            = $request->get('konteks');
            $filter_departemen  = $request->get('filter_departemen');
            $filter_cabang      = $request->get('filter_cabang');
            $filter_id_karyawan = $request->get('filter_id_karyawan');
            $month_year         = $request->get('month_year');
            $range_tanggal_mulai    = $request->get('range_tanggal_mulai');
            $range_tanggal_selesai  = $request->get('range_tanggal_selesai');
            $id_pengajuan           = $request->get('id_pengajuan');
            $limit                  = $request->get('limit');
            $offset                 = $request->get('offset');
            $status                 = $request->get('status');
            $data_karyawan          = Token_m::get_data_karyawan_by_token($token_fcm);

            if($data_karyawan==null){
                $response = array('success' => false, 'message' => 'Unauthorized Access');
            }
            else{
                $data_reimbursement = Reimbursement_m::getDataReimbursement(
                    $konteks,
                    $data_karyawan->id_karyawan, 
                    $data_karyawan->id_company,
                    $data_karyawan->id_cabang,
                    $data_karyawan->id_departemen,
                    $data_karyawan->level_user,
                    $filter_departemen,
                    $filter_cabang,
                    $filter_id_karyawan,
                    $month_year,
                    $range_tanggal_mulai,
                    $range_tanggal_selesai,
                    $id_pengajuan,
                    $status,
                    $limit,
                    $offset
                );

                if(count($data_reimbursement)>0){
                    $response = array(
                        'success' => true, 
                        'message' => 'Data reimbursement berhasil ditemukan',
                        'data'    => $data_reimbursement
                    );
                }
                else{
                    $response = array(
                        'success' => false, 
                        'message' => 'Data reimbursement gagal ditemukan',
                    );
                }
            }
            return response()->json($response,200);
        }

        public static function getJenisReimbursement(Request $request){
            $data_karyawan = Token_m::get_data_karyawan_by_token($request->get('token_fcm'));
            if($data_karyawan==null){
                $response = array('success' => false, 'message' => 'Unauthorized Access');
            }
            else{
                $data_jenis = Reimbursement_m::getJenisReimbursement($data_karyawan->id_company, $data_karyawan->id_cabang);
                if(count($data_jenis)>0){
                    $response = array(
                        'success' => true, 
                        'message' => 'Data jenis reimbursement berhasil ditemukan',
                        'data'    => $data_jenis
                    );
                }
                else{
                    $response = array(
                        'success' => false, 
                        'message' => 'Data jenis reimbursement gagal ditemukan',
                    );
                }
            }
            return response()->json($response,200);
        }

        public static function getDataItemTemp(Request $request){
            $data_karyawan = Token_m::get_data_karyawan_by_token($request->get('token_fcm'));
            if($data_karyawan==null)  $response = array('success' => false, 'message' => 'Unauthorized Access');
            else{
                $data_item_temp = Reimbursement_m::getDataItemTemp($data_karyawan->id_karyawan, $data_karyawan->id_company);
                if(count($data_item_temp)>0){
                    $total_pengajuan = 0;
                    foreach($data_item_temp as $row){
                        $total_pengajuan = $total_pengajuan + $row->nominal;
                        $row->tgl_bukti =  DateFormat::format($row->tgl_bukti,'d M Y');
                        $row->file = Uploads_c::cekFoto($row->file)?Uploads_c::retrieve_file_url($row->file):'-';
                        
                    }
                    $data = array(
                        'total_pengajuan' => $total_pengajuan,
                        'list_item_temp'  => $data_item_temp
                    );

                    $response = array(
                        'success' => true, 
                        'message' => 'Data item reimbursement berhasil ditemukan',
                        'data'    => $data
                    );
                }
                else{
                    $response = array(
                        'success' => false, 
                        'message' => 'Data item reimbursement gagal ditemukan',
                    );
                }
            }
            return response()->json($response,200);
        }

        public static function tambahItemTemp(Request $request){
            $id_jenis_reimbursement = $request->input('id_jenis_reimbursement');
            $nominal                = $request->input('nominal');
            $tgl_nota               = $request->input('tgl_nota');
            $keterangan             = $request->input('keterangan');
            $image                   = $request->input('image');
            $data_karyawan = Token_m::get_data_karyawan_by_token($request->input('token_fcm'));

            if($data_karyawan==null) $response = array('success' => false, 'message' => 'Unauthorized Access');
            else{
                $id_company = $data_karyawan->id_company;
                $id_departemen = $data_karyawan->id_departemen;
                $id_cabang = $data_karyawan->id_cabang;
                $id_karyawan = $data_karyawan->id_karyawan;
                $id = Reimbursement_m::getId($id_company, 'reimbursement_temp');

                // $imageName = $id_karyawan.date('YmdHis').".jpg";
                // $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                
                // $mkdir = "reimbursement/".date("Ym").'/'.$id_company;
                // if(!is_dir(base_path("../web/public/".$mkdir))){ 
                //     mkdir(base_path("../web/public/".$mkdir),0777,TRUE); 
                // }
                // file_put_contents(base_path("../web/public/".$mkdir."/".$imageName), $imageData);
                // $alamat_foto = '/public/'.$mkdir."/".$imageName;

                $insert = Reimbursement_m::tambahItemTemp(
                    array(
                        'id'                        => $id,
                        'id_karyawan'               => $id_karyawan,
                        'id_company'                => $id_company,
                        'id_jenis_reimbursement'    => $id_jenis_reimbursement,
                        'nominal'                   => $nominal,
                        'keterangan'                => $keterangan,
                        'tgl_bukti'                 => $tgl_nota,
                        'file'                      => Uploads_c::upload_file(
                            $image, 
                            $id_company."/reimbursement/".date("Ym"),
                            $id_karyawan.date('YmdHis').".jpg"
                        )
                    )
                );
                if($insert){
                    $response = array(
                        'success'=>true,
                        'message'=>'Berhasil menambahkan item reimbursement');
                }else{
                    $response = array(
                        'success'=>false,
                        'message'=>'Gagal menambahkan item reimbursement');
                }
            }
            return response()->json($response,200);
        }

        public static function hapusItemTemp($token_fcm, $id){
            $data_karyawan = Token_m::get_data_karyawan_by_token($token_fcm);
            if($data_karyawan==null)
                $response = array('success' => false, 'message' => 'Unauthorized Access');
            else{
                if(Reimbursement_m::hapusItemTemp($id)) $response = array(
                    'success' => true, 
                    'message' => 'Item reimbursement berhasil dihapus',
                );
                else $response = array(
                        'success' => false, 
                        'message' => 'Item reimbursement gagal dihapus',
                );
            }
            return response()->json($response,200);
        }

        public static function mapping_approval_dan_notifikasi($data_p_approval, $id_company, $id_cabang, $id_departemen){
            $data_penerima_notifikasi = array();
            $data_master_jabatan = User_m::get_master_jabatan($data_p_approval, $id_company, $id_departemen); //GET LEVEL USER YG MENGAPROVE
            $mapping = array();
            foreach (explode(',',$data_p_approval) as $row) {
                $detail_mapping = array();
                if ($row=='2') {
                    if($data_penerima_notifikasi==null)
                        $data_penerima_notifikasi = User_m::get_data_user_by_where(
                            [
                                'usergroup.id' => $row,
                                'users.id_company' => $id_company,
                                'data_karyawan.id_departemen' => $id_departemen,
                            ]);
                    $detail_mapping['column_approval'] = 'apv_kedep';
                }
                else if ($row=='4') {
                if($data_penerima_notifikasi==null)
                        $data_penerima_notifikasi = User_m::get_data_user_by_where(
                            [
                                'usergroup.id' => $row,
                                'users.id_company' => $id_company,
                            ]);
                    $detail_mapping['column_approval'] = 'apv_direksi';
                }
                else if ($row=='5') {
                    if($data_penerima_notifikasi==null)
                        $data_penerima_notifikasi = User_m::get_data_user_by_where(
                            [
                                'usergroup.id' => $row,
                                'users.id_company' => $id_company,
                                'data_karyawan.id_cabang' => $id_cabang,
                            ]);
                    $detail_mapping['column_approval'] = 'apv_kacab';
                }
                else if ($row=='6') {
                    if($data_penerima_notifikasi==null)
                        $data_penerima_notifikasi = User_m::get_data_user_by_where(
                            [
                                'usergroup.id' => $row,
                                'users.id_company' => $id_company,
                                'data_karyawan.id_cabang' => $id_cabang,
                            ]);
                    $detail_mapping['column_approval'] = 'apv_finance';
                }
                else if ($row=='7') {
                    if($data_penerima_notifikasi==null)
                    $data_penerima_notifikasi = User_m::get_data_user_by_where(
                        [
                            'usergroup.id' => $row,
                            'users.id_company' => $id_company,
                            'data_karyawan.id_departemen' => $id_departemen,
                        ]);
                    $detail_mapping['column_approval'] = 'apv_spv';
                }
                $mapping[] = $detail_mapping;
            }
            $index=0;
            foreach ($mapping as $row) {
                if($index==0) {
                    $data_mapping[$row['column_approval']] = '1';
                }
                else {
                    $data_mapping[$row['column_approval']] = '6';
                }
                $index++;
            }
            return array('data_mapping' => $data_mapping, 'data_penerima_notifikasi' => $data_penerima_notifikasi);
        }

        public static function pengajuanReimbursement(Request $request){
            $data_karyawan = Token_m::get_data_karyawan_by_token($request->input('token_fcm'));
            if($data_karyawan==null)
                $response = array('success' => false, 'message' => 'Unauthorized Access');
            else{
                $id_company = $data_karyawan->id_company;
                $id_departemen = $data_karyawan->id_departemen;
                $id_cabang = $data_karyawan->id_cabang;
                $id_karyawan = $data_karyawan->id_karyawan;
                $level_user = $data_karyawan->level_user;
                $id_pengajuan = Reimbursement_m::getId($id_company, 'reimbursement_pengajuan');
                $no_pengajuan = Reimbursement_m::getNomorPengajuan($id_company);
                $data_mapping = array();
                $data_penerima_notifikasi = array();

                //CEK P_APPROVAL_REIMBURSEMENT
                $data_p_approval = Reimbursement_m::get_p_approval($level_user, $id_company, $id_cabang);
                if($data_p_approval==null) $response = array('success'=>false, 'message'=>'Pengaturan Reimbursement belum dibuat, silahkan hubungi atasan Anda');
                else{
                    $data_mapping_dan_notifikasi = $data_mapping = self::mapping_approval_dan_notifikasi($data_p_approval, $id_company, $id_cabang, $id_departemen);
                    $data_mapping = $data_mapping_dan_notifikasi['data_mapping'];
                    $data_penerima_notifikasi = $data_mapping_dan_notifikasi['data_penerima_notifikasi'];
                }

                $data_insert_pengajuan = array(
                    'id'            => $id_pengajuan,
                    'no_pengajuan'  => $no_pengajuan,
                    'tgl_pengajuan' => date('Y-m-d H:i:s'),
                    'id_karyawan'   => $id_karyawan,
                    'id_departemen' => $id_departemen,
                    'id_cabang'     => $id_cabang,
                    'id_company'    => $id_company
                );

                $data_insert_pengajuan = array_merge($data_insert_pengajuan, $data_mapping);
                
                //GET DATA TEMP
                $data_item_temp = Reimbursement_m::getDataItemTemp($id_karyawan, $id_company);
                if(count($data_item_temp)==0){
                    $response = array('success' => false, 'message' => 'Item pengajuan belum diisi');
                }
                else{
                    $insert_pengajuan = Reimbursement_m::insertPengajuan($data_insert_pengajuan);
                    if($insert_pengajuan){
                        foreach($data_item_temp as $row){
                            $data_insert_item = array(
                                'id'    => Reimbursement_m::getId($id_company, 'reimbursement_data'),
                                'id_pengajuan_reimbursement'    => $id_pengajuan,
                                'id_karyawan'                   => $id_karyawan,
                                'id_jenis_reimbursement'        => $row->id_jenis_reimbursement,
                                'nominal'                       => $row->nominal,
                                'nominal_disetujui'             => 0 ,
                                'keterangan'                    => $row->keterangan,
                                'tgl_bukti'                     => $row->tgl_bukti,
                                'file'                          => $row->file,
                                'id_company'                    => $id_company
                            );
                            $data_insert_item = array_merge($data_insert_item, $data_mapping);
                            $insert_item = Reimbursement_m::insertItemPengajuan($data_insert_item);
                            if($insert_item){
                                Reimbursement_m::hapusItemTemp($row->id);
                            }
                        }
                        //SEND NOTIF
                        foreach ($data_penerima_notifikasi as $row_penerima) {
                            //NOTIFIKASI KHUSUS 07062021
                            if($row_penerima->level_user=='1'){
                                Notifikasi_c::notifikasi_khusus($id_company, 'notif_hrd', 'Pengajuan Reimbursement', User_m::get_data_karyawan($id_karyawan)->nama_lengkap.' mengajukan reimbursement');
                            }
                            else if($row_penerima->level_user=='6'){
                                Notifikasi_c::notifikasi_khusus($id_company, 'notif_finance', 'Pengajuan Reimbursement', User_m::get_data_karyawan($id_karyawan)->nama_lengkap.' mengajukan reimbursement');
                            }
                            //

                            Notifikasi_c::send_fcm($row_penerima->token_fcm, 'Pengajuan Reimbursement', User_m::get_data_karyawan($id_karyawan)->nama_lengkap.' mengajukan reimbursement', '/detail_reimbursement_pegawai', $id_pengajuan);
                        }
                        $response = array('success' => true, 'message' => 'Pengajuan reimbursement berhasil');
                    }
                    else{
                        $response = array('success' => false, 'message' => 'Pengajuan reimbursement gagal');
                    }
                }
            }
            return response()->json($response,200);
        }

        public function updateStatusPengajuan(Request $request){
            $data_karyawan = Token_m::get_data_karyawan_by_token($request->input('token_fcm'));
            if($data_karyawan==null)
                $response = array('success' => false, 'message' => 'Unauthorized Access');
            else{
                $id_pengajuan = $request->input('id_pengajuan');
                $id_company = $data_karyawan->id_company;
                $id_departemen = $data_karyawan->id_departemen;
                $id_cabang = $data_karyawan->id_cabang;
                $id_karyawan = $data_karyawan->id_karyawan;
                $level_user = $data_karyawan->level_user;
                $jml_item = $request->input('jumlah_item');
                $revisi_pengajuan = false;
                $jml_tolak = 0;
                $data_reimbursement = Reimbursement_m::getDataReimbursementById($id_pengajuan);

                for($i=0;$i<$jml_item;$i++){//UPDATE PER ITEM MENURUT JUMLAH
                    $id_item    = $request->input('id_item'.$i);
                    $status     = $request->input('status'.$i);
                    $nominal     = $request->input('nominal'.$i);
                    $keterangan     = $request->input('keterangan'.$i);

                    if($status=='4') $revisi_pengajuan = true; //JIKA TERDAPAT REVISI PADA ITEM
                    if($status=='3') $jml_tolak++;

                    foreach ($this->column_setting_approval as $row) {
                        if(Reimbursement_m::cek_approval_per_column('reimbursement_data', ['id' => $id_item, $row['column_approval'] => '1'])>0){
                            $data_update_item = array(
                                $row['column_approval'] => $status,
                                $row['column_tgl_approval'] => date("Y-m-d H:i:s"),
                                $row['column_user_approval'] => $id_karyawan
                            );
                            if($status=='3') {
                                $data_update_item[$row['column_ket_tolak']] = $keterangan;
                                $data_update_item['nominal_disetujui'] = null;
                            } //JIKA ITEM DITOLAK
                            if($status=='4') $data_update_item[$row['column_ket_rev']] = $keterangan; //JIKA ITEM DIREVISI
                            if($status=='5') $data_update_item['nominal_disetujui'] = $nominal; //JIKA ITEM DISETUJUI
                            
                            $update = Reimbursement_m::updateStatusItemPengajuan($data_update_item, $id_item);
                            break;
                        }
                        
                    }
                    if ($update) {
                        $index = 0;
                         foreach ($this->column_setting_approval as $row) {
                            $index++;
                            if($status!='2' && $status!='3' && $status!='4' 
                            && Reimbursement_m::cek_approval_per_column('reimbursement_data', ['id' => $id_item, $row['column_approval'] => '6'])>0){
                                Reimbursement_m::updateStatusItemPengajuan([$row['column_approval'] => '1'], $id_item);
                                break;
                            }
                        }
                     }
                }

                

                //STATUS PENGAJUAN
                if(!$revisi_pengajuan && $jml_tolak<$jml_item) $status_pengajuan = '5';//JIKA TIDAK ADA REVISI PADA ITEM REIMBURSEMENT
                else if($jml_tolak==$jml_item) $status_pengajuan = '3';//JIKA DITOLAK SEMUA
                else if($revisi_pengajuan)$status_pengajuan = '4';//REVISI
                //


                foreach ($this->column_setting_approval as $row) {
                    //SEND NOTIF BAWAHAN YG TERKAIT
                    if(Reimbursement_m::cek_approval_per_column('reimbursement_pengajuan', ['id' => $id_pengajuan, $row['column_approval'] => '5'])>0){
                        $data_penerima_notifikasi = Notifikasi_c::GetBawahanPenerimaNotifikasi($row['level_user'], $id_company, $data_reimbursement->id_cabang, $data_reimbursement->id_departemen, $data_reimbursement->user_spv);
                        foreach ($data_penerima_notifikasi as $row_penerima) {
                             Notifikasi_c::send_fcm($row_penerima->token_fcm, 'Pengajuan Reimbursement', 'Pengajuan Reimbursement '.User_m::get_data_karyawan($data_reimbursement->id_karyawan)->nama_lengkap.' telah '.$this->convertIdStatusToNamaStatus[$status_pengajuan].' oleh '.User_m::get_data_karyawan($id_karyawan)->nama_lengkap, '/detail_reimbursement_pegawai', $id_pengajuan);
                        }
                    }
                    //
                    if(Reimbursement_m::cek_approval_per_column('reimbursement_pengajuan', ['id' => $id_pengajuan, $row['column_approval'] => '1'])>0){
                        $update = Reimbursement_m::updateStatusPengajuan(
                            [
                                $row['column_approval'] => $status_pengajuan,
                                $row['column_tgl_approval'] => date("Y-m-d H:i:s"),
                                $row['column_user_approval'] => $id_karyawan
                            ]
                            , $id_pengajuan);
                        break;
                    }
                }
                if ($update) {
                    //SEND NOTIF PEMBUAT REIMBURSEMENT
                    Notifikasi_c::send_fcm(User_m::get_data_karyawan($data_reimbursement->id_karyawan)->token_fcm, 'Pengajuan Reimbursement','Pengajuan Reimbursementmu telah '.$this->convertIdStatusToNamaStatus[$status_pengajuan].' oleh '.User_m::get_data_karyawan($id_karyawan)->nama_lengkap, '/detail_reimbursement_saya', $id_pengajuan);
                    //
                    $index = 0;
                    foreach ($this->column_setting_approval as $row) {
                        $index++;
                        if($status_pengajuan!='2' && $status_pengajuan!='3' && $status_pengajuan!='4' 
                        && Reimbursement_m::cek_approval_per_column('reimbursement_pengajuan', ['id' => $id_pengajuan, $row['column_approval'] => '6'])>0){
                            //SEND NOTIF ATASAN
                            $data_penerima_notifikasi = Notifikasi_c::GetAtasanPenerimaNotifikasi($row['level_user'], $id_company, $data_reimbursement->id_cabang, $data_reimbursement->id_departemen);
                            foreach ($data_penerima_notifikasi as $row_penerima) {
                                //NOTIFIKASI KHUSUS 07062021
                                if($row_penerima->level_user=='1'){
                                    Notifikasi_c::notifikasi_khusus($id_company, 'notif_hrd', 'Pengajuan Reimbursement', User_m::get_data_karyawan($data_reimbursement->id_karyawan)->nama_lengkap.' mengajukan reimbursement');
                                }
                                else if($row_penerima->level_user=='6'){
                                    Notifikasi_c::notifikasi_khusus($id_company, 'notif_finance', 'Pengajuan Reimbursement', User_m::get_data_karyawan($data_reimbursement->id_karyawan)->nama_lengkap.' mengajukan reimbursement');
                                }
                                //
                                 Notifikasi_c::send_fcm($row_penerima->token_fcm, 'Pengajuan Reimbursement', User_m::get_data_karyawan($data_reimbursement->id_karyawan)->nama_lengkap.' mengajukan reimbursement', '/detail_reimbursement_pegawai', $id_pengajuan);
                            }
                            //
                            Reimbursement_m::updateStatusPengajuan([$row['column_approval'] => '1'], $id_pengajuan);
                            break;
                        }
                        if($index==count($this->column_setting_approval)) {
                            Reimbursement_m::updateStatusPengajuan(['status' => $status_pengajuan], $id_pengajuan);
                        }
                    }
                    if($status_pengajuan=='3' || $status_pengajuan=='4'){
                        Reimbursement_m::updateStatusPengajuan(['status' => $status_pengajuan], $id_pengajuan);
                    }
                 }

                $response = array('success' => true, 'message' => 'Reimbursement berhasil diproses');
                   
            }
            return response()->json($response,200);
            
        }

        public function updateRevisiReimbursement(Request $request){
            $data_karyawan = Token_m::get_data_karyawan_by_token($request->input('token_fcm'));
            if($data_karyawan==null)
                $response = array('success' => false, 'message' => 'Unauthorized Access');
            else{
                $id_pengajuan = $request->input('id_pengajuan');
                $id_company = $data_karyawan->id_company;
                $id_departemen = $data_karyawan->id_departemen;
                $id_cabang = $data_karyawan->id_cabang;
                $id_karyawan = $data_karyawan->id_karyawan;
                $level_user = $data_karyawan->level_user;
                $jml_item = $request->input('jumlah_item');

                for($i=0;$i<$jml_item;$i++){//UPDATE PER ITEM MENURUT JUMLAH
                    $id_item    = $request->input('id_item'.$i);
                    $jenis      = $request->input('jenis'.$i);
                    $tgl_nota   = $request->input('tgl_nota'.$i);
                    $nominal     = $request->input('nominal'.$i);
                    $keterangan  = $request->input('keterangan'.$i);
                    $image        = $request->input('file'.$i);

                    $imageName = $id_karyawan.date('YmdHis').".jpg";
                    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                    
                    $mkdir = "reimbursement/".date("Ym").'/'.$id_company;
                    if(!is_dir(base_path("../web/public/".$mkdir))){ 
                        mkdir(base_path("../web/public/".$mkdir),0777,TRUE); 
                    }
                    file_put_contents(base_path("../web/public/".$mkdir."/".$imageName), $imageData);
                    $alamat_foto = '/public/'.$mkdir."/".$imageName;

                    foreach ($this->column_setting_approval as $row) {
                        if(Reimbursement_m::cek_approval_per_column('reimbursement_data', ['id' => $id_item, $row['column_approval'] => '4'])>0){
                            //SEND NOTIF ATASAN YG MEMINTA REVISI
                            $data_penerima_notifikasi = Notifikasi_c::GetAtasanPenerimaNotifikasi($row['level_user'], $id_company, $id_cabang, $id_departemen);
                            foreach ($data_penerima_notifikasi as $row_penerima) {
                                 Notifikasi_c::send_fcm($row_penerima->token_fcm, 'Revisi Pengajuan Reimbursement', User_m::get_data_karyawan($id_karyawan)->nama_lengkap.' merevisi pengajuan reimbursement', '/detail_reimbursement_pegawai', $id_pengajuan);
                            }
                            //
                            $data_update_item = array(
                                $row['column_approval'] => '1',
                                'id_jenis_reimbursement' => $jenis,
                                'tgl_bukti' => $tgl_nota,
                                'nominal'   => $nominal,
                                'keterangan' => $keterangan,
                                'file'      => $alamat_foto
                            );
                            
                            $update = Reimbursement_m::updateStatusItemPengajuan($data_update_item, $id_item);
                            break;
                        }
                    }
                }

                foreach ($this->column_setting_approval as $row) {
                    if(Reimbursement_m::cek_approval_per_column('reimbursement_pengajuan', ['id' => $id_pengajuan, $row['column_approval'] => '4'])>0){
                        $update = Reimbursement_m::updateStatusPengajuan(
                            [
                                $row['column_approval'] => '1',
                                'status' => '1'
                            ]
                            , $id_pengajuan);
                        break;
                    }
                }
                $response = array('success' => true, 'message' => 'Revisi reimbursement berhasil disubmit');
                   
            }
            return response()->json($response,200);
            
        }
    }