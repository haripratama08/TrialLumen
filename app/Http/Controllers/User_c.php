<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\User_m;
    use App\Models\Token_m;
    use App\Http\Controllers\Uploads_c;

    class User_c extends Controller{

        public function index(){
            
        }

        public function active(Request $request){
            $id_company = $request->get('id_company');
            $id_karyawan = $request->get('id_karyawan');
            return User_m::checkUserActive($id_company, $id_karyawan);

        }

        public function getToken(Request $request){
            $id_company = $request->input('id_company');
            $id_karyawan = $request->input('id_karyawan');
            return User_m::getToken($id_company, $id_karyawan);

        }

        public function login(Request $request){
            $data = array(
                'username'  => $request->get('username'),
                'password'  => $request->get('password'),
                'token_fcm' => $request->get('token_fcm'),
                'emulator'  => $request->get('emulator'),
                'os'        => $request->get('os'),
                'versi_os'  => $request->get('versi_os'),
                'base_os'   => $request->get('base_os'),
                'brand'     => $request->get('brand'),
                'model'     => $request->get('model'),
                'device_id' => $request->get('device_id'),
            );
            return User_m::login($data);

        }

        public function logout(Request $request){
            $id_karyawan = $request->get('id_karyawan');
            return User_m::logout($id_karyawan);
        }

        public function resetPassword(Request $request){
            $email = $request->input('email');
            return User_m::resetPassword($email);

        }

        public function loginPayroll(Request $request){
            $username = $request->input('username');
            $password = SHA1(SHA1(MD5($request->input('password'))));
            return User_m::loginPayroll($username, $password);

        }

        public function getLogoPerusahaan(Request $request){
            $id_company = $request->get('id_company');
            return User_m::getLogoPerusahaan($id_company);
        }

        public function getKomponenPaket(Request $request){
            $id_company = $request->get('id_company');
            return User_m::getKomponenPaket($id_company);
        }

        public function gantiPassword(Request $request){
            $id_company = $request->post('id_company');
            $id_karyawan = $request->post('id_karyawan');
            $new_password = SHA1(SHA1(md5($request->post('new_password'))));
            return User_m::gantiPassword($id_company, $id_karyawan, $new_password);
        }

        public function detail(Request $request){
            $id_company = $request->get('id_company');
            $id_karyawan = $request->get('id_karyawan');
            return User_m::detail($id_company, $id_karyawan);
        }

        //SURYA

        public function getUserTerkini(Request $request){
            $id_karyawan = $request->get('id_karyawan');
            $id_company = $request->get('id_company');
            $token_fcm = $request->get('token_fcm');
            return User_m::getUserTerkini($id_karyawan, $id_company, $token_fcm);
        }

        public function ubahPassword($token_fcm, $password_lama, $password_baru){
            return User_m::ubahPassword($token_fcm, $password_lama, $password_baru);
        }

        public function ubahProfil($token_fcm, $no_telp){
            return User_m::ubahProfil($token_fcm, array('telepon' => $no_telp));
        }

        public function cekPassword(Request $request){
            $token_fcm = $request->get('token_fcm');
            $password = $request->get('password');
            return User_m::cekPassword($token_fcm, $password);
        }

        public function uploadFotoProfil(Request $request){
            // echo base_path('../');exit;
            $token_fcm = $request->get('token_fcm');
            $id_company = $request->get('id_company');
            $foto = $request->get('foto');
            $cek_token = Token_m::cekToken($token_fcm);
            if ($cek_token!==false) {
                $id_karyawan = $cek_token;
                $update = User_m::uploadFotoProfil($id_karyawan, Uploads_c::upload_file(
                    $foto,
                    "/biodata/".env('NAME_APPLICATION')."/", 
                    $id_company."/karyawan/".$id_karyawan."/profil",
                    $id_karyawan.date('YmdHis').".jpg"
                    )
                );
                if ($update) $response = array('success'=>true,'message'=> 'Foto berhasil diupload');
                else $response = array('success'=>false,'message'=> 'Foto gagal diupload');
            }
            else $response = array('success'=>false,'message'=> 'Unauthorized Access'); 
            return response()->json($response,200);
        }

        public function face_registration(Request $request){
            $data_karyawan = Token_m::get_data_karyawan_by_token($request->input('token_fcm'));
            if($data_karyawan==null)
                $response = array('success' => false, 'message' => 'Unauthorized Access');
            else{
                $id_karyawan = $data_karyawan->id_karyawan;
                $id_company = $data_karyawan->id_company;
                $face_data = $request->input('face_data');
                $image = $request->input('image');
                //upload file
                // $imageName = $id_karyawan.date('YmdHis').".jpg";
                // $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                // $mkdir = "face_data/".date("Ym").'/'.$id_company;
                // if(!is_dir(base_path("../web/public/".$mkdir))){ 
                //     mkdir(base_path("../web/public/".$mkdir),0777,TRUE); 
                // }
                // file_put_contents(base_path("../web/public/".$mkdir."/".$imageName), $imageData);
                // $alamat_foto = '/public/'.$mkdir."/".$imageName;
                $alamat_foto = Uploads_c::upload_file(
                    $image,
                    "/biodata/".env('NAME_APPLICATION')."/",
                    $id_company."/face_data/".date("Ym"),
                    $id_karyawan.date('YmdHis').".jpg"
                );
                //
                $update = User_m::face_registration($id_karyawan, array('matrix_facerecognition' => $face_data, 'foto_facerecognition' => $alamat_foto));
                if($update||$update=='') $response = array('success'=>true,'message'=> 'Registrasi wajah berhasil');
                else $response = array('success'=>false,'message'=> 'Registrasi wajah gagal');
            }
            return response()->json($response,200);
        }
    }