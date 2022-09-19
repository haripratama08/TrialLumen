<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Http\Response;
    use App\Http\Helpers\DateFormat;
    use App\Models\Token_m;
    use URL;
    use App\Http\Helpers\Convertion;
    use App\Http\Controllers\Uploads_c;

    class User_m extends Model{

        private static function checkCompany($id_company = null){
            $status_company = DB::table('master_company')
                            ->select('id_company')
                            ->where('id_company', '=', ''.$id_company.'')
                            ->where('flag', '=', '1')
                            ->count();
            if($status_company > 0){
                return true;
            }else{
                $response = array('success'=>false,
                                'message'=>'Perusahaan Anda sudah tidak aktif di Aplikasi Absenku');
                return $response;
            }
        }

        private static function checkMasaLayanan($id_company = null){
            $select_master_plan = "SELECT id_plan 
                                    FROM master_plan 
                                    WHERE id_company='$id_company'
                                    AND DATE_FORMAT(tgl_berakhir,'%Y-%m-%d') >= DATE_FORMAT(NOW(),'%Y-%m-%d')";
            $masa_layanan = DB::select($select_master_plan);

            if(count($masa_layanan)){
                return true;
            }else{
                $response = array('success'=>false,
                                'message'=>'Masa layanan Anda sudah habis');
                return $response;
            }
        }

        protected static function checkUsername($username = null){
            $select = collect(DB::select("SELECT username FROM users WHERE username = '$username'"));
            if($select->count() > 0){
                return true;
            }else{
                $response = array('success'=>false,
                                    'message'=>'Username Anda tidak terdaftar');

                return response()->json($response, 401);
            }
        }

        protected static function checkPassword($username = null, $password = null){
            $select = collect(DB::select("SELECT id_user
                                            FROM users
                                            WHERE username = '$username'
                                            AND password = '$password'"));
            if($select->count() > 0){
                return true;
            }else{
                $response = array('success'=>false,
                                    'message'=>'Password Anda salah');

                return response()->json($response, 401);
            }
        }



        public static function checkUserActive ($id_company = null, $id_karyawan = null, $token_user = null){
            $checkCompany = User_m::checkCompany($id_company);
            if($checkCompany === true){
                $checkMasaLayanan = User_m::checkMasaLayanan($id_company);
                if($checkMasaLayanan === true ){
                    $select_user = "SELECT id_user,
                                            token
                                    FROM users
                                    WHERE id_karyawan = '$id_karyawan'
                                    AND id_company ='$id_company'
                                    AND flag = '1'";
                    $data_user = collect(DB::select($select_user));
                    
                    if(count($data_user)){
                        $data_user = $data_user->first();

                        if($token_user !== $data_user->token){
                            $response = array('success'=>true,
                                            'message'=>'User terverfikasi');
                        }else{
                            $response = array('success'=>false,
                                            'message'=>'Akun Anda sedang aktif diperangkat lain, silahkan login ulang');
                        }
                    }else{
                        $response = array('success'=>false,
                                        'message'=>'Akun Anda sudah tidak aktif');
                    }
                }else{
                    $response = $checkMasaLayanan;
                }
                
            }else{
                $response = $checkCompany;
            }

            return response()->json($response);
        }

        private static function cekDeviceID($id_karyawan, $id_company, $device_id){
            $lock_device_users = DB::table('users')->select('lock_device')
            ->where('id_karyawan', $id_karyawan)->first()->lock_device;
            if($lock_device_users==1) $cek_device = true;
            else $cek_device = false;
            if ($cek_device) {
                $cek_device_id = DB::table('users')->where(
                    array(
                        'id_karyawan' => $id_karyawan,
                        'id_company'  => $id_company,
                    )
                )
                ->where(function ($query) use($device_id) {
                    $query->where('device_id', $device_id) 
                          ->orWhere('device_id', '');
                })->count();
                if ($cek_device_id>0) return true;
                else return array('success'=>false,
                                'message'=>'Akun ini sudah digunakan pada perangkat(HP) lain, silahkan hubungi bagian HR jika akan ganti perangkat(HP)');
            }
            else return true;
        }

        public static function login($data = array()){
            $username = $data['username'];
            $password = $data['password'];
            $token_fcm = $data['token_fcm'];
            
            if($username=='' || $password==''){
                if($username =='' && $password =='')
                    $response = array(
                        'success'=>false,
                        'message'=>'Username dan Password tidak boleh kosong'
                    );
                else if($username=='')
                    $response = array(
                        'success'=>false,
                        'message'=>'Username tidak boleh kosong'
                    );
                else 
                    $response = array(
                        'success'=>false,
                        'message'=>'Password tidak boleh kosong'
                    );
            }
            else{
                $password2 = $password;
                $password = SHA1(SHA1(MD5($password)));
                $cek_username = DB::table('users')->where('username', $username)->get();
                // $cek_username = DB::select("SELECT username FROM users WHERE username = '$username'");
                if(count($cek_username) > 0 ){
                    DB::enableQueryLog();
                    $cek_password = DB::table('users')
                                    ->where('username', $username)
                                    ->whereRaw('(password = ? OR password_2 = ?)', [$password, $password2])
                                    ->get();
                    // print_r(DB::getQueryLog());exit;
                    // $cek_password = collect(DB::select("SELECT id_user, 
                    //                                     id_company, id_karyawan
                    //                             FROM users
                    //                             WHERE username = '$username'
                    //                             AND password = '$password'"));
                    if(count($cek_password) > 0){
                        $id_company = $cek_password->first()->id_company;
                        $id_karyawan = $cek_password->first()->id_karyawan;
                        $checkDeviceID = self::cekDeviceID($id_karyawan, $id_company, $data['device_id']);
                        if ($checkDeviceID === true) {
                            $checkCompany = User_m::checkCompany($id_company);
                            if($checkCompany === true){
                            $checkMasaLayanan = User_m::checkMasaLayanan($id_company);
                            if($checkMasaLayanan === true){
                                // echo 'df';exit;
                                $cekStatusKaryawan = User_m::cekStatusKaryawan($id_company,$id_karyawan);
                                if ($cekStatusKaryawan['success']) {
                                    $select_user = collect(DB::select("SELECT users.id_user,
                                                                            users.id_karyawan,
                                                                                users.username,
                                                                                data_karyawan.nama_lengkap as nama_user,
                                                                                users.token_fcm,
                                                                                data_karyawan.id_cabang,
                                                                                data_karyawan.id_departemen,
                                                                                mj.nama as nama_jabatan,
                                                                                mj.level_user,
                                                                                IF(users.lock_facerecognition='1', TRUE, FALSE) as lock_facerecognition,
                                                                                users.matrix_facerecognition as face_data,
                                                                                users.foto_facerecognition as foto_face,
                                                                                users.kamera,
                                                                                users.id_company,
                                                                                data_karyawan.alamat_domisili as alamat,
                                                                            md.nama as nama_departemen,
                                                                            data_karyawan.telepon,
                                                                            data_karyawan.email,
                                                                            data_karyawan.foto,
                                                                            tp.komponen_mobile,
                                                                            mcb.flag as kantor_pusat
                                                                    FROM users
                                                                    LEFT JOIN data_karyawan ON data_karyawan.id_karyawan = users.id_karyawan
                                                                            AND data_karyawan.id_company = users.id_company
                                                                    LEFT JOIN master_departemen md ON md.id_departemen = data_karyawan.id_departemen
                                                                    LEFT JOIN master_jabatan mj ON mj.id_jabatan = data_karyawan.id_jabatan 
                                                                    LEFT JOIN master_company mc ON mc.id_company = data_karyawan.id_company
                                                                    LEFT JOIN master_cabang mcb ON mcb.id_cabang = data_karyawan.id_cabang
                                                                    LEFT JOIN tb_paket tp ON tp.id_tb_paket = mc.id_tb_paket
                                                                    WHERE users.username = '$username'
                                                                    AND (users.password = '$password' OR users.password_2 = '$password2')"));
                                
                                    if(count($select_user) > 0){
                                        // echo 'df';exit;
                                        $rows = $select_user->first();
                                        $admin_boleh_login = true;
                                        if($rows->level_user=='1' && !$admin_boleh_login){
                                            $response = array('success'=>false,
                                            'message'=>'Level Admin HR tidak diperbolehkan login di mobile');
                                        }
                                        else if($rows->level_user=='6' && !$admin_boleh_login){
                                            $response = array('success'=>false,
                                            'message'=>'Level Admin Finance tidak diperbolehkan login di mobile');
                                        }
                                        else{
                                            if($token_fcm != $rows->token_fcm){
                                                //UPDATE TOKEN && DEVICE ID
                                                $update = DB::table('users')
                                                            ->where('id_user', $rows->id_user)
                                                            ->update([
                                                                'token_fcm' => $token_fcm, 
                                                                'device_id' => $data['device_id'],
                                                                'login' => '1'
                                                            ]);
                                            }
            
                                            $rows = collect($select_user)->first();
            
                                            //INSERT R_LOGIN_MOBILE
                                            DB::table('r_login_mobile')->insert(array(
                                                'id_karyawan' => $rows->id_karyawan,
                                                'nama_karyawan' => $rows->nama_user,
                                                'emulator'      => $data['emulator'],
                                                'os'            => $data['os'],
                                                'versi_os'      => $data['versi_os'],
                                                'base_os'       => $data['base_os'],
                                                'brand'         => $data['brand'],
                                                'model'         => $data['model'],
                                                'token_fcm'     => $data['token_fcm'],
                                                'device_id'     => $data['device_id'],
                                                'id_company'        => $rows->id_company
                                            ));
                                            //
                                        
                                            $data = array('id_karyawan'=>$rows->id_karyawan,
                                                            'username'=>$rows->username,
                                                            'nama_user'=>$rows->nama_user,
                                                            'id_cabang'=>$rows->id_cabang,
                                                            'id_departemen'=>$rows->id_departemen,
                                                            'nama_jabatan'=>$rows->nama_jabatan,
                                                            'level'=>$rows->level_user,
                                                             'nama_departemen'  =>$rows->nama_departemen,
                                                            'alamat'            =>$rows->alamat,
                                                            'no_telp'           =>$rows->telepon,
                                                            'email'             =>$rows->email,
                                                            'kamera'            =>$rows->kamera,
                                                            'lock_facerecognition' => $rows->lock_facerecognition=='1'?TRUE:FALSE,
                                                            'face_data'         =>$rows->face_data,
                                                            'foto'              =>Uploads_c::cekFoto($rows->foto)?Uploads_c::retrieve_file_url($rows->foto, 'photo'):'-',
                                                            'foto_face'         =>Uploads_c::cekFoto($rows->foto_face)?Uploads_c::retrieve_file_url($rows->foto_face, 'photo'):'-',
                                                            'id_company'=>$rows->id_company,
                                                            'kantor_pusat'=>$rows->kantor_pusat,
                                                            'komponen_mobile'=>str_replace('-', '',$rows->komponen_mobile)
                                                        );
                                            
                                            $response = array('success'=>true,
                                                                'message'=>'Anda berhasil login',
                                                                'data_login'=>$data);
                                        }
                                        
                                        
                                    }else{
                                        $response = array('success'=>false,
                                                            'message'=>'Akun Anda sudah tidak aktif');
                                    }
                                }else{
                                    $response = $cekStatusKaryawan;
                                }
                            }else{
                                $response = $checkMasaLayanan;
                            }
                            
                        }else{
                            $response = $checkCompany;
                        }
                        }
                        else{
                            $response = $checkDeviceID;
                        }
                        
                    }else{
                        $response = array('success'=>false,
                                        'message'=>'Password Anda salah');
                    }
    
                }else{
                    $response = array('success'=>false,
                                        'message'=>'Username Anda tidak terdaftar');
                }
            }
            return response()->json($response);
        }

        public static function logout($id_karyawan = null){
            // KURANG DEVICE ID (IMEI)
            $id_company = DB::table("users")->select("token_fcm", "login")->where("id_karyawan", $id_karyawan)->get();
            if(count($id_company) > 0){

                $update = DB::table('users')
                            ->where('id_karyawan', $id_karyawan)
                            ->update(['token_fcm' => '', 'login' => '0']);
                $response = array('success'=>true,
                                        'message'=>'Berhasil logout');
                
            }else{
                $response = array('success'=>false,
                                    'message'=>'Maaf, data karyawan tidak ditemukan');
            }
            return response()->json($response);
        }

        public static function resetPassword($email = null){
            $select = "SELECT id_karyawan, id_company
                        FROM data_karyawan 
                        WHERE email='$email'
                        ORDER BY tgl_input DESC
                        LIMIT 1";
            $data_user = collect(DB::select($select));
            if($data_user->count() > 0){

                $random = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890');
                $new_password = substr($random, 0, 8);
                $encript_pass = SHA1(SHA1(md5($new_password)));

                $data_user = $data_user->first();

                $update_password =  DB::update(" UPDATE users
                                                SET password = '".$encript_pass."'
                                                WHERE id_karyawan = '".$data_user->id_karyawan."'
                                                AND id_company = '$data_user->id_company'");
                if($update_password){
                    $response = array('success'=>true,
                                    'message'=>'Reset password berhasil, silahkan cek email Anda');
                }else{
                    $response = array('success'=>false,
                                    'message'=>'Password gagal direset, silahkan coba kembali');
                }
                

            }else{
                $response = array('success'=>false,
                                    'message'=>'Email Anda tidak terdaftar');
            }

            return response()->json($response);
        }


        public static function loginPayroll($username = null, $password = null){
            $check_username = User_m::checkUsername($username);
            if($check_username === true){
                $check_password = User_m::checkPassword($username,$password);
                if($check_password === true){
                    $response = array('success'=>true,
                                    'message'=>'Anda berhasil login');
                    return response()->json($response, 200);
                }else{
                    return $check_password;
                }
            }else{
                return $check_username;
            }
        }

        public static function getLogoPerusahaan($id_company = null){
            $select = collect(DB::select("SELECT url_logo FROM master_company WHERE id_company = '$id_company'"));

            if($select->count() > 0){
                $row = $select->first();
                $logo = $row->url_logo;
                if(!empty($logo)){
                    $data = array('logo'=>$row->url_logo);
                    $response = array('success'=>true,
                                        'data'=>$data);
                    return response()->json($response,200);
                }else{
                    $response = array('success'=>false,
                                    'message'=>'Data tidak ditemukan');
                    return response()->json($response,401);
                }
                
            }else{
                $response = array('success'=>false,
                                    'message'=>'Data tidak ditemukan');
                return response()->json($response,401);
            }
        }


        public static function getKomponenPaket($id_company = null){
            $select = collect(DB::select("SELECT kode_komponen, 
                                                nama_komponen,
                                                flag 
                                        FROM master_komponen_paket 
                                        WHERE id_company='$id_company'"));

            if($select->count() > 0){
                $data_komponen = $select;
                $data = array();
                foreach($data_komponen as $rows){
                    $data[] = array('kode_komponen'=>$rows->kode_komponen,
                                    'nama_komponen'=>$rows->nama_komponen,
                                    'flag'=>$rows->flag);
                }
                $response = array('success'=>true,
                                    'data'=>$data);
                return response()->json($response,200);
            }else{
                $response = array('success'=>false,
                                    'message'=>'Data tidak ditemukan');
                return response()->json($response,401);
            }
        }

        public static function gantiPassword($id_company = null, $id_karyawan = null, $new_password = null){
            $update = DB::update("UPDATE users
                                    SET password = '$new_password'
                                    WHERE id_karyawan = '$id_karyawan'
                                    AND id_company = '$id_company'");

            if($update){
                $response = array('success'=>true,
                                    'message'=>'Password berhasil diganti');
                return response()->json($response,200);
            }else{
                $response = array('success'=>false,
                                    'message'=>'Password gagal diganti');
                return response()->json($response,401);
            }
        }

        public static function detail($id_company = null, $id_karyawan = null){
            $select = collect(DB::select("SELECT data_karyawan.nama_lengkap,
                                            data_karyawan.jenis_kelamin,
                                            data_karyawan.tempat_lahir,
                                            data_karyawan.tgl_lahir,
                                            data_karyawan.telepon,
                                            data_karyawan.email,
                                            master_company.nama AS company,
                                            master_cabang.nama AS cabang,
                                            master_departemen.nama AS departemen,
                                            master_jabatan.nama AS jabatan
                                        FROM data_karyawan
                                        LEFT JOIN master_company ON master_company.id_company = data_karyawan.id_company
                                        LEFT JOIN master_cabang ON master_cabang.id_cabang = data_karyawan.id_cabang
                                        LEFT JOIN master_departemen ON master_departemen.id_departemen = data_karyawan.id_departemen
                                        LEFT JOIN master_jabatan ON master_jabatan.id_jabatan = data_karyawan.id_jabatan
                                        WHERE data_karyawan.id_company = '$id_company' 
                                        AND data_karyawan.id_karyawan = '$id_karyawan'"));

            if($select->count() > 0){
                $user = $select->first();

                $data = array('nama_karyawan' => $user->nama_lengkap,
                                'jenis_kelamin' => (($user->jenis_kelamin == 'P')?'Perempuan':'Laki - Laki'),
                                'tempat_lahir' => ((empty($user->tempat_lahir))?'':$user->tempat_lahir),
                                'tgl_lahir' => ((empty($user->tgl_lahir) || $user->tgl_lahir == '0000-00-00')?'':DateFormat::format($user->tgl_lahir,'d M Y')),
                                'telepon' => ((empty($user->telepon))?'':$user->telepon),
                                'email' => ((empty($user->email))?'':$user->email),
                                'company' => ((empty($user->company))?'':$user->company),
                                'cabang' => ((empty($user->cabang))?'':$user->cabang),
                                'departemen' => ((empty($user->departemen))?'':$user->departemen),
                                'jabatan' => ((empty($user->jabatan))?'':$user->jabatan));

                $response = array('success'=>true,
                                    'data'=>$data);
                return response()->json($response,200);
            }else{
                $response = array('success'=>false,
                                    'message'=>'Data tidak ditemukan');
                return response()->json($response,401);
            }
        }

        private static function getIdCompany($id_karyawan = null){
            $id_company = DB::table("users")->select("id_company")->where("id_karyawan", $id_karyawan)->first();
            if(count($id_company) > 0){
                echo 'ada data';
            }else{
                echo 'tidak ada data';
            }
        }
        private static function detaiKaryawan($id_karyawan = null){}

        private static function cekToken($token_fcm){
            $data = DB::table('users')->where('token_fcm', $token_fcm)->count();
            if ($data>0) {
                return true;
            }
            else return array('success'=>false,
                                'message'=>'Data token tidak ditemukan');
        }

        private static function cekStatusKaryawan($id_company = null,$id_karyawan = null){
            $query = "SELECT status, DATE_FORMAT(tgl_berhenti_bekerja,'%Y-%m-%d') as tgl_berakhir
                                  FROM data_karyawan 
                                  WHERE id_company='$id_company' 
                                    AND id_karyawan='$id_karyawan'";
            $selectStatusKaryawan = DB::select($query);

            if ($selectStatusKaryawan[0]->status == '1') {
                if (strtotime($selectStatusKaryawan[0]->tgl_berakhir) < strtotime(date('Y-m-d'))) {
                    $response = array('success'=>false,
                                    'message'=>'Masa kontrak Anda sudah habis');
                }else{
                    $response = array('success'=>true,
                                    'message'=>'');
                }
            }else if (in_array($selectStatusKaryawan[0]->status, ['5','6','7'])) {
                if (strtotime($selectStatusKaryawan[0]->tgl_berakhir) < strtotime(date('Y-m-d'))) {
                    $response = array('success'=>false,
                                    'message'=>'Akun Anda sudah tidak aktif');
                }else{
                    $response = array('success'=>true,
                                    'message'=>'');
                }
            }else{
                $response = array('success'=>true,
                                    'message'=>'');
            }
            return $response;            
        }

        private static function statusKaryawanGenerator($status){
            if($status == '1')
                $data = "Kontrak";
            else if($status == '2')
                $data = "Tetap";
            else if($status == '3')
                $data = "Izin Belajar";
            else if($status == '4')
                $data = "Tugas Belajar";
            else if($status == '5')
                $data = "Resign";
            else if($status == '6')
                $data = "Dikeluarkan/PHK";
            else if($status == '7')
                $data = "Pensiun";
            else if($status == '8')
                $data = "Harian";
            else
                $data = "Belum disetting";
            
            return $data;
        }

        public static function getUserTerkini($id_karyawan, $id_company, $token_fcm){
            $cekToken = User_m::cekToken($token_fcm);
            if ($cekToken === true) {
                $checkCompany = User_m::checkCompany($id_company);
                if($checkCompany === true){
                    $checkMasaLayanan = User_m::checkMasaLayanan($id_company);
                    if($checkMasaLayanan === true){
                        // echo 'df';exit;
                        $cekStatusKaryawan = User_m::cekStatusKaryawan($id_company,$id_karyawan);
                        if ($cekStatusKaryawan['success']) {
                            $select_user = collect(DB::select("SELECT users.id_user,
                                                                    users.id_karyawan,
                                                                        users.username,
                                                                        data_karyawan.nama_lengkap as nama_user,
                                                                        users.token_fcm,
                                                                        data_karyawan.id_cabang,
                                                                        data_karyawan.id_departemen,
                                                                        mj.nama as nama_jabatan,
                                                                        mj.level_user,
                                                                        users.id_company,
                                                                        data_karyawan.alamat_domisili as alamat,
                                                                        md.nama as nama_departemen,
                                                                        data_karyawan.telepon,
                                                                        data_karyawan.email,
                                                                        data_karyawan.foto,
                                                                        tp.komponen_mobile,
                                                                        users.kamera,
                                                                        users.foto_facerecognition as foto_face,
                                                                        users.matrix_facerecognition as face_data,
                                                                        mcb.flag as kantor_pusat,
                                                                        IF(users.lock_facerecognition='1', TRUE, FALSE) as lock_facerecognition
                                                            FROM users
                                                            LEFT JOIN data_karyawan ON data_karyawan.id_karyawan = users.id_karyawan
                                                                    AND data_karyawan.id_company = users.id_company
                                                            LEFT JOIN master_jabatan mj ON mj.id_jabatan = data_karyawan.id_jabatan 
                                                            LEFT JOIN master_departemen md ON md.id_departemen = data_karyawan.id_departemen
                                                            LEFT JOIN master_company mc ON mc.id_company = data_karyawan.id_company
                                                            LEFT JOIN master_cabang mcb ON mcb.id_cabang = data_karyawan.id_cabang
                                                            LEFT JOIN tb_paket tp ON tp.id_tb_paket = mc.id_tb_paket
                                                            WHERE users.id_karyawan = '$id_karyawan'"));

                            if(count($select_user) > 0){
                                $rows = $select_user->first();
                                $admin_boleh_login = true;
                                if($rows->level_user=='1' && !$admin_boleh_login){
                                    $response = array('success'=>false,
                                    'message'=>'Level Admin HR tidak diperbolehkan login di mobile');
                                }
                                else if($rows->level_user=='6' && !$admin_boleh_login){
                                    $response = array('success'=>false,
                                    'message'=>'Level Admin Finance tidak diperbolehkan login di mobile');
                                }
                                else{
                                    $rows = collect($select_user)->first();
                            
                                    $data = array('id_karyawan'=>$rows->id_karyawan,
                                                    'username'=>$rows->username,
                                                    'nama_user'=>$rows->nama_user,
                                                    'id_cabang'=>$rows->id_cabang,
                                                    'id_departemen'=>$rows->id_departemen,
                                                    'nama_jabatan'=>$rows->nama_jabatan,
                                                    'level'=>$rows->level_user,
                                                    'nama_departemen'=>$rows->nama_departemen,
                                                    'alamat'=>$rows->alamat,
                                                    'no_telp'=>$rows->telepon,
                                                    'email'=>$rows->email,
                                                    'face_data'=>$rows->face_data,
                                                    'kamera'=>$rows->kamera,
                                                    'lock_facerecognition' => $rows->lock_facerecognition=='1'?TRUE:FALSE,
                                                    'foto'=>Uploads_c::cekFoto($rows->foto)?Uploads_c::retrieve_file_url($rows->foto, 'photo'):'-',
                                                    'foto_face'=>Uploads_c::cekFoto($rows->foto_face)?Uploads_c::retrieve_file_url($rows->foto_face, 'photo'):'-',
                                                    'id_company'=>$rows->id_company,
                                                    'kantor_pusat'=>$rows->kantor_pusat,
                                                    'komponen_mobile'=>str_replace('-', '',$rows->komponen_mobile)
                                                );

                                    $response = array('success'=>true,
                                                        'message'=>'Anda berhasil login',
                                                        'data_login'=>$data);
                                }
                                
                                
                            }else{
                                $response = array('success'=>false,
                                                    'message'=>'Akun Anda sudah tidak aktif');
                            }
                        }else{
                            $response = $cekStatusKaryawan;
                        }
                    }else{
                        $response = $checkMasaLayanan;
                    }
                    
                }else{
                    $response = $checkCompany;
                }
            }
            else{
                $response = $cekToken;
            }
            return response()->json($response);
        }


        public static function ubahPassword($token_fcm, $password_lama, $password_baru){
            $cek_token = Token_m::cekToken($token_fcm);
            if ($cek_token!==false) {
                $id_karyawan = $cek_token;
                $cek_password = DB::table('users')
                ->where('id_karyawan', $id_karyawan)
                ->where('password', SHA1(SHA1(md5($password_lama))))
                ->count();
                if ($cek_password>0) {
                    if (strlen($password_baru)>=8) {
                        $update = DB::table('users')->where('id_karyawan', $id_karyawan)
                        ->update(['password' => SHA1(SHA1(md5($password_baru)))]);
                        if($update||$update=='') $response = array('success'=>true,'message'=> 'Password berhasil diubah');
                        else $response = array('success'=>false,'message'=> 'Password gagal diubah');
                    }
                    else $response = array('success'=>false,'message'=> 'Password kurang dari 8 karakter');
                }
                else $response = array('success'=>false,'message'=> 'Password lama tidak sesuai');
            }
            else return $response = array('success'=>false,'message'=> 'Unauthorized Access'); 
            return response()->json($response,200);
        }

        public static function ubahProfil($token_fcm, $data = array()){
            $cek_token = Token_m::cekToken($token_fcm);
            if ($cek_token!==false) {
                $id_karyawan = $cek_token;
                $update = DB::table('data_karyawan')->where('id_karyawan', $id_karyawan)
                ->update($data);
                if($update||$update=='') $response = array('success'=>true,'message'=> 'Nomor telepon/handphone berhasil diubah');
                else $response = array('success'=>false,'message'=> 'Nomor telepon/handphone gagal diubah');
            }
            else return $response = array('success'=>false,'message'=> 'Unauthorized Access'); 
            return response()->json($response,200);
        }

        public static function get_data_karyawan($id_karyawan){
            $data = DB::table('data_karyawan as dk')
            ->join('users as u', 'u.id_karyawan', '=', 'dk.id_karyawan')
            ->select('nama_lengkap', 'token_fcm', 'dk.id_departemen', 'dk.id_cabang', 'dk.jatah_cuti', 'dk.id_company')->where('dk.id_karyawan',
                $id_karyawan)->first();
            return $data;
        }

        public static function cekPassword($token_fcm, $password){
            $cek_password = DB::table('users')
            ->where('token_fcm', $token_fcm)
            ->where('password', SHA1(SHA1(md5($password))))
            ->count();
            if($cek_password>0) $response = array('success'=>true,'message'=> 'Password sesuai');
            else $response = array('success'=>false,'message'=> 'Password tidak sesuai');
            return response()->json($response,200);
        }

        public static function getPKedep($id_karyawan){
            return DB::table('p_kedep')->select('id_departemen')->where('id_karyawan', $id_karyawan)->get();
        }

        public static function getPKacab($id_karyawan){
            return DB::table('p_kacab')->select('id_cabang')->where('id_karyawan', $id_karyawan)->get();
        }

        public static function uploadFotoProfil($id_karyawan, $alamat_foto){
            return DB::table('data_karyawan')->where('id_karyawan', $id_karyawan)->update(array('foto' => $alamat_foto));
        }

         public static function get_data_user_by_level($level_user, $id_company){
            $data = DB::table('users')
            ->join('data_karyawan', 'data_karyawan.id_karyawan', '=', 'users.id_karyawan')
            ->join('master_jabatan', 'master_jabatan.id_jabatan', '=', 'data_karyawan.id_jabatan')
            ->join('usergroup', 'usergroup.id', '=', 'master_jabatan.level_user')
            ->select('users.id_karyawan', 'users.token_fcm')->where(['usergroup.id' => $level_user, 'users.id_company' => $id_company])->get();
            return $data;
        }

         public static function get_data_user_by_where($where){
            $data = DB::table('users')
            ->join('data_karyawan', 'data_karyawan.id_karyawan', '=', 'users.id_karyawan')
            ->join('master_jabatan', 'master_jabatan.id_jabatan', '=', 'data_karyawan.id_jabatan')
            ->join('usergroup', 'usergroup.id', '=', 'master_jabatan.level_user')
            ->select('users.id_karyawan', 'users.token_fcm', 'usergroup.id as level_user')->where($where)->get();
            return $data;
        }

        public static function get_data_user_by_id($id_karyawan){
            $data = DB::table('users')
            ->join('data_karyawan', 'data_karyawan.id_karyawan', '=', 'users.id_karyawan')
            ->join('master_jabatan', 'master_jabatan.id_jabatan', '=', 'data_karyawan.id_jabatan')
            ->join('usergroup', 'usergroup.id', '=', 'master_jabatan.level_user')
            ->select('usergroup.urutan', 'data_karyawan.supervisi')->where('data_karyawan.id_karyawan', $id_karyawan)->first();
            return $data;
        }

        public static function get_master_jabatan($level_approval, $id_company, $id_departemen){
            $data = DB::table('master_jabatan as mj')
                ->select('mj.level_user', 'mj.nama', 'mj.id_jabatan')
                ->leftJoin('data_karyawan as dk', 'dk.id_jabatan', '=', 'mj.id_jabatan')
                ->groupBy('dk.id_jabatan')
                ->whereIn('mj.level_user', explode(',', $level_approval))
                ->where('dk.id_departemen', $id_departemen)
                ->where('dk.id_company', $id_company)
                ->get();
            return $data;
        }

        public static function face_registration($id_karyawan, $data = array()){
            return DB::table('users')->where('id_karyawan', $id_karyawan)
            ->update($data);
        }






    }