<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use URL;
    use App\Http\Helpers\DateFormat;
    class Gaji_m extends Model{

        public static function getDataGaji($id_karyawan, $month_year){
            $split_periode = explode('-', $month_year);
            $periode = $split_periode[1]."-".$split_periode[0];
            // $periode = $month_year;
            $data_gaji = DB::table('informasi_gaji')
            ->select('nik', 'nama_karyawan', 'id',
                'gaji_pokok', 't_gross_up', 't_direksi', 't_area','t_jabatan', 't_transport', 't_makan', 't_lembur', 't_kehadiran', 't_shift', 't_kelebihan_jam_kerja', 't_insentif_libur_nasional', 't_insentif', 't_lainlain', 'total_tunjangan',
                'pot_admin_payroll', 'pot_absen_bko', 'pot_terlambat', 'pot_lalai', 'pot_bpjs_kesehatan', 'pot_bpjs_pensiun', 'pot_jht', 'pot_lainlain', 'total_potongan',
                'rapelan', 'benefit_jkk', 'benefit_jkm', 'benefit_jht', 'benefit_jaminan_pensiun', 'benefit_bpjs_kesehatan', 'benefit_lainlain',
                'take_home_pay'
                ) 
            ->where('id_karyawan', $id_karyawan)
            ->where('publish', '1')
            ->where('periode', $periode)
            ->first();

            if($data_gaji == null){
                $data = [];
            }else{
                # PENERIMAAN
                $gaji_pokok = array(
                    'judul' => 'Gaji Pokok',
                    'value' => 'Rp. '.strval(number_format($data_gaji->gaji_pokok)),
                    'bold'  => false
                );
                
                $t_gross_up = array(
                    'judul' => 'Tunjangan Gross Up',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_gross_up)),
                    'bold'  => false
                );
                $t_direksi = array(
                    'judul' => 'Tunjangan Direksi',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_direksi)),
                    'bold'  => false
                );

                $t_area = array(
                    'judul' => 'Tunjangan Area',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_area)),
                    'bold'  => false
                );
                
                $t_jabatan = array(
                    'judul' => 'Tunjangan Jabatan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_jabatan)),
                    'bold'  => false
                );

                $t_transport = array(
                    'judul' => 'Tunjangan Tranport',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_transport)),
                    'bold'  => false
                );

                $t_makan = array(
                    'judul' => 'Tunjangan Makan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_makan)),
                    'bold'  => false
                );

                $t_lembur = array(
                    'judul' => 'Tunjangan Lembur',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_lembur)),
                    'bold'  => false
                );

                $t_kehadiran = array(
                    'judul' => 'Tunjangan Kehadiran',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_kehadiran)),
                    'bold'  => false
                );

                $t_shift = array(
                    'judul' => 'Tunjangan Shift',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_shift)),
                    'bold'  => false
                );

                $t_kelebihan_jam_kerja = array(
                    'judul' => 'Tunjangan Kelebihan Jam Kerja',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_kelebihan_jam_kerja)),
                    'bold'  => true
                );

                $t_insentif_libur_nasional = array(
                    'judul' => 'Insentif Libur Nasional',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_insentif_libur_nasional)),
                    'bold'  => false
                );

                $t_insentif = array(
                    'judul' => 'Insentif',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_insentif)),
                    'bold'  => false
                );

                $t_lainlain = array(
                    'judul' => 'Tunjangan Lain - Lain',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_lainlain)),
                    'bold'  => false
                );

                $ttl_tunjangan = array(
                    'judul' => 'Total Tunjangan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->total_tunjangan)),
                    'bold'  => true
                );
                $penerimaan = [];
                if($data_gaji->t_gross_up > 0){
                    array_push($penerimaan, $t_gross_up);
                }
                if($data_gaji->t_direksi > 0){
                    array_push($penerimaan, $t_direksi);
                }
                if($data_gaji->t_area > 0){
                    array_push($penerimaan, $t_area);
                }
                if($data_gaji->t_jabatan > 0){
                    array_push($penerimaan, $t_jabatan);
                }
                if($data_gaji->t_transport > 0){
                    array_push($penerimaan, $t_transport);
                }
                if($data_gaji->t_makan > 0){
                    array_push($penerimaan, $t_makan);
                }
                if($data_gaji->t_lembur > 0){
                    array_push($penerimaan, $t_lembur);
                }
                if($data_gaji->t_kehadiran > 0){
                    array_push($penerimaan, $t_kehadiran);
                }
                if($data_gaji->t_shift > 0){
                    array_push($penerimaan, $t_shift);
                }
                if($data_gaji->t_kelebihan_jam_kerja > 0){
                    array_push($penerimaan, $t_kelebihan_jam_kerja);
                }
                if($data_gaji->t_insentif_libur_nasional > 0){
                    array_push($penerimaan, $t_insentif_libur_nasional);
                }
                if($data_gaji->t_insentif > 0){
                    array_push($penerimaan, $t_insentif);
                }
                if($data_gaji->t_lainlain > 0){
                    array_push($penerimaan, $t_lainlain);
                }
                array_push($penerimaan, $ttl_tunjangan);


                // $penerimaan = array($gaji_pokok, $t_gross_up, $t_direksi, $t_area, 
                //         $t_jabatan, $t_transport, $t_makan, $t_lembur, $t_kehadiran, $t_shift,
                //         $t_kelebihan_jam_kerja, $t_insentif_libur_nasional, $t_insentif, $t_lainlain,
                //         $ttl_tunjangan
                // );
                $penerimaan =  array(
                    'main_judul' => 'Penerimaan',
                    'main_value' => $penerimaan,
                    'total_value' => $ttl_tunjangan,
                );
                # END PENERIMAAN

                #POTONGAN 
                $pot_admin_payroll = array(
                    'judul' => 'Potongan Admin Payroll',
                    'value' => 'Rp. '.strval(number_format($data_gaji->pot_admin_payroll)),
                    'bold'  => false
                );
                
                $pot_absen_bko = array(
                    'judul' => 'Potongan Absen BKO',
                    'value' => 'Rp. '.strval(number_format($data_gaji->pot_absen_bko)),
                    'bold'  => false
                );
                
                $pot_terlambat = array(
                    'judul' => 'Potongan Terlambat',
                    'value' => 'Rp. '.strval(number_format($data_gaji->pot_terlambat)),
                    'bold'  => false
                );

                $pot_lalai = array(
                    'judul' => 'Potongan Kelalaian',
                    'value' => 'Rp. '.strval(number_format($data_gaji->pot_lalai)),
                    'bold'  => false
                );

                $pot_bpjs_kesehatan = array(
                    'judul' => 'Potongan BPJS Kesehatan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->pot_bpjs_kesehatan)),
                    'bold'  => false
                );
                
                $pot_bjps_pensiun = array(
                    'judul' => 'Potongan Jaminan Pensiun',
                    'value' => 'Rp. '.strval(number_format($data_gaji->pot_bpjs_pensiun)),
                    'bold'  => false
                );

                $pot_jht = array(
                    'judul' => 'Potongan JHT',
                    'value' => 'Rp. '.strval(number_format($data_gaji->pot_jht)),
                    'bold'  => false
                );
                
                $pot_lainlain = array(
                    'judul' => 'Potongan Lain Lain',
                    'value' => 'Rp. '.strval(number_format($data_gaji->pot_lainlain)),
                    'bold'  => false
                );
                
                $total_potongan = array(
                    'judul' => 'Total Potongan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->total_potongan)),
                    'bold'  => true
                );
                
                $potongan = [];
                if($data_gaji->pot_admin_payroll > 0){
                    array_push($potongan, $pot_admin_payroll);
                }
                if($data_gaji->pot_absen_bko > 0){
                    array_push($potongan, $pot_absen_bko);
                }
                if($data_gaji->pot_terlambat > 0){
                    array_push($potongan, $pot_terlambat);
                }
                if($data_gaji->pot_lalai > 0){
                    array_push($potongan, $pot_lalai);
                }
                if($data_gaji->pot_bpjs_kesehatan > 0){
                    array_push($potongan, $pot_bpjs_kesehatan);
                }
                if($data_gaji->pot_bpjs_pensiun > 0){
                    array_push($potongan, $pot_bjps_pensiun);
                }
                if($data_gaji->pot_jht > 0){
                    array_push($potongan, $pot_jht);
                }
                if($data_gaji->pot_lainlain > 0){
                    array_push($potongan, $pot_lainlain);
                }
                    array_push($potongan, $total_potongan);
                // $potongan = [
                //     $pot_admin_payroll, $pot_absen_bko, $pot_terlambat, $pot_lalai,
                //     $pot_bpjs_kesehatan, $pot_bjps_pensiun, $pot_jht, $pot_lainlain, $total_potongan
                // ];

                $potongan = array(
                    'main_judul' => 'Potongan',
                    'main_value' => $potongan,
                    'total_value' => $total_potongan
                );
                #END POTONGAN

                #BENEFIT

                $rapelan = array(
                    'judul' => 'Rapelan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->rapelan)),
                    'bold'  => false
                );
                
                $benefit_jkk = array(
                    'judul' => 'Benefit JKK',
                    'value' => 'Rp. '.strval(number_format($data_gaji->benefit_jkk)),
                    'bold'  => false
                );

                $benefit_jkm = array(
                    'judul' => 'Benefit JKM',
                    'value' => 'Rp. '.strval(number_format($data_gaji->benefit_jkm)),
                    'bold'  => false
                );

                $benefit_jht = array(
                    'judul' => 'Benefit JHT',
                    'value' => 'Rp. '.strval(number_format($data_gaji->benefit_jht)),
                    'bold'  => false
                );

                $benefit_jaminan_pensiunan = array(
                    'judul' => 'Benefit Jaminan Pensiunan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->benefit_jaminan_pensiun)),
                    'bold'  => false
                );

                $benefit_bpjs_kesehatan = array(
                    'judul' => 'Benefit BPJS Kesehatan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->benefit_bpjs_kesehatan)),
                    'bold'  => false
                );

                $benefit_lainlain = array(
                    'judul' => 'Benefit Lain',
                    'value' => 'Rp. '.strval(number_format($data_gaji->benefit_lainlain)),
                    'bold'  => false
                );

                $total_benefit = array(
                    'judul' => 'Tunjangan Benefit',
                    'value' => '',
                    'bold'  => true
                );

                $benefit = [];
                if($data_gaji->rapelan > 0){
                    array_push($benefit, $rapelan);
                }
                if($data_gaji->benefit_jkk > 0){
                    array_push($benefit, $benefit_jkk);
                }
                if($data_gaji->benefit_jkm > 0){
                    array_push($benefit, $benefit_jkm);
                }
                if($data_gaji->benefit_jht > 0){
                    array_push($benefit, $benefit_jht);
                }
                if($data_gaji->benefit_jaminan_pensiun > 0){
                    array_push($benefit, $benefit_jaminan_pensiunan);
                }
                if($data_gaji->benefit_bpjs_kesehatan > 0){
                    array_push($benefit, $benefit_bpjs_kesehatan);
                }
                if($data_gaji->benefit_lainlain > 0){
                    array_push($benefit, $benefit_lainlain);
                }
               
                
                
                // $benefit = [
                //     $rapelan, $benefit_jkk, $benefit_jkm, $benefit_jht,
                //     $benefit_jaminan_pensiunan, $benefit_bpjs_kesehatan, $benefit_lainlain
                // ];

                $benefit = array(
                    'main_judul' => 'Benefit',
                    'main_value' => $benefit,
                    'total_value' => $total_benefit
                );

                #END TBP

                
                $gaji_diterima = array(
                    'judul'    => 'Take Home Pay',
                    'value'    => 'Rp. '.strval(number_format($data_gaji->take_home_pay)),
                    'bold'     => true
                );

                $data = [
                    $penerimaan,
                    $potongan,
                    $benefit,
                ];

                $komponen_lain = [
                    $gaji_diterima
                ];
                $data = array(
                    'detail_gaji' => $data,
                    'komponen_lain' => $komponen_lain,
                    'link_download' => 'https://maleoreport.absenku.com/web/slip_gaji/'.md5($data_gaji->id)
                );

            }

            if ($data_gaji!=null) 
                $response = array('success' => true, 'message' => 'Data gaji berhasil ditemukan', 'data_gaji' => $data);
            else $response = array('success'=>false,'message'=> 'Data gaji gagal ditemukan');
            return response()->json($response,200);
        }

        private static function getIdKaryawan($token_fcm){
            $id_karyawan = DB::table('users')->select('id_karyawan')->where('token_fcm', $token_fcm)->first();
            if($id_karyawan!=null) return $id_karyawan->id_karyawan;
            else return null;
        }

    }