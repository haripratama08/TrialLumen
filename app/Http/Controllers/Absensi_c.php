<?php

    namespace App\Http\Controllers;
    use Illuminate\Http\Request;
    use App\Http\Helpers\TimezoneMapper;
    use App\Models\Jam_kerja_m;
    use App\Models\Lokasi_m;
    use App\Models\Absensi_m;
    use DateTime;
    use DateTimeZone;
    use App\Http\Controllers\Uploads_c;

    class Absensi_c extends Controller{
        public $path;

        public function __construct()
        {
            //DEFINISIKAN PATH
            $this->path = storage_path('app/public/images');
        }

        public function index(){
            
        }

        public function jam_kerja(Request $request){
            $id_company = $request->get('id_company');
            $id_karyawan = $request->get('id_karyawan');
            $lat = $request->get('latitude');
            $long = $request->get('longitude');
            $get_timezone = TimezoneMapper::latLngToTimezoneString($lat, $long);
            $timezone = new DateTimeZone($get_timezone);
            $date = new DateTime();
            $date->setTimeZone($timezone);
            
            $current_date = $date->format('Y-m-d');
            $day_now = $date->format('N');
            $gmt = $date->format('P');

            return Jam_kerja_m::getJamKerja($id_company, $id_karyawan, $current_date, $day_now);
        }

        public function cekAbsen(Request $request){
            $id_cabang = $request->get('id_cabang');
            $id_company = $request->get('id_company');
            $id_karyawan = $request->get('id_karyawan');
            $current_date = date('Y-m-d');
            $lat = $request->get('latitude');
            $long = $request->get('longitude');
            $get_timezone = TimezoneMapper::latLngToTimezoneString($lat, $long);
            $timezone = new DateTimeZone($get_timezone);
            $date = new DateTime();
            $date->setTimeZone($timezone);
            
            $current_date = $date->format('Y-m-d');
            $current_time = $date->format('H:i:s');
            $day_now = $date->format('N');
            $gmt = $date->format('P');
            return Absensi_m::cekAbsen($id_karyawan, $id_company, $id_cabang, $current_date, $lat, $long, $day_now, $current_time);
        }
        
        public function add_absensi(Request $request){
            $id_company = $request->input('id_company');
            $id_karyawan = $request->input('id_karyawan');
            $jenis_absen = $request->input('jenis_absen');
            $lokasi_kantor = $request->input('lokasi_kantor');
            $lat = $request->input('latitude');
            $long = $request->input('longitude');
            $tipe_absen = $request->input('tipe_absen');
            $kode_absen = $request->input('kode_absen');
            $jam_kerja = $request->input('jam_kerja');
            $keterangan_absen = $request->input('keterangan_absen');
            $image = $request->input('image');
            $ket_kode = $request->input('ket_kode');
            $tgl_absensi = $request->input('tgl_absensi');
            $id_master_shift = $request->input('id_master_shift'); //CASE UNTUK JIKA ADA YG INJECT
            $durasi_istirahat = $request->input('durasi_istirahat');

            $get_timezone = TimezoneMapper::latLngToTimezoneString($lat, $long);
            $timezone = new DateTimeZone($get_timezone);
            $date = new DateTime();
            $date->setTimeZone($timezone);
            $gmt = $date->format('P');

            $current_date = $date->format('Y-m-d');

            if($tgl_absensi == null){
                $tgl_absensi = $current_date;
            }
            $jam_absen = $date->format('H:i:s');

            $split_jam_kerja = explode("-",$jam_kerja);
            $jam_masuk_kerja = $split_jam_kerja[0];
            $jam_pulang_kerja = $split_jam_kerja[1];

            if(strtotime($current_date.' '.$jam_absen) > strtotime($current_date.' '.$jam_masuk_kerja)){
                $terlambat = floor((strtotime($current_date.' '.$jam_absen) - strtotime($current_date.' '.$jam_masuk_kerja))/60);
            }else{
                $terlambat = 0;
            }

            if(strtotime($current_date.' '.$jam_absen) < strtotime($current_date.' '.$jam_pulang_kerja)){
                $pulang_cepat = floor((strtotime($current_date.' '.$jam_pulang_kerja) - strtotime($current_date.' '.$jam_absen))/60);
            }else{
                $pulang_cepat = 0;
            }

            $data1 = array();
            if($jenis_absen == 'absen_masuk'){
                $table = 'absensi_masuk';
                $id = Absensi_m::getId($id_company,$table);
                if($terlambat > 0){
                    $kode_absen = $kode_absen.',(T)';
                }
                $data1 = array('id_absensi_masuk' => $id,
                                'jenis_absen' => $tipe_absen,
                                'jam_kerja' => $jam_masuk_kerja,
                                'terlambat' => $terlambat,
                                'ket_kode'   => $ket_kode,
                                'kode_absen' => $kode_absen);
                            
            }else if($jenis_absen == 'absen_pulang'){
                $table = 'absensi_pulang';

                $id = Absensi_m::getId($id_company,$table);
                $absensi_masuk = Absensi_m::getLastRowIAbsensiMasuk($id_karyawan);
                $id_masuk = $absensi_masuk->id_absensi_masuk;
                $tgl_absen_masuk = $absensi_masuk->tgl_absen;

                //CEK APABILA ABSEN ISTIRAHAT MULAI NAMUN BELUM ABSEN ISTIRAHAT SELESAI
                $cek_istirahat = Absensi_m::cekAbsensiIstirahat($id_masuk);
                if($cek_istirahat !=null){//ADA ABSEN ISTIRAHAT YG BELUM MELAKUKAN ABSEN ISTIRAHAT SELESAI
                    $id_istselesai = Absensi_m::getId($id_company,'istirahat_selesai');
                    Absensi_m::insertIstirahatSelesai(array(
                        'id_istselesai' => $id_istselesai, 
                        'id_absensi_masuk' => $id_masuk,
                        'id_company' => $id_company,
                        'id_karyawan' => $id_karyawan,
                        'lokasi_absen' => $lokasi_kantor,
                        'timezone' => $get_timezone,
                        'gmt' => $gmt,
                        'latitude' => $lat,
                        'longitude' => $long,
                        'tgl_absen' => $current_date,
                        'jam_absen' => $current_date.' '.$jam_absen,
                    ));
                }

                $data1 = array('id_absensi_pulang' => $id,
                                'jam_kerja' => $jam_pulang_kerja,
                                'pulang_cepat' => $pulang_cepat,
                                'id_masuk' => $id_masuk
                            );

            }else if($jenis_absen == 'istirahat_mulai'){

                $table = 'istirahat_mulai';
                $id = Absensi_m::getId($id_company,$table);
                $data1 = array('id_istmulai' => $id, 'id_absensi_masuk' => Absensi_m::getLastRowIAbsensiMasuk($id_karyawan)->id_absensi_masuk);

            }else if($jenis_absen == 'istirahat_selesai'){

                $table = 'istirahat_selesai';
                $id = Absensi_m::getId($id_company,$table);
                $data1 = array('id_istselesai' => $id, 'id_absensi_masuk' => Absensi_m::getLastRowIAbsensiMasuk($id_karyawan)->id_absensi_masuk);

            }else if($jenis_absen == 'lembur_mulai'){

                $table = 'lembur_mulai';
                $id = Absensi_m::getId($id_company,$table);
                $data1 = array('id_lemmulai' => $id);

            }else if($jenis_absen == 'lembur_selesai'){

                $table = 'lembur_selesai';
                $id = Absensi_m::getId($id_company,$table);
                $data1 = array('id_lemselesai' => $id);
            }

            $data2 = array('id_company' => $id_company,
                            'id_karyawan' => $id_karyawan,
                            'lokasi_absen' => $lokasi_kantor,
                            'timezone' => $get_timezone,
                            'gmt' => $gmt,
                            'latitude' => $lat,
                            'longitude' => $long,
                            'tgl_absen' => $tgl_absensi,
                            'jam_absen' => $current_date.' '.$jam_absen,
                            'keterangan' => $keterangan_absen,
                            'foto' => Uploads_c::upload_file(
                                $image,
                                "/absensi/".env('NAME_APPLICATION')."/",
                                $id_company."/".$jenis_absen."/".date("Ym"),
                                $id_karyawan.date('YmdHis').".jpg"
                            ));
            
            $data = array_merge($data1,$data2);

            return Absensi_m::addAbsensi($table, $data);
        }

        public function getRekapAbsensi(Request $request){
            $id_company = $request->get('id_company');
            $id_karyawan = $request->get('id_karyawan');
            $current_month = date('Y-m');
            return Absensi_m::getRekapAbsensi($id_company, $id_karyawan, $current_month);
        }

        public function dataAbsensi(Request $request){
            $data = array(
                'id_company'    => $request->get('id_company'),
                'id_departemen' => $request->get('id_departemen'), //absensiKedep
                'id_karyawan'   => $request->get('id_karyawan'), //absensiSaya & absensiSPV
                'id_cabang'     => $request->get('id_cabang'), //absensiKacab
                'bulan_tahun'           => $request->get('filter_bulan'),
                'range_tanggal_mulai'   => $request->get('range_tanggal_mulai'),
                'range_tanggal_selesai' => $request->get('range_tanggal_selesai'),
                'level_user'    => $request->get('level_user'),
                'konteks'       => $request->get('konteks'),
                'limit'         => $request->get('limit'),
                'offset'        => $request->get('offset'),
                'id_select_karyawan'   => $request->get('id_select_karyawan'), //search karyawan
            );

            return Absensi_m::dataAbsensi($data);
        }
        
        public function getBebasShift(Request $request){
            $id_company = $request->get('id_company');
            $id_cabang = $request->get('id_cabang');
            $id_karyawan = $request->get('id_karyawan');
            $id_departemen = $request->get('id_departemen');

            return Absensi_m::getBebasPilihShift($id_karyawan,$id_cabang,$id_company,$id_departemen);
        }

    }