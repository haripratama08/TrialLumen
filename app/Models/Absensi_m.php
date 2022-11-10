<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\DateFormat;
use App\Http\Helpers\Convertion;
use App\Http\Controllers\Uploads_c;

class Absensi_m extends Model
{

    private static function warnaJenisAbsen($jenis_absensi)
    {
        if ($jenis_absensi == 'reguler') $status = array(
            'color' => '0XFF1575d6',
            'font_color' => '0XFFFFFFFF',
            'enable_detail' => true
        );
        else if ($jenis_absensi == 'shift') $status = array(
            'color' => '0XFF228B22',
            'font_color' => '0XFFFFFFFF',
            'enable_detail' => true
        );
        else if ($jenis_absensi == 'tukar-shift') $status = array(
            'color' => '0XFFFFD700',
            'font_color' => '0XFFFFFFFF',
            'enable_detail' => true
        );
        else if ($jenis_absensi == 'libur') $status = array(
            'color' => '0XFF525252',
            'font_color' => '0XFFFFFFFF',
            'enable_detail' => false
        );
        else if ($jenis_absensi == 'izin' || $jenis_absensi == 'Sakit' || $jenis_absensi == 'cuti') $status = array(
            'color' => '0XFFff7530',
            'font_color' => '0XFFFFFFFF',
            'enable_detail' => false
        );
        else if ($jenis_absensi == 'alpha') $status = array(
            'color' => '0XFFff4f4f',
            'font_color' => '0XFFFFFFFF',
            'enable_detail' => false
        );
        else $status = array(
            'color' => '0XFF525252',
            'font_color' => '0XFFFFFFFF',
            'enable_detail' => false
        );
        return $status;
    }


    public static function cekAbsen($id_karyawan = null, $id_company = null, $id_cabang = null, $current_date = null, $lat_absen = null, $long_absen = null, $day_now = null, $current_time = null)
    {
        $level = User_m::get_data_user_by_where(['data_karyawan.id_karyawan' => $id_karyawan]);
        // var_dump($level);exit;
        if (empty($level[0])) {
            $respon = array('status' => false, 'message' => 'Jabatan Anda belum diatur');
            $json = response()->json($respon);
            return $json;
            exit;
        }

        $level_user = $level[0]->level_user;

        // echo $level[0]->level_user;exit;
        if ($level_user == '1' || $level_user == '6') {
            $respon = array('status' => false, 'message' => 'Akun HR dan Finance tidak diperbolehkan untuk absensi');
            $json = response()->json($respon);
            return $json;
            exit;
        }
        # LOKASI
        $lokasi = self::_getLokasi($id_karyawan, $id_company, $lat_absen, $long_absen);
        if (!$lokasi['status']) {
            $response = $lokasi;
            $json = response()->json($response);
            return $json;
            exit;
        }
        // echo $current_time;exit;
        // echo ("masuk cek absen");
        $cek_absensi_terakhir = self::_cekAbsensiTerakhir($id_karyawan, $id_company, $current_time);
        $ganti_jadwal = $cek_absensi_terakhir['ganti_jadwal'];
        $kode_absen = $cek_absensi_terakhir['kode_absen'];


        if ($ganti_jadwal == '0') {
            $isGantiJadwal = false;
        } else {
            $isGantiJadwal = true;
        }
        if ($cek_absensi_terakhir['status']) { //TRUE LEWAT HARI
            $tgl_absensi =  $cek_absensi_terakhir['tgl_absen'];
            $absen_pulang =  $cek_absensi_terakhir['absen_pulang'];
            // DB::enableQueryLog();
            $jadwal_hari_ini = DB::table('data_shift_karyawan as dsk')
                ->selectRaw('dsk.id_shift_karyawan')
                ->where('dsk.id_karyawan', $id_karyawan)
                ->where('dsk.tanggal', $tgl_absensi)
                ->where('dsk.id_company', $id_company)
                ->count();

            if ($jadwal_hari_ini == 0) {
                $jenisJadwal = 'reguler';
            } else {
                $jenisJadwal = 'shift';
            }

            if ($absen_pulang) {
                # SUDAH PULANG
                //JADWAL SHIFT HARI INI
                $jadwal_absensi = self::_getJadwalAbsensi($id_cabang, $id_company, $current_date, $jenisJadwal, $id_karyawan, $day_now);
                $data_absensi = self::_dataAbsensi($id_karyawan, $id_company, $current_date, $absen_pulang);
                $status = $jadwal_absensi['status'];
            } else { //BELUM PULANG
                # JADWAL SHIFT KEMARIN
                $jadwal_absensi = self::_getJadwalAbsensi($id_cabang, $id_company, $tgl_absensi, $jenisJadwal, $id_karyawan, $day_now, $isGantiJadwal, $kode_absen);
                $data_absensi = self::_dataAbsensi($id_karyawan, $id_company, $tgl_absensi, $absen_pulang);
                $status = $jadwal_absensi['status'];
            }
            // exit();
        } else {
            # CEK JENIS JAM KERJA
            $cek_jadwal_absensi = self::_cekJadwalAbsensi($id_cabang, $id_company, $current_date, $id_karyawan, $day_now);
            $jenis_jam_kerja = $cek_jadwal_absensi['jenis_jam_kerja'];
            if (in_array($jenis_jam_kerja, array('reguler', 'shift', 'tukar-shift'))) {
                # JADWAL ABSENSI
                $jadwal_absensi = self::_getJadwalAbsensi($id_cabang, $id_company, $current_date, $jenis_jam_kerja, $id_karyawan, $day_now, $isGantiJadwal, $kode_absen);
                // var_dump($jadwal_absensi);
                // exit();
            } else {
                $jadwal_absensi = array(
                    'status' => true,
                    'jenis_jam_kerja' => $jenis_jam_kerja,
                    'jam_kerja' => array(
                        'tanggal' => DateFormat::format($current_date, "N d M Y"),
                        'kode_absensi' => 'L',
                        'ket_kode' => 'Libur',
                        'keterangan' => $cek_jadwal_absensi['keterangan'],
                        'jam_masuk' => '',
                        'jam_pulang' => '',
                        'start_absensi_masuk' => '',
                        'batas_absensi_pulang' => '',
                        'flag_batas_absen_pulang' => '',
                        'tgl_jadwal_absensi' => $current_date,
                    ),
                    'libur_shift' => '1',
                    'keterangan' => $cek_jadwal_absensi['keterangan']
                );
            }


            # DATA ABSENSI
            $data_absensi = self::_dataAbsensi($id_karyawan, $id_company, $current_date, '1');
            $status = $jadwal_absensi['status'];
        }
        if (!$jadwal_absensi['status']) {
            $response = $jadwal_absensi;
            $json = response()->json($response);
            return $json;
            exit;
        }
        if ($jadwal_absensi['jenis_jam_kerja'] == 'libur') {
            $start_absensi_masuk  = '';
            $batas_absensi_pulang = '';
        } else {
            // echo $jadwal_absensi['jam_kerja']['tgl_jadwal_absensi']." ".$jadwal_absensi['jam_kerja']['start_absensi_masuk'];exit;
            $start_absensi_masuk  = strtotime($jadwal_absensi['jam_kerja']['tgl_jadwal_absensi'] . " " . $jadwal_absensi['jam_kerja']['start_absensi_masuk']);
            $batas_absensi_pulang = strtotime($jadwal_absensi['jam_kerja']['batas_absensi_pulang']);
        }
        // var_dump($jadwal_absensi['jam_kerja']);exit;
        $time_now           = strtotime($current_time);
        $jenis_jam_kerja    = $jadwal_absensi["jenis_jam_kerja"];
        $jam_masuk          = $data_absensi["jam_masuk"];
        $jam_pulang         = $data_absensi["jam_pulang"];
        $istirahat_mulai    = $data_absensi["istirahat_mulai"];
        $istirahat_selesai  = $data_absensi["istirahat_selesai"];
        $libur_shift        = $jadwal_absensi["libur_shift"];
        // echo $jadwal_absensi["jam_kerja"]["flag_batas_absen_pulang"];exit;
        if ($jadwal_absensi["jam_kerja"]["flag_batas_absen_pulang"] == '1') {
            $batas_absensi_pulang = $jadwal_absensi["jam_kerja"]['tgl_jadwal_absensi'] . ' ' . $jadwal_absensi["jam_kerja"]['batas_absensi_pulang'];
            $batas_absensi_pulang = date_format(date_create($batas_absensi_pulang), 'Y-m-d H:i:s');
            // echo $jam_pulang;exit;
            $batas_absensi_pulang = date('Y-m-d H:i:s', strtotime('+1 days', strtotime($batas_absensi_pulang)));
            // echo $batas_absensi_pulang;exit;
            $batas_absensi_pulang = strtotime($batas_absensi_pulang);
        } else {
            $batas_absensi_pulang = $jadwal_absensi["jam_kerja"]['tgl_jadwal_absensi'] . ' ' . $jadwal_absensi["jam_kerja"]['batas_absensi_pulang'];
            $batas_absensi_pulang =  strtotime($batas_absensi_pulang);
        }

        // IDENTIFIKASI TUKAR SHIFT

        $cek_tukar_shift = DB::table('absensi_masuk as am')
            ->selectRaw('am.keterangan')
            ->where('am.id_karyawan', $id_karyawan)
            ->where('am.id_company', $id_company)
            ->where('am.keterangan', 'Tukar Shift')
            ->count();

        if ($cek_tukar_shift > 0) {

            // CEK APAKAH TUKAR SHIFT SUDAH PULANG?

            $absen_masuk_tukar_shift = DB::table('absensi_masuk as am')
                ->selectRaw('am.keterangan')
                ->where('am.id_karyawan', $id_karyawan)
                ->where('am.id_company', $id_company)
                ->where('am.ket_kode', 'Tukar Shift')
                ->first();

            if ($absen_masuk_tukar_shift->keterangan) {
                $belum_selesai_tukar_shift = 1;
            } else {
                $belum_selesai_tukar_shift = 0;
            }
        } else {
            $belum_selesai_tukar_shift = 0;
        }

        // print_r($time_now);
        // print_r($batas_absensi_pulang);

        // echo $jam_pulang;exit;
        $button_absensi = self::_getTogleButtonAbsensi($jenis_jam_kerja, $time_now, $start_absensi_masuk, $jam_masuk, $istirahat_mulai, $istirahat_selesai, $jam_pulang, $batas_absensi_pulang, $libur_shift, $belum_selesai_tukar_shift);

        $data['lokasi'] = $lokasi;
        $data['data_absensi'] = $data_absensi;
        $data['jadwal_absensi'] = $jadwal_absensi;
        $data['button_absensi'] = $button_absensi;
        $waktu = array(
            'current_date' => $current_date,
            'current_time' => $current_time,
            'day_now'       => $day_now
        );
        $data['waktu'] = $waktu;
        $response['status'] = $status;
        $response['data'] =  $data;
        $json = response()->json($response);
        return $json;
    }

