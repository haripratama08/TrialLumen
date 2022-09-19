<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use App\Http\Helpers\DateFormat;

    class Jam_kerja_m extends Model{

        public static function getJamKerja($id_company = null, $id_karyawan = null, $current_date = null, $day_now = null){
            $cek_izin = DB::table('absensi_masuk')
                            ->select('ket_kode')
                            ->whereIn('jenis_absen',['sakit','izin','cuti'])
                            ->where('id_karyawan', $id_karyawan)
                            ->where('id_company', $id_company)
                            ->where('tgl_absen', $current_date);

            if($cek_izin->count()>0){
                $data = array(
                    'jadwal_kerja'=>$cek_izin->first()->ket_kode,
                    'jam_kerja'     => array('tanggal' => DateFormat::format($current_date,'N d M Y'))
                );
                $response = array(
                    'success'=>true,
                    'data'=>$data
                );
                $json = response()->json($response,200);
            }
            else{
                $jadwal_shift = collect(DB::select("SELECT data_shift_karyawan.tanggal,
                                                        master_shift.jam_masuk,
                                                        master_shift.jam_pulang,
                                                        master_shift.kode_shift,
                                                        master_shift.nama_shift,
                                                        master_shift.libur
                                                FROM data_shift_karyawan
                                                LEFT JOIN master_shift ON master_shift.id_master_shift = data_shift_karyawan.id_master_shift
                                                WHERE data_shift_karyawan.id_company = '$id_company'
                                                    AND data_shift_karyawan.id_karyawan = '$id_karyawan'
                                                    AND data_shift_karyawan.tanggal = '$current_date'"));

                if($jadwal_shift->count() > 0){
                    $row = $jadwal_shift->first();
                    if($row->libur == '1'){
                        $data = array('jadwal_kerja'=>'Libur');
                    }else{
                        $data = array('jadwal_kerja'=>'Shift',
                                        'jam_kerja'=>array('tanggal'=>DateFormat::format($current_date,'N d M Y'),
                                                            'jam_masuk'=>$row->jam_masuk,
                                                            'jam_pulang'=>$row->jam_pulang,
                                                            'kode_absensi'=>$row->kode_shift,
                                                            'keterangan'=>$row->nama_shift
                                                        ),
                                        'data_absensi' => self::dataAbsensiHariIni($id_karyawan, $current_date)
                                    );
                    }
                    $response = array('success'=>true,
                                        'data'=>$data);

                    $json = response()->json($response,200);

                }else{
                    $holidays = collect(DB::select("SELECT id FROM holidays WHERE id_company='$id_company' AND tanggal='$current_date'"));
                    if($holidays->count() > 0){
                        $row = $holidays->first();
                        $data = array('jadwal_kerja'=>'Libur');

                        $response = array('success'=>true,
                                            'data'=>$data);

                        $json = response()->json($response,200);

                    }else{
                        $reguler = collect(DB::select("SELECT jam_kerja.libur,
                                                            jam_kerja.masuk,
                                                            jam_kerja.pulang
                                                        FROM jam_kerja
                                                        JOIN data_karyawan ON data_karyawan.id_company =  jam_kerja.id_company
                                                            AND data_karyawan.id_cabang = jam_kerja.id_cabang
                                                            AND data_karyawan.id_karyawan = '$id_karyawan'
                                                            AND data_karyawan.id_company = '$id_company'
                                                        WHERE jam_kerja.id_company='$id_company' AND hari='$day_now'"));

                        if($reguler->count() > 0){
                            $row = $reguler->first();
                            if($row->libur == '1'){
                                $data = array('jadwal_kerja'=>'Libur');

                                $response = array('success'=>true,
                                                    'data'=>$data);

                                $json = response()->json($response,200);
                            }else{
                                $data = array('jadwal_kerja'=>'Reguler',
                                            'jam_kerja'=>array('tanggal'=>DateFormat::format($current_date,'N d M Y'),
                                                                'jam_masuk'=>date_format(date_create($row->masuk), "H:i"),
                                                                'jam_pulang'=>date_format(date_create($row->pulang), "H:i"),
                                                                'kode_absensi'=>'H',
                                                                'keterangan'=>'Hadir'),
                                            'data_absensi' => self::dataAbsensiHariIni($id_karyawan, $current_date)
                                        );

                                $response = array('success'=>true,
                                                    'data'=>$data);

                                $json = response()->json($response,200);
                            }

                        }else{
                            $response = array('success'=>false,
                                                'message'=>'Jadwal kerja belum ada');

                            $json = response()->json($response,401);
                        }

                    }
                }
            }
            
            

            return $json;
        }

        private static function convertTimeZoneIndonesia($timezone){
            $data = array(
                'Asia/Jakarta' => 'WIB',
                'Asia/Makassar' => 'WITA',
                'Asia/Jayapura' => 'WIT'
            );
            return $data[$timezone]??'';
        }

        public static function dataAbsensiHariIni($id_karyawan, $current_date){
            $data = DB::table('absensi_masuk as am')->select('am.jam_absen as absen_masuk', 'ap.jam_absen as absen_pulang', 'am.timezone as timezone_masuk', 'ap.timezone as timezone_pulang')
            ->leftJoin('absensi_pulang as ap', 'ap.id_masuk', '=', 'am.id_absensi_masuk')
            ->where('am.id_karyawan', $id_karyawan)
            ->where('am.tgl_absen', $current_date)
            ->orderBy('am.jam_absen', 'desc')
            // ->where(function ($query) use($current_date){
            //         $query->where('am.tgl_absen', $current_date)
            //               ->orWhere('ap.tgl_absen', $current_date);
            //     })
            
            ->first();
            if ($data!=null) {
                $data_absensi = array(
                    'absen_masuk'   => $data->absen_masuk!=''?
                    date_format(date_create($data->absen_masuk),'H:i').' '.self::convertTimeZoneIndonesia($data->timezone_masuk):'-',
                    'absen_pulang'  => $data->absen_pulang!=''?
                    date_format(date_create($data->absen_pulang),'H:i').' '.self::convertTimeZoneIndonesia($data->timezone_pulang):'-',
                );
            }
            else{
                $data_absensi = array(
                    'absen_masuk'   => '-',
                    'absen_pulang'  => '-'
                );
            }
            return $data_absensi;
        }

        public static function getJamKerjaRaw($id_company = null, $id_karyawan = null, $current_date = null, $day_now = null){
            $jadwal_shift = collect(DB::select("SELECT data_shift_karyawan.tanggal,
                                                        master_shift.jam_masuk,
                                                        master_shift.jam_pulang,
                                                        master_shift.kode_shift,
                                                        master_shift.nama_shift,
                                                        master_shift.libur
                                                FROM data_shift_karyawan
                                                LEFT JOIN master_shift ON master_shift.id_master_shift = data_shift_karyawan.id_master_shift
                                                WHERE data_shift_karyawan.id_company = '$id_company'
                                                    AND data_shift_karyawan.id_karyawan = '$id_karyawan'
                                                    AND data_shift_karyawan.tanggal = '$current_date'"));

            if($jadwal_shift->count() > 0){
                $row = $jadwal_shift->first();
                if($row->libur == '1'){
                    $data = array('jadwal_kerja'=>'libur');
                }else{
                    $data = array('jadwal_kerja'=>'shift',
                                    'jam_kerja'=>array('tanggal'=>DateFormat::format($current_date,'N d M Y'),
                                                        'jam_masuk'=>$row->jam_masuk,
                                                        'jam_pulang'=>$row->jam_pulang,
                                                        'kode_absensi'=>$row->kode_shift,
                                                        'keterangan'=>$row->nama_shift));
                }
                return $data;

            }else{
                $holidays = collect(DB::select("SELECT id FROM holidays WHERE id_company='$id_company' AND tanggal='$current_date'"));
                if($holidays->count() > 0){
                    $row = $holidays->first();
                    $data = array('jadwal_kerja'=>'libur');
                }else{
                    $reguler = collect(DB::select("SELECT jam_kerja.libur,
                                                        jam_kerja.masuk,
                                                        jam_kerja.pulang
                                                    FROM jam_kerja
                                                    JOIN data_karyawan ON data_karyawan.id_company =  jam_kerja.id_company
                                                        AND data_karyawan.id_cabang = jam_kerja.id_cabang
                                                        AND data_karyawan.id_karyawan = '$id_karyawan'
                                                        AND data_karyawan.id_company = '$id_company'
                                                    WHERE jam_kerja.id_company='$id_company' AND hari='$day_now'"));

                    if($reguler->count() > 0){
                        $row = $reguler->first();
                        if($row->libur == '1'){
                            $data = array('jadwal_kerja'=>'libur');
                        }else{
                            $data = array('jadwal_kerja'=>'reguler',
                                        'jam_kerja'=>array('tanggal'=>DateFormat::format($current_date,'N d M Y'),
                                                            'jam_masuk'=>$row->masuk,
                                                            'jam_pulang'=>$row->pulang,
                                                            'kode_absensi'=>'H',
                                                            'keterangan'=>'Hadir'));
                        }
                    }
                }
                return $data;
            }

            
        }
    }