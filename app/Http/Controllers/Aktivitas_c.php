<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Helpers\TimezoneMapper;
    use App\Models\Aktivitas_m;
    use App\Models\Absensi_m;
    use DateTime;
    use DateTimeZone;
    use App\Http\Helpers\DateFormat;
    use App\Http\Controllers\Uploads_c;

    class Aktivitas_c extends Controller{
        public $path;

        public function __construct(){
            $this->path = storage_path('app/public/images');
        }

        public function index(){
            
        }


        /*public function addAktivitas(Request $request){
            $id_company = $request->input('id_company');
            $id_karyawan = $request->input('id_karyawan');
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $keterangan = $request->input('keterangan');
            $image = $request->input('image');

            $get_timezone = TimezoneMapper::latLngToTimezoneString($latitude, $longitude);
            $timezone = new DateTimeZone($get_timezone);
            $date = new DateTime();
            $date->setTimeZone($timezone);
            
            $current_date = $date->format('Y-m-d H:i:s');
            
            $imageName = "IMG".$id_karyawan.date('His').".jpg";
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));

            $mkdir = "images/aktifitas/".$id_company."/".date("Ym");

            if(!is_dir(storage_path("/public/".$mkdir))){ 
				mkdir(storage_path("/public/".$mkdir),0777,TRUE); 
			}
            file_put_contents(storage_path("/public/".$mkdir."/".$imageName), $imageData);

            $nama_foto = $mkdir."/".$imageName;

            $data = array('id_aktivitas' => Aktivitas_m::getId($id_company),
                            'id_company' => $id_company,
                            'id_karyawan' => $id_karyawan,
                            'waktu' => $current_date,
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'keterangan' => $keterangan,
                            'image' => $nama_foto);

            return Aktivitas_m::addAktivitas($data);
        }*/

        /*public function dataAktivitas(Request $request){
            $id_company = $request->get('id_company');
            $id_karyawan = $request->get('id_karyawan');
            $filter_bulan = $request->get('filter_bulan');
            $limit_start = $request->get('limit_start');
            $limit_end = $limit_start + 10;

            return Aktivitas_m::dataAktivitas($id_company, $id_karyawan, $filter_bulan, $limit_start, $limit_end);
        }*/

        public function getDataAktivitas(Request $request){
             $data = array(
                "id_company"        => $request->get('id_company'),
                "id_karyawan"       => $request->get('id_karyawan'),
                "id_karyawan_select"       => $request->get('id_karyawan_select'),
                "level_user"        => $request->get('level_user'),
                "id_cabang"         => $request->get('id_cabang'),
                "id_departemen"     => $request->get('id_departemen'),
                "limit"             => $request->get('limit'),
                "offset"            => $request->get('offset'),
                "konteks"           => $request->get('konteks'),
                "month_year"            => $request->get('month_year'),
                "range_tanggal_mulai"     => $request->get('range_tanggal_mulai'),
                "range_tanggal_selesai"   => $request->get('range_tanggal_selesai'),
                "id_aktivitas"       => $request->get('id_aktivitas'),
                "jenis"       => $request->get('jenis'),
            );

            return Aktivitas_m::getDataAktivitas($data);
        }

        public function addAktivitas(Request $request){
            $keterangan = $request->input('keterangan');
            $id_company = $request->input('id_company');
            $id_karyawan = $request->input('id_karyawan');
            $lat = $request->input('latitude');
            $long = $request->input('longitude');
            $image = $request->input('image');
            $jenis = $request->input('jenis');

            $get_timezone = TimezoneMapper::latLngToTimezoneString($lat, $long);
            $timezone = new DateTimeZone($get_timezone);
            $date = new DateTime();
            $date->setTimeZone($timezone);
            $gmt = $date->format('P');

            $current_date = $date->format('Y-m-d');
            $jam_absen = $date->format('Y-m-d H:i:s');

            $data_insert = array(
                'id_karyawan'   => $id_karyawan,
                'jenis'     => $jenis,
                'waktu'     => $jam_absen,
                'lokasi'  => $request->input('lokasi_absen'),
                'timezone'      => $get_timezone,
                'gmt'           => $gmt,
                'latitude'      => $lat,
                'longitude'     => $long,
                'keterangan'    => $keterangan,
                'id_company'    => $id_company, 
                'image'          => Uploads_c::upload_file(
                    $image,
                    "/absensi/".env('NAME_APPLICATION')."/",
                    $id_company."/aktivitas/".date("Ym"),
                    $id_karyawan.date('YmdHis').".jpg"
                ),
                'id_aktivitas'  => Aktivitas_m::get_id_aktivitas($id_company)
            );
            return Aktivitas_m::addAktivitas($data_insert);
            
        }

        public function cekLokasi(Request $request){
            $id_company = $request->get('id_company');
            $id_karyawan = $request->get('id_karyawan');
            $lat = $request->get('latitude');
            $long = $request->get('longitude');
            $get_timezone = TimezoneMapper::latLngToTimezoneString($lat, $long);
            $timezone = new DateTimeZone($get_timezone);
            $date = new DateTime();
            $date->setTimeZone($timezone);
            $current_date = $date->format('Y-m-d');
            $tgl = DateFormat::format($current_date,"N d M Y");
            $dataLokasi = Absensi_m::_getLokasi($id_karyawan, $id_company, $lat, $long);
            $response = array(
                'success' => true,
                'message' => 'Data berhasil ditemukan',
                'tanggal' => $tgl,
                'CekLokasi'    => $dataLokasi
            );
            return response()->json($response,200);
           }
    }