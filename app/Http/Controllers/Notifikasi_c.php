<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\Berita_m;
    use App\Models\User_m;
    use App\Models\Notifikasi_m;
    use App\Http\Helpers\DateFormat;

    class Notifikasi_c extends Controller{

        public function index(){
            
        }

        //ROUTE INFORMATION 
        // /detail_izin_pegawai: DETAIL IZIN (PENGAJUAN) PARAM ID_REF = ID_IZIN
        // /detail_izin_saya: DETAIL IZIN (SETUJUI) PARAM ID_REF = ID_IZIN
        // /detail_izin_saya: DETAIL IZIN (TOLAK) PARAM ID_REF = ID_IZIN
        // /detail_lembur_pegawai: DETAIL LEMBUR (PENGAJUAN) PARAM ID_REF = ID_LEMMULAI
        // /detail_lembur_saya: DETAIL LEMBUR (SETUJUI) PARAM ID_REF = ID_LEMMULAI
        // /detail_lembur_saya: DETAIL LEMBUR (TOLAK) PARAM ID_REF = ID_LEMMULAI
        // /detail_berita: DETAIL BERITA PARAM ID_REF = ID_BERITA
        // /detail_pengumuman: DETAIL PENGUMUMAN  PARAM ID_REF = ID_PENGUMUMAN
        //END ROUTE INFORMATION

        /*private function konten_notifikasi($jenis, $pengirim){
            if($jenis=='1') return array('title' => 'Pengajuan Izin', 'body'  => 'Izin anda telah disetujui oleh '.$pengirim);
            else if($jenis=='2') return array('title' => 'Pengajuan Izin', 'body'  => 'Izin anda telah disetujui oleh '.$pengirim);
            else if($jenis=='3') return array('title' => 'Pengajuan Izin', 'body'  => 'Izin anda telah disetujui oleh '.$pengirim);
            else if($jenis=='4') return array('title' => 'Pengajuan Izin', 'body'  => 'Izin anda telah disetujui oleh '.$pengirim);
            else if($jenis=='5') return array('title' => 'Pengajuan Izin', 'body'  => 'Izin anda telah disetujui oleh '.$pengirim);
            else if($jenis=='6') return array('title' => 'Pengajuan Izin', 'body'  => 'Izin anda telah disetujui oleh '.$pengirim);
            else if($jenis=='7') return array('title' => 'Pengajuan Izin', 'body'  => 'Izin anda telah disetujui oleh '.$pengirim);
            else if($jenis=='8') return array('title' => 'Pengajuan Izin', 'body'  => 'Izin anda telah disetujui oleh '.$pengirim);
            
        }*/

        public static function notifikasi_khusus($id_company, $select, $title, $body){
            $data_penerima_notif = Notifikasi_m::notifikasi_khusus($id_company, $select);
            foreach($data_penerima_notif as $row){
                self::send_fcm($row->token_fcm, $title, $body, '/notif_khusus', url('../web/login'));
            }
        }

        public static function GetBawahanPenerimaNotifikasi($level_user, $id_company, $id_cabang, $id_departemen, $id_karyawan_spv){
            $data_penerima_notifikasi = array();
            if($level_user=='7'){
                $data_penerima_notifikasi = User_m::get_data_user_by_where(['data_karyawan.id_karyawan' => $id_karyawan_spv]);
            }
            else if($level_user=='5'){
                $data_penerima_notifikasi = User_m::get_data_user_by_where(['usergroup.id' => $level_user, 'data_karyawan.id_cabang' => $id_cabang]);
            }
            else if($level_user=='4' || $level_user=='1'){
                $data_penerima_notifikasi = User_m::get_data_user_by_where(['usergroup.id' => $level_user, 'data_karyawan.id_company' => $id_company]);
            }
            else if($level_user=='2'){
                $data_penerima_notifikasi = User_m::get_data_user_by_where(['usergroup.id' => $level_user, 'data_karyawan.id_departemen' => $id_departemen]);
            }
            return $data_penerima_notifikasi;
        }

        public static function GetAtasanPenerimaNotifikasi($level_user, $id_company, $id_cabang, $id_departemen){
            $data_penerima_notifikasi = array();
            if($level_user=='7'){
                $data_penerima_notifikasi = User_m::get_data_user_by_where(
                    [
                        'usergroup.id' => $level_user,
                        'users.id_company' => $id_company,
                        'data_karyawan.id_departemen' => $id_departemen,
                    ]);    
            }
            else if($level_user=='5' || $level_user=='6'){
                if($data_penerima_notifikasi==null)
                        $data_penerima_notifikasi = User_m::get_data_user_by_where(
                            [
                                'usergroup.id' => $level_user,
                                'users.id_company' => $id_company,
                                'data_karyawan.id_cabang' => $id_cabang,
                            ]);
            }
            else if($level_user=='4' || $level_user=='1'){
                $data_penerima_notifikasi = User_m::get_data_user_by_where(
                    [
                        'usergroup.id' => $level_user,
                        'users.id_company' => $id_company,
                    ]);
            }
            else if($level_user=='2'){
                $data_penerima_notifikasi = User_m::get_data_user_by_where(
                    [
                        'usergroup.id' => $level_user,
                        'users.id_company' => $id_company,
                        'data_karyawan.id_departemen' => $id_departemen,
                    ]);
            }
            return $data_penerima_notifikasi;
        }

        public function reminder_kontrak(Request $request){
            $data = Notifikasi_m::reminder_kontrak();
            $title = "Kontrak Berakhir";
            $body = "Masa kontrak anda akan berakhir pada tanggal";
            $route = '/akun';
            $id_ref = '1';
            if(count($data) > 0){
                foreach($data as $rows){
                    self::send_fcm($rows->token_fcm, $title, $body." ".DateFormat::format($rows->tgl_berhenti_bekerja,"N d M Y"), $route, $id_ref);
                }
                echo "Ada data ".count($data);
            }
        }

        public function send_fcm_web(Request $request){
            $token_fcm = $request->get('token_fcm');
            $route = $request->get('route');
            $title = $request->get('title');
            $body = $request->get('body');
            $id_ref = $request->get('id_ref');
            self::send_fcm($token_fcm, $title, $body, $route, $id_ref);
        }

        public static function send_fcm($token_fcm, $title, $body, $route, $id_ref){
            $payload_member = array(
                "to" => $token_fcm, 
                "notification" => array( 
                    "title" => $title, 
                    "body" => $body,
                    "click_action" => "FLUTTER_NOTIFICATION_CLICK"),
                "data"=> array(
                    "message" => "This is a Firebase Cloud Messaging Topic Message!",
                    "route" => $route,
                    "id_ref" => $id_ref,
                    "title" => $title, 
                    "body" => $body,
                ) 
            );
            return self::curl_send_fcm($payload_member);
        }

        private static function curl_send_fcm($data){
            $data = json_encode($data);
            $url = 'https://fcm.googleapis.com/fcm/send';
            //api_key in Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key
            $server_key = env('API_KEY');
            //header with content_type api key
            $headers = array(
                'Content-Type:application/json',
                'Authorization:key='.$server_key
            );
            //CURL request to route notification to FCM connection server (provided by Google)
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $result = curl_exec($ch);
            if ($result === FALSE) {
                die('Oops! FCM Send Error: ' . curl_error($ch));
            }
            curl_close($ch);
            return $result;
        }

        public static function send_web_notif($prm = [])
        {
            $prm['web_key']            = '123';
            $in = json_encode($prm);

            $web_param = strtr(base64_encode($in), '+/=', '._-');

            $url     = 'https://kasyasindo.absenku.com/web/';
            $url    .= 'api/notif/receive/';

            $curl = curl_init();

            curl_setopt_array($curl, array(
              CURLOPT_URL => $url.$web_param,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);
            
            curl_close($curl);
        }
    }