    private static function _cekJadwalAbsensi($id_cabang, $id_company, $current_date, $id_karyawan, $day_now)
    {
        $jadwal_shift = DB::table('data_shift_karyawan as dsk')
            ->selectRaw('dsk.id_shift_karyawan')
            ->where('dsk.id_karyawan', $id_karyawan)
            ->where('dsk.tanggal', $current_date)
            ->where('dsk.id_company', $id_company)
            ->count();

        $cekJadwalTukarShift = DB::table('data_shift_karyawan as dsk')
            ->selectRaw('dsk.id_shift_karyawan')
            ->where('dsk.id_karyawan', $id_karyawan)
            ->where('dsk.tanggal', $current_date)
            ->where('dsk.id_company', $id_company)
            ->where('dsk.id_master_shift', 'D220905202209000')
            ->count();


        // echo($cekJadwalTukarShift);
        // exit();
        if ($jadwal_shift > 0) {
            if ($cekJadwalTukarShift > 0) {
                $result['jenis_jam_kerja'] = 'tukar-shift';
            } else {
                $result['jenis_jam_kerja'] = 'shift';
            }
            // if($cekJadwalShift['id_master_shift'] == 'D220905202209000'){
            //     $result['jenis_jam_kerja'] = 'tukar-shift';
            // }

            //    print_r($cekJadwalShift);
            //     exit();
        } else {
            $holidays = DB::table('holidays')
                ->select('id', 'keterangan')
                ->where('tanggal', $current_date)
                ->where('id_company', $id_company)
                ->first();
            if ($holidays != null) {
                $result['jenis_jam_kerja'] = 'libur';
                $result['keterangan']      = 'Libur - ' . $holidays->keterangan;
            } else {
                $jam_kerja = DB::table('jam_kerja')
                    ->select('id_jam_kerja')
                    ->where('id_company', $id_company)
                    ->where('id_cabang', $id_cabang)
                    ->where('hari', $day_now)
                    ->where('libur', '1')
                    ->count();
                if ($jam_kerja > 0) {
                    $result['jenis_jam_kerja'] = 'libur';
                    $result['keterangan']      = 'libur';
                } else {
                    $izin = DB::table('absensi_masuk')
                        ->select('id_absensi_masuk', 'ket_kode')
                        ->where('id_company', $id_company)
                        ->where('id_karyawan', $id_karyawan)
                        ->where('tgl_absen', $current_date)
                        ->whereIn('jenis_absen', ['sakit', 'izin', 'cuti'])
                        ->first();
                    if ($izin != null) {
                        $result['jenis_jam_kerja'] = 'reguler';
                        $result['keterangan']      = $izin->ket_kode;
                    } else {
                        $result['jenis_jam_kerja'] = 'reguler';
                    }
                }
            }
        }

        return $result;
    }

    private static function _getJadwalAbsensi(
        $id_cabang = null,
        $id_company = null,
        $tanggal = null,
        $jenisJadwal = null,
        $id_karyawan = null,
        $dayNow = null,
        $ganti_jadwal = false,
        $kode_absen = 'H'
    ) {
        // echo($kode_absen);
        // die();
        // DB::enableQueryLog();
        if (!$ganti_jadwal) { //TIDAK GANTI JADWAL / DEFAULT
            if ($jenisJadwal == 'reguler') {
                $jenis_jam_kerja = 'reguler';
                $jam_kerja = DB::table('jam_kerja')
                    ->selectRaw('"0" AS id_master_shift, CURDATE() AS tanggal,masuk as jam_masuk, pulang as jam_pulang, start_absen_masuk, batas_absen_pulang,"" as kode_shift, "reguler" as nama_shift, "0" as flag_batas_absen_pulang')
                    ->where('hari', $dayNow)
                    ->where('id_cabang', $id_cabang)
                    ->where('id_company', $id_company)
                    ->first();
            } else if ($jenisJadwal == 'tukar-shift') {

                $jenis_jam_kerja = 'tukar-shift';
                $jam_kerja = DB::table('data_shift_karyawan as dsk')
                    ->selectRaw('ms.id_master_shift, dsk.tanggal, ms.jam_masuk, ms.jam_pulang, ms.start_absen_masuk, ms.batas_absen_pulang, ms.kode_shift, ms.nama_shift, ms.libur, ms.flag_batas_absen_pulang')
                    ->join('master_shift as ms', 'ms.id_master_shift', '=', 'dsk.id_master_shift')
                    ->where('dsk.id_karyawan', $id_karyawan)
                    ->where('dsk.tanggal', $tanggal)
                    ->where('ms.id_cabang', $id_cabang)
                    ->where('dsk.id_company', $id_company)
                    ->first();
            } else {

                $jenis_jam_kerja = 'shift';
                $jam_kerja = DB::table('data_shift_karyawan as dsk')
                    ->selectRaw('ms.id_master_shift, dsk.tanggal, ms.jam_masuk, ms.jam_pulang, ms.start_absen_masuk, ms.batas_absen_pulang, ms.kode_shift, ms.nama_shift, ms.libur, ms.flag_batas_absen_pulang')
                    ->join('master_shift as ms', 'ms.id_master_shift', '=', 'dsk.id_master_shift')
                    ->where('dsk.id_karyawan', $id_karyawan)
                    ->where('dsk.tanggal', $tanggal)
                    ->where('ms.id_cabang', $id_cabang)
                    ->where('dsk.id_company', $id_company)
                    ->first();
            }
        } else {

            if ($kode_absen == 'H') {
                $jenis_jam_kerja = 'reguler';
                $jam_kerja = DB::table('jam_kerja')
                    ->selectRaw('"0" AS id_master_shift, CURDATE() AS tanggal,masuk as jam_masuk, pulang as jam_pulang, start_absen_masuk, batas_absen_pulang,"" as kode_shift, "reguler" as nama_shift, "0" as flag_batas_absen_pulang')
                    ->where('hari', $dayNow)
                    ->where('id_cabang', $id_cabang)
                    ->where('id_company', $id_company)
                    ->first();
            } else {

                $jenis_jam_kerja = 'shift';
                $jam_kerja = DB::table('master_shift as ms')
                    ->selectRaw('ms.id_master_shift, CURDATE() AS tanggal, ms.jam_masuk, ms.jam_pulang, ms.start_absen_masuk, ms.batas_absen_pulang, ms.kode_shift, ms.nama_shift, ms.libur, ms.flag_batas_absen_pulang')
                    ->where('ms.kode_shift', $kode_absen)
                    ->where('ms.id_company', $id_company)
                    ->first();
                // echo($kode_absen);
                // print_r($jam_kerja);
                // die();
            }
        }
        // print_r(DB::getQueryLog());exit;
        if ($jam_kerja == null) { # JIKA JAM KERJA BELUM DI SETTING
            $response['status'] = false;
            $response['message'] = "Jam kerja belum diatur oleh admin";
        } else {
            if (!$ganti_jadwal) {
                if ($jenisJadwal == 'reguler') {
                    $keterangan     = 'Reguler';
                    $kode_absensi   = 'H';
                    $ket_kode       = 'Reguler';
                } else {
                    $keterangan     = $jam_kerja->kode_shift . " - " . $jam_kerja->nama_shift;
                    $kode_absensi   = $jam_kerja->kode_shift;
                    $ket_kode       = $jam_kerja->nama_shift;
                    $libur          = $jam_kerja->libur;
                }
            } else {
                if ($kode_absen == 'H') {
                    $keterangan     = 'Reguler';
                    $kode_absensi   = 'H';
                    $ket_kode       = 'Reguler';
                } else {
                    $keterangan     = $jam_kerja->kode_shift . " - " . $jam_kerja->nama_shift;
                    $kode_absensi   = $jam_kerja->kode_shift;
                    $ket_kode       = $jam_kerja->nama_shift;
                    $libur          = $jam_kerja->libur;
                }
            }
            // print_r($jam_kerja);
            // exit();
            $data_jam_kerja = array(
                'tanggal'       => DateFormat::format($tanggal, "N d M Y"),
                'kode_absensi'  => $kode_absensi,
                'ket_kode'      => $ket_kode,
                'keterangan'    => $keterangan,
                'jam_masuk'     => $jam_kerja->jam_masuk,
                'jam_pulang'    => $jam_kerja->jam_pulang,
                'start_absensi_masuk' => $jam_kerja->start_absen_masuk,
                'batas_absensi_pulang' => $jam_kerja->batas_absen_pulang,
                'tgl_jadwal_absensi' => $jam_kerja->tanggal,
                'id_master_shift' => $jam_kerja->id_master_shift,
                'flag_batas_absen_pulang' => $jam_kerja->flag_batas_absen_pulang

            );
            $response['status'] = true;
            $response['jenis_jam_kerja'] = $jenis_jam_kerja;
            $response['jam_kerja'] = $data_jam_kerja;
            $response['libur_shift'] = $libur ?? '0';
        }
        return $response;
    }

