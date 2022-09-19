<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Helpers\TimezoneMapper;
    use App\Models\Token_m;
    use DateTime;
    use DateTimeZone;

    class FaceRecognition_c extends Controller{
        public $path;

        public function __construct(){
            $this->path = storage_path('app/public/images');
        }

        public function index(){
            
        }


        public function getDataPeringatanRegistrasi(Request $request){
            $token_fcm = $request->get('token_fcm');
            $data_karyawan        = Token_m::get_data_karyawan_by_token($token_fcm);
            if($data_karyawan==null){
                $response = array('success' => false, 'message' => 'Unauthorized Access');
            }
            else{
                $data = array(
                    array(
                        'index' => '1',
                        'isi' => 'Absensi hanya dapat dilakukan setelah registrasi wajah',
                    ),
                    array(
                        'index' => '2',
                        'isi' => 'Registrasi wajah harus dihindari melihat ke atas, ke bawah, atau ke samping',
                    ),
                    array(
                        'index' => '3',
                        'isi' => 'Usahakan pengambilan gambar dalam kondisi cahaya dan fokus optimal',
                    ),
                    array(
                        'index' => '4',
                        'isi' => 'Registrasi wajah tidak boleh menggunakan masker/penutup wajah',
                    ),
                    array(
                        'index' => '5',
                        'isi' =>  'Jika registrasi wajah menggunakan kacamata, maka absensi harus menggunakan kacamata',
                    ),
                   
                );
                $response = array('success' => true, 'message' => 'Data peringatan registrasi berhasil ditemukan', 'data' => $data);
            }
            return response()->json($response,200);
        }
    }