    private static function _getTogleButtonAbsensi($jenis_jam_kerja = null, $time_now = null, $start_absensi_masuk = null, $jam_masuk = null, $istirahat_mulai = null, $istirahat_selesai = null, $jam_pulang = null, $batas_absensi_pulang = null, $libur_shift = null, $belum_selesai_tukar_shift)
    {
        // var_dump($jenis_jam_kerja);
        // var_dump($start_absensi_masuk);
        // var_dump($jam_masuk);
        // var_dump($istirahat_mulai);
        // var_dump($istirahat_selesai);
        // exit();
        // var_dump($belum_selesai_shift_malam);


        // $time_now = '2021-09-08 03:30';
        // $time_now = date_format(date_create($time_now), 'Y-m-d H:i:s');
        // $time_now = strtotime($time_now);
        // echo "jenis_jam_kerja =".$jenis_jam_kerja."<br>";
        // echo "time_now =".date("Y-m-d H:i:s",$time_now)."<br>";
        // echo "start_absensi_masuk =".date("Y-m-d H:i:s",$start_absensi_masuk)."<br>";
        // echo "start_absensi_masuk =".$start_absensi_masuk."<br>";
        // echo "jam_masuk =".$jam_masuk."<br>";
        // echo "istirahat_mulai ; =".$istirahat_mulai."<br>";
        // echo "istirahat_selesai ; =".$istirahat_selesai."<br>" ;
        // echo "jam_pulang  =".$jam_pulang ."<br>";
        // echo "batas_absensi_pulang  =".date('Y-m-d H:i:s', $batas_absensi_pulang) ."<br>";
        // echo "libur_shift;exit =".$libur_shift."<br>";exit;



        // print_r($batas_absensi_pulang);

        $time_now = date("H:i:s", $time_now);
        $date_now = date('Y-m-d');
        $time_now = date_format(date_create($date_now . " " . $time_now), 'Y-m-d H:i:s');
        $time_now = strtotime($time_now);

        
        
        $cekAbsenMasukTS = DB::table('absensi_masuk as am')
        ->selectRaw('am.id_absensi_masuk')
        ->where('am.jenis_absen', 'tukar-shift')
        ->where('am.tgl_absen', $date_now)
        ->count();
        

        // print_r($time_now);
        // exit();

        // echo $jam_pulang;exit;

        if ($belum_selesai_tukar_shift == 1) {
            // echo("belum absen pulang tukar shift");
            // exit();
            $toggle_absen_kerja      = false;
            $enabled_absen_kerja     = false;
            $toggle_absen_istirahat  = true;
            $enabled_absen_istirahat = true;
            $toggle_absen_tukarShift = false;
            $enabled_absen_tukarShift = true;
            $keterangan_kondisi = "Belum absen pulang tukar shift";
            $keterangan_button = "";
        }
        //    else 
        else if (in_array($jenis_jam_kerja, array("reguler", "shift", "tukar-shift"))) {

            if ($libur_shift == '1') {
                # Shift libur
                $toggle_absen_kerja      = true;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = true;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = false;
                $keterangan_kondisi = "0";
                $keterangan_button = "Shift hari ini libur";
            } else if ($jam_masuk == "0000-00-00 00:00:00") {
                $toggle_absen_kerja      = true;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = true;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = false;
                $keterangan_kondisi = "Izin / Cuti";
                $keterangan_button = "Anda melakukan izin / cuti";
            } else if ($start_absensi_masuk > $time_now) {
                $toggle_absen_kerja      = true;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = true;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = false;
                $keterangan_kondisi = "1A";
                $keterangan_button = "";
                // $keterangan_button = "Absensi masuk dimulai pukul " . date("H:i", $start_absensi_masuk);
            } else if ($start_absensi_masuk <= $time_now && $jam_masuk == "" ) {
                # Sudah waktunya absen masuk dan belum absen masuk
                $toggle_absen_kerja      = true;
                $enabled_absen_kerja     = true;
                $toggle_absen_istirahat  = true;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = false;
                $keterangan_kondisi = "2";
                $keterangan_button = "";
            } 
            // else if ($start_absensi_masuk <= $time_now && $jam_masuk == "" && $cekAbsenMasukTS == '1') {
            //     # Sudah absen TS hari ini
            //     $toggle_absen_kerja      = true;
            //     $enabled_absen_kerja     = true;
            //     $toggle_absen_istirahat  = true;
            //     $enabled_absen_istirahat = false;
            //     $toggle_absen_tukarShift = true;
            //     $enabled_absen_tukarShift = false;
            //     $keterangan_kondisi = "2A";
            //     $keterangan_button = "";
            // } 
                    
                        
                            
                                
                                    else if ($jam_masuk == "" && $batas_absensi_pulang <= $time_now) {
                # Belum absen masuk dan sudah melewati batas absensi pulang
                $toggle_absen_kerja      = true;
                $enabled_absen_kerja     = true;
                $toggle_absen_istirahat  = true;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = false;
                $keterangan_kondisi = "3";
                $keterangan_button = "Batas absensi pulang pukul " . date("H:i", $batas_absensi_pulang);
            } else if ($jam_masuk != "" && $istirahat_mulai == "" && $istirahat_selesai == "" && $jam_pulang == "" && $batas_absensi_pulang >= $time_now) {
                # Sudah absen masuk dan 
                # belum istirahat mulai dan 
                # belum istirahat selesai dan 
                # belum pulang dan belum melewati batas absensi pulang
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = true;
                $toggle_absen_istirahat  = true;
                $enabled_absen_istirahat = true;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = false;
                $keterangan_kondisi = "4";
                $keterangan_button = "";
            } else if ($jam_masuk != "" && $istirahat_mulai == "" && $istirahat_selesai == "" && $jam_pulang != "" && $batas_absensi_pulang >= $time_now && $jam_masuk < $jam_pulang) {
                # Sudah absen masuk dan 
                # belum istirahat mulai dan 
                # belum istirahat selesai dan 
                # sudah pulang dan belum melewati batas absensi pulang
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = true;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = true;
                $keterangan_kondisi = "5";
                $keterangan_button = "";
            } else if ($jam_masuk != "" && $istirahat_mulai == "" && $istirahat_selesai == "" && $jam_pulang != "" && $batas_absensi_pulang >= $time_now && $cekAbsenMasukTS == '0') {
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = true;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = true;
                $keterangan_kondisi = "5A";
                $keterangan_button = "";
            } 
            else if ($jam_masuk != "" && $istirahat_mulai == "" && $istirahat_selesai == "" && $jam_pulang != "" && $batas_absensi_pulang >= $time_now && $cekAbsenMasukTS ==  '1') {
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = true;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = false;
                $keterangan_kondisi = "5B";
                $keterangan_button = "";
            } 
                    
                        else if ($jam_masuk != "" && $istirahat_mulai == "" && $istirahat_selesai == "" && $jam_pulang != "" && $batas_absensi_pulang < $time_now) {
                # Sudah absen masuk dan 
                # belum istirahat mulai dan 
                # belum istirahat selesai dan 
                # sudah pulang dan sudah melewati batas absensi pulang
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = true;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = true;
                $keterangan_kondisi = "6";
                $keterangan_button = "";
            } else if ($jam_masuk != "" && $istirahat_mulai == "" && $istirahat_selesai == "" && $jam_pulang == "" && $batas_absensi_pulang <= $time_now) {
                # sudah absen masuk dan 
                # belum istirahat mulai dan 
                # belum istirahat selesai dan 
                # belum pulang dan sudah melewati batas absensi pulang
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = true;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = true;
                $keterangan_kondisi = "7";
                $keterangan_button = "Batas absensi pulang pukul " . date("H:i", $batas_absensi_pulang);
            } else if ($jam_masuk != "" && $istirahat_mulai != "" && $istirahat_selesai == "" && $jam_pulang == "" && $batas_absensi_pulang >= $time_now) {
                # sudah absen masuk dan 
                # sudah istirahat mulai dan 
                # belum istirahat selesai dan 
                # belum pulang dan belum melewati batas absensi pulang
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = true;
                $toggle_absen_istirahat  = false;
                $enabled_absen_istirahat = true;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = false;
                $keterangan_kondisi = "8";
                $keterangan_button = "";
            } else if ($jam_masuk != "" && $istirahat_mulai != "" && $istirahat_selesai == "" && $jam_pulang == "" && $batas_absensi_pulang <= $time_now) {
                # sudah absen masuk dan 
                # sudah istirahat mulai dan 
                # belum istirahat selesai dan 
                # belum pulang dan 
                # sudah melewati batas absensi pulang
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = false;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = true;
                $keterangan_kondisi = "9";
                $keterangan_button = "Batas absensi pulang pukul " . date("H:i", $batas_absensi_pulang);
            } else if ($jam_masuk != "" && $istirahat_mulai != "" && $istirahat_selesai != "" && $jam_pulang == "" && $batas_absensi_pulang >= $time_now) {
                # sudah absen masuk dan 
                # sudah istirahat mulai dan 
                # sudah istirahat selesai dan 
                # belum pulang dan 
                # belum melewati batas absensi pulang
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = true;
                $toggle_absen_istirahat  = false;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = false;
                $keterangan_kondisi = "10";
                $keterangan_button = "";
            } else if ($jam_masuk != "" && $istirahat_mulai != "" && $istirahat_selesai != "" && $jam_pulang == "" && $batas_absensi_pulang <= $time_now) {
                # sudah absen masuk dan 
                # sudah istirahat mulai dan 
                # sudah istirahat selesai dan 
                # belum pulang dan 
                # sudah melewati batas absensi pulang
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = false;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = true;
                $keterangan_kondisi = "11";
                $keterangan_button = "Batas absensi pulang pukul " . date("H:i", $batas_absensi_pulang);
            } else if ($jam_masuk != "" && $istirahat_mulai != "" && $istirahat_selesai != "" && $jam_pulang != "" && $batas_absensi_pulang >= $time_now) {
                # sudah absen masuk dan 
                # sudah istirahat mulai dan 
                # sudah istirahat selesai dan 
                # sudah pulang dan 
                # belum melewati batas absensi pulang
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = false;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = true;
                $keterangan_kondisi = "12";
                $keterangan_button = "";
            } else if ($jam_masuk != "" && $istirahat_mulai != "" && $istirahat_selesai != "" && $jam_pulang != "" && $batas_absensi_pulang >= $time_now) {
                # sudah absen masuk dan 
                # sudah istirahat mulai dan 
                # sudah istirahat selesai dan 
                # sudah pulang dan 
                # sudah melewati batas absensi pulang
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = false;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = true;
                $keterangan_kondisi = "13";
                $keterangan_button = "";
            } else {
                $toggle_absen_kerja      = false;
                $enabled_absen_kerja     = false;
                $toggle_absen_istirahat  = false;
                $enabled_absen_istirahat = false;
                $toggle_absen_tukarShift = true;
                $enabled_absen_tukarShift = true;
                $keterangan_kondisi = "14";
                $keterangan_button = "";
            }
        } else {
            $toggle_absen_kerja      = true;
            $enabled_absen_kerja     = false;
            $toggle_absen_istirahat  = true;
            $enabled_absen_istirahat = false;
            $toggle_absen_tukarShift = true;
            $enabled_absen_tukarShift = false;
            $keterangan_kondisi = "0";
            $keterangan_button = "";
        }

        $button_absensi = array(
            'toggle_absen_kerja'        => $toggle_absen_kerja,
            'enabled_absen_kerja'       => $enabled_absen_kerja,
            'toggle_absen_istirahat'    => $toggle_absen_istirahat,
            'enabled_absen_istirahat'   => $enabled_absen_istirahat,
            'toggle_absen_tukarShift' => $toggle_absen_tukarShift,
            'enabled_absen_tukarShift' => $enabled_absen_tukarShift,
            'kondisi'                   => $keterangan_kondisi,
            'keterangan_button'         => $keterangan_button,

        );
        return $button_absensi;
    }

    private static function _dataAbsensi($id_karyawan = null, $id_company = null, $current_date = '0000-00-00', $absen_pulang)
    {

        if ($absen_pulang == '0') {
            $harisebelum = date('Y-m-d', strtotime('-1 days', strtotime(strval($current_date))));
            $select = collect(DB::select("SELECT am.tgl_absen,
                                                am.jam_absen,
                                                am.terlambat,
                                                am.lokasi_absen,
                                                am.keterangan,
                                                im.jam_absen AS ist_mulai,
                                                ise.jam_absen AS ist_selesai,
                                                ap.jam_absen AS jam_pulang,
                                                am.ganti_jadwal
                                        FROM absensi_masuk AS am
                                        LEFT JOIN istirahat_mulai AS im ON im.id_karyawan =  am.id_karyawan AND im.tgl_absen = '$current_date'
                                        LEFT JOIN istirahat_selesai AS ise ON ise.id_karyawan = am.id_karyawan AND ise.tgl_absen = '$current_date'
                                        LEFT JOIN absensi_pulang AS ap ON ap.id_karyawan = am.id_karyawan AND ap.tgl_absen = '$current_date'
                                        WHERE am.tgl_absen = '$harisebelum'
                                        AND am.id_karyawan = '$id_karyawan'
                                        AND am.id_company = '$id_company'"))->first();
            $jadwal = array(
                'jam_masuk' => ((isset($select->jam_absen) && !empty($select->jam_absen)) ? date_format(date_create($select->jam_absen), 'H:i') : ""),
                'jam_pulang' => ((isset($select->jam_pulang) && !empty($select->jam_pulang)) && ($select->jam_pulang) > ($select->jam_absen) ? date_format(date_create($select->jam_pulang), 'H:i') : ""),
                'ganti_jadwal' => ((isset($select->ganti_jadwal) && !empty($select->ganti_jadwal)) ? $select->ganti_jadwal : ""),
                'istirahat_mulai' => ((isset($select->ist_mulai) && !empty($select->ist_mulai)) ? date_format(date_create($select->ist_mulai), 'H:i') : ""),
                'istirahat_selesai' => ((isset($select->ist_selesai) && !empty($select->ist_selesai)) ? date_format(date_create($select->ist_selesai), 'H:i') : ""),
                'masuk_tukar_shift' => '',
            );
        } else {
            
            $select = collect(DB::select("SELECT am.tgl_absen,
                                                am.jam_absen,
                                                am.terlambat,
                                                am.lokasi_absen,
                                                am.keterangan,
                                                im.jam_absen AS ist_mulai,
                                                ise.jam_absen AS ist_selesai,
                                                ap.jam_absen AS jam_pulang,
                                                am.ganti_jadwal
                                        FROM absensi_masuk AS am
                                        LEFT JOIN istirahat_mulai AS im ON im.id_karyawan =  am.id_karyawan AND im.tgl_absen = '$current_date'
                                        LEFT JOIN istirahat_selesai AS ise ON ise.id_karyawan = am.id_karyawan AND ise.tgl_absen = '$current_date'
                                        LEFT JOIN absensi_pulang AS ap ON ap.id_karyawan = am.id_karyawan AND ap.tgl_absen = '$current_date'
                                        WHERE am.tgl_absen = '$current_date'
                                        AND am.id_karyawan = '$id_karyawan'
                                        AND am.id_company = '$id_company'"))->first();
                                        
                                        
            $tukar_shift = DB::table('absensi_masuk as am')
                ->selectRaw('am.jenis_absen')
                ->where('am.id_karyawan', $id_karyawan)
                ->where('am.id_company', $id_company)
                ->where('am.keterangan', 'Tukar Shift')
                ->count();

            if ($tukar_shift > 0) {
                $harisebelum = date('Y-m-d', strtotime('-1 days', strtotime(strval($current_date))));
            $selectJamKerja = collect(DB::select("SELECT am.tgl_absen,
                                                        am.jam_absen,
                                                        am.terlambat,
                                                        am.lokasi_absen,
                                                        am.keterangan,
                                                        im.jam_absen AS ist_mulai,
                                                        ise.jam_absen AS ist_selesai,
                                                        ap.jam_absen AS jam_pulang,
                                                        am.ganti_jadwal
                                                FROM absensi_masuk AS am
                                                LEFT JOIN istirahat_mulai AS im ON im.id_karyawan =  am.id_karyawan AND im.tgl_absen = '$current_date'
                                                LEFT JOIN istirahat_selesai AS ise ON ise.id_karyawan = am.id_karyawan AND ise.tgl_absen = '$current_date'
                                                LEFT JOIN absensi_pulang AS ap ON ap.id_karyawan = am.id_karyawan AND (ap.tgl_absen = '$current_date' OR ap.tgl_absen = '$harisebelum')
                                                -- LEFT JOIN absensi_pulang AS ap ON ap.id_karyawan = am.id_karyawan AND ap.tgl_absen = '$harisebelum'
                                                -- WHERE am.jenis_absen =  'reguler' OR am.jenis_absen = 'shift'
                                                WHERE am.tgl_absen = '$current_date' OR am.tgl_absen = '$harisebelum'
                                                AND am.id_karyawan = '$id_karyawan'
                                                AND am.id_company = '$id_company'"))->first();
                                                        
                                                            // print_r($selectJamKerja);
                                                            // exit();
                
                $ambil_data = DB::table('absensi_masuk as am')
                    ->selectRaw('am.jam_absen')
                    ->where('am.id_karyawan', $id_karyawan)
                    ->where('am.id_company', $id_company)
                    ->where('am.keterangan', 'Tukar Shift')
                    ->first();

                $jadwal = array(
                    'jam_masuk' => ((isset($selectJamKerja->jam_absen) && !empty($selectJamKerja->jam_absen)) ? date_format(date_create($selectJamKerja->jam_absen), 'H:i') : ""),
                    'jam_pulang' => ((isset($selectJamKerja->jam_pulang) && !empty($selectJamKerja->jam_pulang)) && ($selectJamKerja->jam_pulang) > ($selectJamKerja->jam_absen) ? date_format(date_create($selectJamKerja->jam_pulang), 'H:i') : ""),
                    'ganti_jadwal' => ((isset($selectJamKerja->ganti_jadwal) && !empty($selectJamKerja->ganti_jadwal)) ? $selectJamKerja->ganti_jadwal : ""),
                    'istirahat_mulai' => ((isset($selectJamKerja->ist_mulai) && !empty($selectJamKerja->ist_mulai)) ? date_format(date_create($selectJamKerja->ist_mulai), 'H:i') : ""),
                    'istirahat_selesai' => ((isset($selectJamKerja->ist_selesai) && !empty($selectJamKerja->ist_selesai)) ? date_format(date_create($selectJamKerja->ist_selesai), 'H:i') : ""),
                    'masuk_tukar_shift' => date_format(date_create($ambil_data->jam_absen), 'H:i'),
                );
            } else {

                $jadwal = array(
                    'jam_masuk' => ((isset($select->jam_absen) && !empty($select->jam_absen)) ? date_format(date_create($select->jam_absen), 'H:i') : ""),
                    'jam_pulang' => ((isset($select->jam_pulang) && !empty($select->jam_pulang)) && ($select->jam_pulang) > ($select->jam_absen) ? date_format(date_create($select->jam_pulang), 'H:i') : ""),
                    'ganti_jadwal' => ((isset($select->ganti_jadwal) && !empty($select->ganti_jadwal)) ? $select->ganti_jadwal : ""),
                    'istirahat_mulai' => ((isset($select->ist_mulai) && !empty($select->ist_mulai)) ? date_format(date_create($select->ist_mulai), 'H:i') : ""),
                    'istirahat_selesai' => ((isset($select->ist_selesai) && !empty($select->ist_selesai)) ? date_format(date_create($select->ist_selesai), 'H:i') : ""),
                    'masuk_tukar_shift' => ''
                );
            }
        }
        // exit();



        return $jadwal;
    }

    public static function _getLokasi($id_karyawan = null, $id_company = null, $lat_absen = null, $long_absen = null)
    {
        // DB::enableQueryLog();
        $lokasi_kantor = DB::table('data_lokasi_kantor')
            ->selectRaw('data_lokasi_kantor.nama_kantor,
                                            data_lokasi_kantor.lat_asli,
                                            data_lokasi_kantor.id_lokasi_kantor,
                                            data_lokasi_kantor.lat_min,
                                            data_lokasi_kantor.lat_max,
                                            data_lokasi_kantor.long_asli,
                                            data_lokasi_kantor.long_min,
                                            data_lokasi_kantor.long_max')
            // ->join('data_karyawan', 'data_karyawan.id_cabang', '=', 'data_lokasi_kantor.id_cabang')
            ->where('data_lokasi_kantor.id_company', $id_company);


        $data_karyawan = DB::table("data_karyawan")
            ->selectRaw("lock_lokasi, id_lokasi")
            ->where('id_karyawan', $id_karyawan)
            ->where('id_company', $id_company)
            ->first();

        if ($data_karyawan->lock_lokasi == '1') {
            if ($data_karyawan->id_lokasi != '' && $data_karyawan->id_lokasi != '0' && $data_karyawan->id_lokasi != null) {
                $id_lokasi = explode(',', $data_karyawan->id_lokasi);
                $lokasi_kantor = $lokasi_kantor->whereIn('data_lokasi_kantor.id_lokasi_kantor', $id_lokasi);
            }
            $lock_lokasi = true;
        } else {
            $lock_lokasi = false;
        }
        
        // }else{
        //     $lock_lokasi = true;
        // }

        $lokasi_kantor = $lokasi_kantor->get();

        $lokasi = false;
        if (count($lokasi_kantor) > 0) {
            foreach ($lokasi_kantor as $rows) {
                $lat_min_kantor = $rows->lat_min;
                $lat_max_kantor = $rows->lat_max;
                $long_min_kantor = $rows->long_min;
                $long_max_kantor = $rows->long_max;
                $nama_lokasi = $rows->nama_kantor;
                $id_lokasi = $rows->id_lokasi_kantor;
                if ($lokasi === false) {
                    if (($lat_absen >= $lat_min_kantor && $long_absen <= $long_min_kantor) && ($lat_absen <= $lat_max_kantor && $long_absen >= $long_max_kantor)) {
                        $lokasi = true;
                        $id_lokasi_absen = $id_lokasi;
                        $lokasi_absen = $nama_lokasi;
                    }
                }
            }

            if ($lokasi == true) {
                $response_lokasi = array(
                    'status' => true,
                    'lock_lokasi' => $lock_lokasi,
                    'boleh_absen' => true,
                    'id_lok' => $id_lokasi_absen,
                    'nama_lokasi' => $lokasi_absen,
                    'ket_lokasi' => $lokasi_absen
                );
            } else {
                if ($lock_lokasi) {
                    $boleh_absen = false;
                } else {
                    $boleh_absen = true;
                }
                $response_lokasi = array(
                    'status' => true,
                    'lock_lokasi' => $lock_lokasi,
                    'boleh_absen' => $boleh_absen,
                    'nama_lokasi' => 'Di luar radius kantor',
                    'ket_lokasi' => 'Anda berada di luar radius kantor'
                );
            }
        } else {
            $response_lokasi = array(
                'status' => false,
                'message' => 'Lokasi kantor belum disetting'
            );
        }
        return $response_lokasi;
        //END LOKASI

    }

    public static function _cekAbsensiTerakhir($id_karyawan = null, $id_company = null, $current_time = '0000-00-00')
    {
        DB::enableQueryLog();
        // $cek_absensi_terakhir = DB::table('absensi_masuk as am')
        //                             ->selectRaw('max(am.tgl_absen) as max_tanggal, am.id_absensi_masuk, am.jam_absen, am.kode_absen, dk.id_cabang, am.tgl_absen, ap.id_absensi_pulang, am.jenis_absen')
        //                             ->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'am.id_karyawan')
        //                             ->leftJoin('absensi_pulang as ap', 'ap.id_masuk', '=', 'am.id_absensi_masuk')
        //                             ->whereRaw('am.id_karyawan = ? AND am.id_company = ? ',[$id_karyawan, $id_company])
        //                             ->orderBy('am.tgl_absen', 'desc')
        //                             ->first();

        $cek_max = DB::table('absensi_masuk as am')
            ->selectRaw('max(am.tgl_absen) as max_tanggal')
            ->whereRaw('am.id_karyawan = ? AND am.id_company = ? ', [$id_karyawan, $id_company])
            ->whereIn('jenis_absen', ['reguler', 'shift'])
            ->first();

        $max_tanggal = $cek_max->max_tanggal;


        $cek_absensi_terakhir = DB::table('absensi_masuk as am')
            ->selectRaw('am.id_absensi_masuk, am.jam_absen, am.kode_absen, dk.id_cabang, am.tgl_absen, ap.id_absensi_pulang, am.jenis_absen, am.ganti_jadwal')
            ->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'am.id_karyawan')
            ->leftJoin('absensi_pulang as ap', 'ap.id_masuk', '=', 'am.id_absensi_masuk')
            ->whereRaw('am.id_karyawan = ? AND am.id_company = ? ', [$id_karyawan, $id_company])
            ->where('am.tgl_absen', $max_tanggal)
            ->first();

        $ganti = $cek_absensi_terakhir != null ? $cek_absensi_terakhir->ganti_jadwal : '0';
        if ($cek_absensi_terakhir != null && $cek_absensi_terakhir->jenis_absen == 'shift') {
            $kode_shift = $cek_absensi_terakhir->kode_absen;
            $kode_shift = explode(',', $kode_shift);
            $kode_shift = $kode_shift[0];
            $id_cabang = $cek_absensi_terakhir->id_cabang;
            if ($current_time == '0000-00-00') {
                $absensi_pulang = $current_time;
            } else {
                $absensi_pulang = DATE_FORMAT(DATE_CREATE(date('Y-m-d') . " " . $current_time), "Y-m-d H:i:s");
            }
            DB::enableQueryLog();
            $batas_tanggal_absen_pulang = date('Y-m-d');
            // $batas_tanggal_absen_pulang = '2021-09-08';  
            $cek_lewat_hari =  DB::table('master_shift as ms')
                ->selectRaw('id_master_shift')
                ->whereRaw('ms.kode_shift = ? AND ms.id_cabang = ? AND id_company = ?', [$kode_shift, $id_cabang, $id_company])
                ->whereRaw('ms.flag_jam_pulang = "1"')
                ->whereRaw('DATE_FORMAT(CONCAT("' . $batas_tanggal_absen_pulang . '"," ",ms.batas_absen_pulang),"%Y-%m-%d %H:%i:%s") > DATE_FORMAT("' . $absensi_pulang . '","%Y-%m-%d %H:%i:%s")')
                ->count();


            //  dd(DB::getQueryLog());exit;
            // var_dump($cek_lewat_hari);exit;
            if (empty($cek_absensi_terakhir->id_absensi_pulang) || $cek_absensi_terakhir->id_absensi_pulang == '') { # BELUM PULANG



                // echo "string";exit();
                $tgl_absen_pulang = $cek_absensi_terakhir->tgl_absen;

                // DB::enableQueryLog();

                $max_date_shift = date('Y-m-d', strtotime($cek_absensi_terakhir->tgl_absen . '+ 1 day'));



                $cek_jadwal =  DB::table('master_shift as ms')
                    ->selectRaw('id_master_shift')
                    ->whereRaw('ms.kode_shift = ? AND ms.id_cabang = ? AND id_company = ?', [$kode_shift, $id_cabang, $id_company])
                    ->whereRaw('ms.flag_jam_pulang = "1"')
                    ->whereRaw('DATE_FORMAT(CONCAT("' . $max_date_shift . '"," ",ms.batas_absen_pulang),"%Y-%m-%d %H:%i:%s") > DATE_FORMAT("' . $absensi_pulang . '","%Y-%m-%d %H:%i:%s")')
                    ->count();

                if ($cek_jadwal > 0) {
                    // echo("cek jadwal nol");
                    $tgl_absen_pulang = $max_date_shift;
                    // $tgl_absen_pulang = $cek_absensi_terakhir->tgl_absen;
                } else {
                    $tgl_absen_pulang = date_format(date_create($absensi_pulang), "Y-m-d");
                }
                // exit();
            } else {
                $tgl_absen_pulang = date_format(date_create($absensi_pulang), "Y-m-d");
            }

            if ($cek_lewat_hari > 0) { # JIKA LEWAT HARI

                $data['ganti_jadwal'] = $ganti;
                $data['kode_absen'] = $kode_shift;
                $data['status'] = true;
                $data['absen_pulang'] = '0';
                $data['tgl_absen'] = $tgl_absen_pulang;
                $data['keterangan'] = '0';
                // var_dump($data);exit;

                // $data['status'] = false;
            } else {
                $data['ganti_jadwal'] = $ganti;
                $data['status'] = false;
                $data['kode_absen'] = $kode_shift;
                $data['keterangan'] = '1';
            }
        } else {
            $data['ganti_jadwal'] = $ganti;
            $data['status'] = false;
            $data['kode_absen'] = 'H';
            $data['keterangan'] = '2';
        }
        return $data;
    }

    //BUAT POST ABSEN
    public static function addAbsensi($table = null, $data = null,  $dataJadwalShift = null)
    {
            
        DB::beginTransaction();
        try {
            DB::table($table)->insert($data);

            if ($table ==  'absensi_masuk') {
                // Penambahan function ketika jenis_absen adalah absen_pulang langsung akan di commit ke database,  dan ketika absen_masuk akan mengirimkan ke data_shift_karyawan
                if ($data['jenis_absen'] == 'shift') {
                    DB::table('data_shift_karyawan')->insert($dataJadwalShift);
                }
                
            } else if ($table == 'absensi_pulang') {

                $cek_tukar_shift = DB::table('absensi_masuk as am')
                    ->selectRaw('am.id_absensi_masuk')
                    ->where('am.id_karyawan', $dataJadwalShift["id_karyawan"])
                    ->where('am.id_company', $dataJadwalShift["id_company"])
                    ->where('am.keterangan', 'Tukar Shift')
                    ->count();

                if ($cek_tukar_shift > 0) {
                    $ambil_id_masuk = DB::table('absensi_masuk as am')
                        ->selectRaw('am.id_absensi_masuk')
                        ->where('am.id_karyawan', $dataJadwalShift["id_karyawan"])
                        ->where('am.id_company', $dataJadwalShift["id_company"])
                        ->where('am.keterangan', 'Tukar Shift')
                        ->first();

                    $id_masuk = $ambil_id_masuk->id_absensi_masuk;


                    DB::update("UPDATE absensi_masuk  
                        SET absensi_masuk.keterangan ='Selesai Tukar Shift' 
                     WHERE absensi_masuk.id_absensi_masuk = '$id_masuk'
                        AND absensi_masuk.jenis_absen='tukar-shift'");
                }
            }

            DB::commit();
            $response = array(
                'success' => true,
                'message' => 'Anda berhasil melakukan absen'
            );
            $json = response()->json($response, 200);
        } catch (\Exception $e) {
            DB::rollback();
            $response = array(
                'success' => false,
                'message' => $e
            );
            $json = response()->json($response, 401);
        }
        return $json;
    }

    public static function getId($id_company = null, $table = null)
    {
        $curent_month = date('Ym');
        $field = static::getFieldId($table);

        $select = collect(DB::select("SELECT MAX($field) AS id
                                        FROM $table
                                        WHERE id_company = '$id_company'
                                        AND SUBSTR($field,-11,6) = DATE_FORMAT(CURRENT_DATE(),'%Y%m')"))->first();

        if (!empty($select->id)) {
            $maxid = substr($select->id, -5);
            $nextid = $id_company . $curent_month . sprintf("%05d", ($maxid + 1));
        } else {
            $nextid = $id_company . $curent_month . '00001';
        }

        return $nextid;
    }

    private static function getFieldId($table = null)
    {
        switch ($table) {
            case "absensi_masuk":
                return "id_absensi_masuk";
                break;
            case "absensi_pulang":
                return "id_absensi_pulang";
                break;
            case "istirahat_mulai":
                return "id_istmulai";
                break;
            case "istirahat_selesai":
                return "id_istselesai";
                break;
            case "lembur_mulai":
                return "id_lemmulai";
                break;
            case "lembur_selesai":
                return "id_lemselesai";
                break;
            case "r_lembur":
                return "id_lembur";
                break;
            case "data_sos":
                return "id";
                break;
            case "data_izin":
                return "id_izin";
                break;
            case "data_aktivitas":
                return "id_aktivitas";
                break;
            case "data_patroli":
                return "id_patroli";
                break;
            case "data_shift_karyawan":
                return "id_shift_karyawan";
                break;
            default:
                return "-";
        }
    }



    public static function getRekapAbsensi($id_company = null, $id_karyawan = null, $current_month = null)
    {
        $sisa_cuti = collect(DB::select("SELECT jatah_cuti 
                                            FROM data_karyawan 
                                            WHERE id_karyawan = '$id_karyawan'
                                            AND id_company = '$id_company'"))->first();

        $select_rekap = DB::select("SELECT kode_absen,
                                                    COUNT(id_karyawan) AS jml_absensi
                                                FROM absensi_masuk
                                                WHERE id_karyawan = '$id_karyawan'
                                                    AND id_company = '$id_company'
                                                    AND DATE_FORMAT(tgl_absen,'%Y-%m') ='$current_month'
                                                GROUP BY kode_absen
                                                ORDER BY kode_absen");
        $jml_hadir = 0;
        $jml_izin = 0;
        $jml_cuti = 0;
        if ($select_rekap) {

            foreach ($select_rekap as $rows) {
                if ($rows->kode_absen == 'C') {
                    $jml_cuti = $jml_cuti + $rows->jml_absensi;
                } else if ($rows->kode_absen == 'I') {
                    $jml_cuti = $jml_cuti + $rows->jml_absensi;
                } else {
                    $jml_hadir = $jml_hadir + $rows->jml_absensi;
                }
            }
        }

        $data = array(
            'jml_hadir' => $jml_hadir,
            'jml_izin' => $jml_izin,
            'jml_cuti' => $jml_cuti,
            'sisa_cuti' => (isset($sisa_cuti->jatah_cuti)) ? $sisa_cuti->jatah_cuti : "0"
        );

        $response = array(
            'success' => true,
            'data' => $data
        );

        $json = response()->json($response, 200);

        return $json;
    }
    public static function getLastRowIAbsensiMasuk($id_karyawan, $kode_absen)
    {
        if($kode_absen == 'TS'){
            return DB::table('absensi_masuk')
            ->select('id_absensi_masuk', 'tgl_absen', 'kode_absen')
            ->where(['id_karyawan' => $id_karyawan])
            ->whereIn('jenis_absen', ['tukar-shift'])
            ->orderBy('tgl_absen', 'desc')
            ->first();
        }
        else{
            return DB::table('absensi_masuk')
            ->select('id_absensi_masuk', 'tgl_absen', 'kode_absen')
            ->where(['id_karyawan' => $id_karyawan])
            ->whereIn('jenis_absen', ['reguler', 'shift'])
            ->orderBy('tgl_absen', 'desc')
            ->first(); 
        } 
   
    }


    public static function cekAbsensiIstirahat($id_absensi_masuk)
    {
        $cek_absen_istirahat = DB::table('absensi_masuk as am')
            ->leftJoin('istirahat_mulai as im', 'im.id_absensi_masuk', '=', 'am.id_absensi_masuk')
            ->leftJoin('istirahat_selesai as is', 'is.id_absensi_masuk', '=', 'am.id_absensi_masuk')
            ->where('am.id_absensi_masuk', $id_absensi_masuk)
            ->whereNotNull('im.id_istmulai')
            ->whereNull('is.id_istselesai')
            ->first();
        return $cek_absen_istirahat;
    }



    private static function dataAbsensiHariIni($data = array())
    {
        $absensi_hari_ini = DB::table('absensi_masuk as am')
            ->select(
                'am.jenis_absen as jenis_absen',
                'am.tgl_absen',
                'am.ket_kode',
                'am.foto as foto_masuk',
                'ap.foto as foto_absen_pulang',
                'im.foto as foto_ist_mulai',
                'is.foto as foto_ist_selesai',
                'am.kode_absen as kode_absensi',
                // DB::raw(
                //     'IF(am.terlambat != 0 && ap.pulang_cepat != 0, "H,(T),(PC)", 
                //     IF(am.terlambat != 0, "H,(T)", 
                //     IF(ap.pulang_cepat !=0, "H,(PC)", "H"))) 
                //     as kode_absensi'),
                'am.jam_absen as absen_masuk',
                'ap.jam_absen as absen_pulang',
                'am.terlambat',
                'am.lokasi_absen as lokasi_masuk',
                'ap.lokasi_absen as lokasi_pulang',
                'im.lokasi_absen as lokasi_ist_mulai',
                'is.lokasi_absen as lokasi_ist_selesai',
                'am.keterangan as keterangan_masuk',
                'ap.keterangan as keterangan_pulang',
                'im.jam_absen AS ist_mulai',
                'is.jam_absen AS ist_selesai',
                'is.keterangan as keterangan_istirahat_selesai',
                'im.keterangan as keterangan_istirahat_mulai',
                'ap.jam_absen AS jam_pulang',
                'md.nama as nama_departemen',
                'mc.nama as nama_cabang',
                'dk.nama_lengkap as nama_karyawan'
            )
            ->leftJoin('absensi_pulang as ap', 'ap.id_masuk', '=', 'am.id_absensi_masuk')
            ->leftJoin('istirahat_mulai as im', 'im.id_absensi_masuk', '=', 'am.id_absensi_masuk')
            ->leftJoin('istirahat_selesai as is', 'is.id_absensi_masuk', '=', 'am.id_absensi_masuk')
            ->join('data_karyawan AS dk', 'dk.id_karyawan', '=', 'am.id_karyawan')
            ->join('master_jabatan as mj', 'mj.id_jabatan', '=', 'dk.id_jabatan')
            ->join('usergroup as ug', 'ug.id', '=', 'mj.level_user')
            ->leftJoin('master_departemen as md', 'md.id_departemen', '=', 'dk.id_departemen')
            ->join('master_cabang as mc', 'mc.id_cabang', '=', 'dk.id_cabang')
            ->where(['am.id_company' => $data['id_company'], 'am.tgl_absen' => date('Y-m-d')]);
        if ($data['konteks'] == 'absensiSaya') {
            $absensi_hari_ini = $absensi_hari_ini->where(array('am.id_karyawan' => $data['id_karyawan']));
        } else if ($data['konteks'] == 'absensiPegawai') {
            if ($data['id_select_karyawan'] != null) {
                $absensi_hari_ini = $absensi_hari_ini->where(array('am.id_karyawan' => $data['id_select_karyawan']));
            }
            if ($data['id_departemen'] != null) {
                $absensi_hari_ini = $absensi_hari_ini->where(array('dk.id_departemen' => $data['id_departemen']));
            }
            if ($data['id_cabang'] != null)
                $absensi_hari_ini = $absensi_hari_ini->where(array('dk.id_cabang' => $data['id_cabang']));
            if ($data['level_user'] == '7') { //JIKA SPV REQUEST
                $absensi_hari_ini = $absensi_hari_ini->where(array('dk.supervisi' => $data['id_karyawan']));
                $absensi_hari_ini = $absensi_hari_ini->whereIn('ug.id', ['3']); //Staff
            } else if ($data['level_user'] == '2') { //JIKA KEDEP REQUEST
                $absensi_hari_ini = $absensi_hari_ini->whereIn('ug.id', ['7', '3']); //SPV dan Staff
                $p_kedep = User_m::getPKedep($data['id_karyawan']);
                if (count($p_kedep) == 0) { //HANYA MENJABAT 1 KEPALA DEPARTEMEN
                    $absensi_hari_ini = $absensi_hari_ini->where('dk.id_departemen', User_m::get_data_karyawan($data['id_karyawan'])->id_departemen);
                } else { //BISA MENJABAT LEBIH DARI 1 KEPALA DEPARTEMEN
                    $list_id_departemen = array();
                    foreach ($p_kedep as $row_kedep) {
                        $list_id_departemen[] = $row_kedep->id_departemen;
                    }
                    $absensi_hari_ini = $absensi_hari_ini->whereIn('dk.id_departemen', $list_id_departemen);
                }
            } else if ($data['level_user'] == '5') { //JIKA KACAB REQUEST
                $absensi_hari_ini = $absensi_hari_ini->whereIn('ug.id', ['7', '3', '2']); //Kedep, SPV dan Staff
                $p_kacab = User_m::getPKacab($data['id_karyawan']);
                if (count($p_kacab) == 0) { //HANYA MENJABAT 1 KEPALA CABANG
                    $absensi_hari_ini = $absensi_hari_ini->where('dk.id_cabang', User_m::get_data_karyawan($data['id_karyawan'])->id_cabang);
                } else { //BISA MENJABAT LEBIH DARI 1 KEPALA CABANG
                    $list_id_cabang = array();
                    foreach ($p_kacab as $row_kacab) {
                        $list_id_cabang[] = $row_kacab->id_cabang;
                    }
                    $absensi_hari_ini = $absensi_hari_ini->whereIn('dk.id_cabang', $list_id_cabang);
                }
            } else if ($data['level_user'] == '4') //JIKA DIREKSI REQUEST
                $absensi_hari_ini = $absensi_hari_ini->whereIn('ug.id', ['5', '2', '7', '3']); //Kacab, Kedep, SPV dan Staff
        }
        if ($data['bulan_tahun'] != null)
            $absensi_hari_ini = $absensi_hari_ini->where('am.tgl_absen', 'like', '%' . $data['bulan_tahun'] . '%');

        if ($data['range_tanggal_mulai'] != null && $data['range_tanggal_selesai']) {
            $absensi_hari_ini = $absensi_hari_ini->where('am.tgl_absen', '>=', $data['range_tanggal_mulai']);
            $absensi_hari_ini = $absensi_hari_ini->where('am.tgl_absen', '<=', $data['range_tanggal_selesai']);
        }

        return $absensi_hari_ini;
    }

    public static function dataAbsensi($data = array())
    {
        $select_absensi = DB::table('r_absensi AS ra')->select(
            'ra.jenis_absen as jenis_absen',
            'tgl_absen',
            'ket_kode',
            'foto_masuk',
            'foto_absen_pulang',
            'foto_ist_mulai',
            'foto_ist_selesai',
            'kode_absensi',
            'absen_masuk',
            'absen_pulang',
            'terlambat',
            'lokasi_masuk',
            'lokasi_pulang',
            'lokasi_ist_mulai',
            'lokasi_ist_selesai',
            'ket_absen_masuk AS keterangan_masuk',
            'ket_absen_pulang AS keterangan_pulang',
            'absen_ist_mulai AS ist_mulai',
            'absen_ist_selesai AS ist_selesai',
            'ket_ist_selesai as keterangan_istirahat_selesai',
            'ket_ist_mulai as keterangan_istirahat_mulai',
            'absen_pulang AS jam_pulang',
            'ra.nama_departemen',
            'ra.nama_cabang',
            'ra.nama_karyawan'
        )
            ->join('data_karyawan AS dk', 'dk.id_karyawan', '=', 'ra.id_karyawan')
            ->join('master_jabatan as mj', 'mj.id_jabatan', '=', 'dk.id_jabatan')
            ->join('usergroup as ug', 'ug.id', '=', 'mj.level_user')
            ->where(['ra.id_company' => $data['id_company']])
            ->where([array('ra.tgl_absen', '<=', date('Y-m-d'))]);

        if ($data['konteks'] == 'absensiSaya')
            $select_absensi = $select_absensi->where(array('ra.id_karyawan' => $data['id_karyawan']));
        else if ($data['konteks'] == 'absensiPegawai') {
            if ($data['id_select_karyawan'] != null)
                $select_absensi = $select_absensi->where(array('ra.id_karyawan' => $data['id_select_karyawan']));
            if ($data['id_departemen'] != null)
                $select_absensi = $select_absensi->where(array('ra.id_departemen' => $data['id_departemen']));
            if ($data['id_cabang'] != null)
                $select_absensi = $select_absensi->where(array('ra.id_cabang' => $data['id_cabang']));
            if ($data['level_user'] == '7') { //JIKA SPV REQUEST
                $select_absensi = $select_absensi->where(array('dk.supervisi' => $data['id_karyawan']));
                $select_absensi = $select_absensi->whereIn('ug.id', ['3']); //Staff
            } else if ($data['level_user'] == '2') //JIKA KEDEP REQUEST
            {
                $select_absensi = $select_absensi->whereIn('ug.id', ['7', '3']); //SPV dan Staff
                $p_kedep = User_m::getPKedep($data['id_karyawan']);
                if (count($p_kedep) == 0) { //HANYA MENJABAT 1 KEPALA DEPARTEMEN
                    $select_absensi = $select_absensi->where('dk.id_departemen', User_m::get_data_karyawan($data['id_karyawan'])->id_departemen);
                } else { //BISA MENJABAT LEBIH DARI 1 KEPALA DEPARTEMEN
                    $list_id_departemen = array();
                    foreach ($p_kedep as $row_kedep) {
                        $list_id_departemen[] = $row_kedep->id_departemen;
                    }
                    $select_absensi = $select_absensi->whereIn('dk.id_departemen', $list_id_departemen);
                }
            } else if ($data['level_user'] == '5') //JIKA KACAB REQUEST
            {
                $select_absensi = $select_absensi->whereIn('ug.id', ['7', '3', '2']); //Kedep, SPV dan Staff
                $p_kacab = User_m::getPKacab($data['id_karyawan']);
                if (count($p_kacab) == 0) { //HANYA MENJABAT 1 KEPALA CABANG
                    $select_absensi = $select_absensi->where('dk.id_cabang', User_m::get_data_karyawan($data['id_karyawan'])->id_cabang);
                } else { //BISA MENJABAT LEBIH DARI 1 KEPALA CABANG
                    $list_id_cabang = array();
                    foreach ($p_kacab as $row_kacab) {
                        $list_id_cabang[] = $row_kacab->id_cabang;
                    }
                    $select_absensi = $select_absensi->whereIn('dk.id_cabang', $list_id_cabang);
                }
            } else if ($data['level_user'] == '4') //JIKA DIREKSI REQUEST
                $select_absensi = $select_absensi->whereIn('ug.id', ['5', '2', '7', '3']); //Kacab, Kedep, SPV dan Staff
        }
        if ($data['bulan_tahun'] != null)
            $select_absensi = $select_absensi->where('tgl_absen', 'like', '%' . $data['bulan_tahun'] . '%');

        if ($data['range_tanggal_mulai'] != null && $data['range_tanggal_selesai'] != null) {
            $select_absensi = $select_absensi->where('tgl_absen', '>=', $data['range_tanggal_mulai']);
            $select_absensi = $select_absensi->where('tgl_absen', '<=', $data['range_tanggal_selesai']);
        }

        DB::enableQueryLog();
        // 

        $select_absensi = self::dataAbsensiHariIni($data)->union($select_absensi)
            ->limit($data['limit'])->offset($data['offset'])
            ->orderBy('tgl_absen', 'DESC')
            ->orderBy('absen_masuk', 'DESC')
            ->get();

        // dd(DB::getQueryLog());exit;

        // $select_absensi = DB::get($select_absensi);
        $path_foto = "absensi/" . env('NAME_APPLICATION') . "/";
        $data_absensi = array();
        if (count($select_absensi) > 0) {
            foreach ($select_absensi as $rows) {
                $kode_absensi = array();
                foreach (self::getContents($rows->kode_absensi, '(', ')') as $row) {
                    $kode_absensi[] = ['kode_absensi' => $row, 'ket_kode_absensi' =>  self::convertKodeAbsensi($row)];
                }
                $data_absensi[] = array(
                    'tgl_absensi'   => DateFormat::format($rows->tgl_absen, 'N d M Y'),
                    'absen_masuk'   => $rows->absen_masuk != null && $rows->absen_masuk != '0000-00-00 00:00:00' ? date_format(date_create($rows->absen_masuk), 'H:i') . ' - ' . $rows->lokasi_masuk : '-',
                    'absen_pulang'  => $rows->absen_pulang != null  && $rows->absen_pulang != '0000-00-00 00:00:00' ? date_format(date_create($rows->absen_pulang), 'H:i') . ' - ' . $rows->lokasi_pulang : '-',
                    'terlambat'     => $rows->terlambat,
                    'keterangan_masuk'    => $rows->keterangan_masuk != null && $rows->keterangan_masuk != ''&& $rows->ket_kode != 'Tukar Shift'  ? $rows->keterangan_masuk : '-',
                    'keterangan_pulang'   => $rows->keterangan_pulang != null && $rows->keterangan_pulang != '' ? $rows->keterangan_pulang : '-',
                    'ist_mulai'     => $rows->ist_mulai != null && $rows->ist_mulai != '0000-00-00 00:00:00' ? date_format(date_create($rows->ist_mulai), 'H:i') . ' - ' . $rows->lokasi_ist_mulai : '-',
                    'ist_selesai'   => $rows->ist_selesai != null && $rows->ist_selesai != '0000-00-00 00:00:00' ? date_format(date_create($rows->ist_selesai), 'H:i') . ' - ' . $rows->lokasi_ist_selesai : '-',
                    'keterangan_istirahat_mulai' => $rows->keterangan_istirahat_mulai != null && $rows->keterangan_istirahat_mulai != '' ? $rows->keterangan_istirahat_mulai : '-',
                    'keterangan_istirahat_selesai' => $rows->keterangan_istirahat_selesai != null && $rows->keterangan_istirahat_selesai != '' ? $rows->keterangan_istirahat_selesai : '-',
                    'jam_pulang'    => $rows->jam_pulang,
                    'kode_absensi'  => $kode_absensi,
                    'ket_kode'      => $rows->ket_kode != null ? $rows->ket_kode : 'Hadir',
                    'foto_masuk'    => ($rows->foto_masuk == '' ? '-' : (Uploads_c::cekFoto($path_foto . $rows->foto_masuk) ? Uploads_c::retrieve_file_url($rows->foto_masuk, 'photo') : '-')),
                    'foto_pulang'    => ($rows->foto_absen_pulang == '' ? '-' : (Uploads_c::cekFoto($path_foto . $rows->foto_absen_pulang) ? Uploads_c::retrieve_file_url($rows->foto_absen_pulang, 'photo') : '-')),
                    'foto_ist_mulai'    => ($rows->foto_ist_mulai == '' ? '-' : (Uploads_c::cekFoto($path_foto . $rows->foto_ist_mulai) ? Uploads_c::retrieve_file_url($rows->foto_ist_mulai, 'photo') : '-')),
                    'foto_ist_selesai'    => ($rows->foto_ist_selesai == '' ? '-' : (Uploads_c::cekFoto($path_foto . $rows->foto_ist_selesai) ? Uploads_c::retrieve_file_url($rows->foto_ist_selesai, 'photo') : '-')),
                    'nama_departemen'   => $rows->nama_departemen,
                    'nama_cabang'       => $rows->nama_cabang,
                    'nama_karyawan'     => $rows->nama_karyawan,
                    'warna'             => self::warnaJenisAbsen($rows->jenis_absen)
                );
            }
            if (count($data_absensi) > 0) {
                $response = array(
                    'success' => true,
                    'data' => $data_absensi
                );
            } else {
                $response = array(
                    'success' => false,
                    'message' => 'Data tidak ditemukan.'
                );
            }
        } else {
            $response = array(
                'success' => false,
                'message' => 'Data tidak ditemukan?'
            );
        }
        return response()->json($response, 200);
    }



    public static function insertIstirahatSelesai($data = array())
    {
        return DB::table('istirahat_selesai')
            ->insert($data);
    }



    private static function convertKodeAbsensi($kode_absensi)
    {
        if ($kode_absensi == 'T') return "Terlambat Absen Masuk";
        else if ($kode_absensi == 'TAP') return "Tidak Absen Pulang";
        else if ($kode_absensi == 'PC') return "Pulang Cepat";
        else if ($kode_absensi == 'IL') return "Istirahat Lebih";
        /*else if($kode_absensi == 'LP') return "Lupa Absen Pulang";*/
        else return "";
    }

    private static function getContents($str, $startDelimiter, $endDelimiter)
    {
        $contents = array();
        $startDelimiterLength = strlen($startDelimiter);
        $endDelimiterLength = strlen($endDelimiter);
        $startFrom = $contentStart = $contentEnd = 0;
        while (false !== ($contentStart = strpos($str, $startDelimiter, $startFrom))) {
            $contentStart += $startDelimiterLength;
            $contentEnd = strpos($str, $endDelimiter, $contentStart);
            if (false === $contentEnd) {
                break;
            }
            $contents[] = substr($str, $contentStart, $contentEnd - $contentStart);
            $startFrom = $contentEnd + $endDelimiterLength;
        }
        return $contents;
    }

    public static function cekJadwalShiftKaryawan($id_karyawan, $tanggal)
    {
        return DB::table('data_shift_karyawan as dsk')
            ->join('master_shift as ms', 'ms.id_master_shift', '=', 'dsk.id_master_shift')
            ->where([
                'ms.libur' => '0',
                'dsk.id_karyawan' => $id_karyawan,
                'dsk.tanggal' => $tanggal
            ])->count();
    }

    public static function cekHolidays($id_company, $tanggal)
    {
        return DB::table('holidays')->where([
            'id_company' => $id_company,
            'tanggal'    => $tanggal
        ])->count();
    }

    public static function cekWorkingDays($id_company, $id_cabang, $tanggal)
    {
        $daysOfWeek = date('w', strtotime($tanggal));
        if ($daysOfWeek == '0') $daysOfWeek = '7';
        return DB::table('jam_kerja')->where([
            'libur'      => '0',
            'id_company' => $id_company,
            'id_cabang'  => $id_cabang,
            'hari'       => $daysOfWeek
        ])->count();
    }

    public static function cekHariKerjaKaryawan($id_karyawan, $id_cabang, $id_company, $tanggal)
    {
        //cek shift
        if (Absensi_m::cekJadwalShiftKaryawan($id_karyawan, $tanggal) > 0) {
            return true;
        }
        //cek holidays
        else if (Absensi_m::cekHolidays($id_company, $tanggal) == 0) {
            //cek hari kerja
            if (Absensi_m::cekWorkingDays($id_company, $id_cabang, $tanggal) > 0) {
                return true;
            } else return false;
        } else return false;
        //
    }

    public static function getBebasPilihShift($tanggal, $jam_sekarang, $id_karyawan, $id_cabang, $id_company, $id_departemen)
    {
        // $cekPengaturan = DB::table('p_jadwal_kerja')
        //                    ->select('id_cabang')
        //                    ->where('id_cabang','=',$id_cabang)
        //                    ->where('id_company','=',$id_company)
        //                    ->where('flag','=','0')
        //                    ->count();
        // $jam_sekarang = '2022-09-22 21:00:00';
        $cekPengaturan = 1;
        // echo $tanggal;exit;
        if ($cekPengaturan > 0) {
            $flagPengaturan = true;

            //REGULER

            $day_now = date('N');
            $jenisJadwal = 'reguler';
            $jadwal_absensi = self::_getJadwalAbsensi($id_cabang, $id_company, $tanggal, $jenisJadwal, $id_karyawan, $day_now);
            $reguler = $jadwal_absensi['jam_kerja'];
            if (($tanggal . ' ' . $reguler['start_absensi_masuk'] <= $jam_sekarang) && ($tanggal . ' ' . $reguler['batas_absensi_pulang'] >= $jam_sekarang)) {
                $flagP = true;
            } else {
                $flagP = false;
            }

            $reguler = array(
                'id_shift'   => $reguler['id_master_shift'],
                'kode_shift' => $reguler['kode_absensi'],
                'nama_shift' => $reguler['ket_kode'],
                'jam_masuk'  => $reguler['jam_masuk'],
                'jam_pulang' => $reguler['jam_pulang'],
                'libur'      => '0',
                'flag'       => true
            );

            //END REGULER

            $dataShift = DB::table('master_shift')
                ->where('id_cabang', '=', $id_cabang)
                ->where('id_company', '=', $id_company);
            // if (!empty($id_departemen)) 
            //     $dataShift = $dataShift->where('id_departemen',$id_departemen);




            $dataShift = $dataShift->get();
            if (count($dataShift) > 0) {
                $cek_shift = DB::table('data_shift_karyawan')
                    ->where('id_karyawan', $id_karyawan)
                    ->where('id_company', $id_company)
                    ->where('tanggal', $tanggal)
                    ->first();
                if ($cek_shift != NULL) { //ADA SHIFT
                    //HARUSNYA TIDAK PERLU QUERY LG

                    $shift_hari_ini = DB::table('master_shift')
                        ->select(
                            'id_master_shift as id_shift',
                            'kode_shift',
                            'nama_shift',
                            'jam_masuk',
                            'jam_pulang',
                            'libur',
                            'start_absen_masuk',
                            'batas_absen_pulang'
                        )
                        ->where('id_master_shift', $cek_shift->id_master_shift)
                        ->where('id_company', $id_company)
                        ->first();

                    if (($tanggal . ' ' . $shift_hari_ini->start_absen_masuk <= $jam_sekarang) && ($tanggal . ' ' . $shift_hari_ini->batas_absen_pulang >= $jam_sekarang)) {
                        $shift_hari_ini->flag = true;
                    } else {
                        $shift_hari_ini->flag = false;
                    }

                    $default = $shift_hari_ini;
                } else { //REGULER
                    $default = $reguler;
                }
                foreach ($dataShift as $rows) {
                    if ($rows->flag_batas_absen_pulang == '1') {
                        $cdate = date('Y-m-d', strtotime('+1 days', strtotime(strval($tanggal))));
                    } else {
                        $cdate = $tanggal;
                    }
                    if (($tanggal . ' ' . $rows->start_absen_masuk <= $jam_sekarang) && ($cdate . ' ' . $rows->batas_absen_pulang >= $jam_sekarang)) {
                        $flagP = true;
                    } else {
                        $flagP = false;
                    }
                    if ($rows->libur == '1') {
                        $flagP = true;
                    }
                    $data[] = array(
                        'id_shift'   => $rows->id_master_shift,
                        'kode_shift' => $rows->kode_shift,
                        'nama_shift' => $rows->nama_shift,
                        'jam_masuk'  => $rows->jam_masuk,
                        'jam_pulang' => $rows->jam_pulang,
                        'libur'      => $rows->libur,
                        'flag'       => $flagP
                    );
                }

                // array_push($data,)
                $hide_menu = array(
                    'id_shift'   => '100',
                    'kode_shift' => '',
                    'nama_shift' => '',
                    'jam_masuk'  => '00:00:00',
                    'jam_pulang' => '00:00:00',
                    'libur'      => '0',
                    'flag'       => true
                );
                array_unshift($data, $hide_menu);
                array_unshift($data, $reguler);
                $result = array('flag_pengaturan' => $flagPengaturan, 'message' => 'Ada shift', 'default' => $default, 'data_shift' => $data);
            } else {
                $result = array('flag_pengaturan' => false, 'message' => 'Pengaturan shift belum dibuat oleh admin', 'default' => null, 'data_shift' => []);
            }
        } else {
            $flagPengaturan = false;
            $result = array('flag_pengaturan' => $flagPengaturan, 'message' => 'Anda tidak diatur untuk pilih shift secara mandiri oleh admin', 'data_shift' => []);
        }

        $json = response()->json($result, 200);
        return $json;
    }
}
