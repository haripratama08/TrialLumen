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
            ->select('nik', 'nama_karyawan', 
                'gaji_pokok', 't_jabatan', 't_transport', 't_makan', 't_istri', 't_anak', 'lembur',
                'komisi', 't_lain', 'ttl_tunjangan', 'ttl_penerimaan', 
                'tbp_bpjs_kesehatan', 'tbp_bpjs_tk', 'tbp_lain', 'ttl_tbp', 
                'p_absensi', 'p_keterlambatan', 'p_koperasi', 'p_asuransi_kesehatan', 'p_bpjs_tk', 'p_bpjs_kesehatan',
                'p_lain', 'ttl_potongan', 
                'gaji_diterima'
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
                
                $uang_makan = array(
                    'judul' => 'Uang Makan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_makan)),
                    'bold'  => false
                );
                $uang_komisi = array(
                    'judul' => 'Insentif',
                    'value' => 'Rp. '.strval(number_format($data_gaji->komisi)),
                    'bold'  => false
                );

                $uang_lembur = array(
                    'judul' => 'Uang Lembur',
                    'value' => 'Rp. '.strval(number_format($data_gaji->lembur)),
                    'bold'  => false
                );
                
                $tunjangan_jabatan = array(
                    'judul' => 'Tunjangan Jabatan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_jabatan)),
                    'bold'  => false
                );

                $tunjangan_tranport = array(
                    'judul' => 'Tunjangan Tranport',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_transport)),
                    'bold'  => false
                );

                $tunjangan_istri = array(
                    'judul' => 'Tunjangan Istri',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_istri)),
                    'bold'  => false
                );

                $tunjangan_anak = array(
                    'judul' => 'Tunjangan Anak',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_anak)),
                    'bold'  => false
                );

                $penerimaan_lain = array(
                    'judul' => 'Penerimaan Lain',
                    'value' => 'Rp. '.strval(number_format($data_gaji->t_lain)),
                    'bold'  => false
                );

                $total_penerimaan = array(
                    'judul' => 'Total Penerimaan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->ttl_penerimaan)),
                    'bold'  => true
                );

                $penerimaan = array($gaji_pokok, $uang_makan, $uang_komisi, $uang_lembur, 
                        $tunjangan_jabatan, $tunjangan_tranport, $tunjangan_istri, $tunjangan_anak,
                        $penerimaan_lain, $total_penerimaan
                );
                $penerimaan =  array(
                    'main_judul' => 'Penerimaan',
                    'main_value' => $penerimaan,
                    'total_value' => $total_penerimaan,
                );
                # END PENERIMAAN

                #POTONGAN 
                $potongan_absen = array(
                    'judul' => 'Potongan Absen',
                    'value' => 'Rp. '.strval(number_format($data_gaji->p_absensi)),
                    'bold'  => false
                );
                
                $potongan_keterlambatan = array(
                    'judul' => 'Potongan Terlambat',
                    'value' => 'Rp. '.strval(number_format($data_gaji->p_keterlambatan)),
                    'bold'  => false
                );
                
                $potongan_koperasi = array(
                    'judul' => 'Potongan Koperasi',
                    'value' => 'Rp. '.strval(number_format($data_gaji->p_koperasi)),
                    'bold'  => false
                );

                $potongan_bpjs_kesehatan = array(
                    'judul' => 'Potongan BPJS Kesehatan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->p_bpjs_kesehatan)),
                    'bold'  => false
                );

                $potongan_bpjs_tk = array(
                    'judul' => 'Potongan BPJS Keternagakerjaan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->p_bpjs_tk)),
                    'bold'  => false
                );
                
                $asuransi_kesehatan = array(
                    'judul' => 'Asuransi Kesehatan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->p_asuransi_kesehatan)),
                    'bold'  => false
                );
                
                $potongan_lain = array(
                    'judul' => 'Potongan Lain',
                    'value' => 'Rp. '.strval(number_format($data_gaji->p_lain)),
                    'bold'  => false
                );
                
                $total_potongan = array(
                    'judul' => 'Total Potongan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->ttl_potongan)),
                    'bold'  => true
                );
                
                $potongan = [
                    $potongan_absen, $potongan_keterlambatan, $potongan_koperasi, $potongan_bpjs_kesehatan,
                    $potongan_bpjs_tk, $asuransi_kesehatan, $potongan_lain, $total_potongan
                ];

                $potongan = array(
                    'main_judul' => 'Potongan',
                    'main_value' => $potongan,
                    'total_value' => $total_potongan
                );
                #END POTONGAN

                #TUNJANGAN DIBIAYAI PERUSAHAAN

                $tbp_bpjs_kesehatan = array(
                    'judul' => 'Tunjangan BPSJ Kesehatan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->tbp_bpjs_kesehatan)),
                    'bold'  => false
                );
                
                $tbp_bpjs_tk = array(
                    'judul' => 'Tunjangan BPJSTK',
                    'value' => 'Rp. '.strval(number_format($data_gaji->tbp_bpjs_tk)),
                    'bold'  => false
                );

                $tbp_lain = array(
                    'judul' => 'Tunjangan Lain',
                    'value' => 'Rp. '.strval(number_format($data_gaji->tbp_lain)),
                    'bold'  => false
                );

                $total_tbp = array(
                    'judul' => 'Tunjangan Tunjangan Dibiayai Perusahaan',
                    'value' => 'Rp. '.strval(number_format($data_gaji->ttl_tbp)),
                    'bold'  => true
                );


                $tbp = [
                    $tbp_bpjs_kesehatan, $tbp_bpjs_tk, $tbp_lain, $total_tbp
                ];

                $tbp = array(
                    'main_judul' => 'Tunjangan Dibiayai Perusahaan',
                    'main_value' => $tbp,
                    'total_value' => $total_tbp
                );

                #END TBP

                
                $gaji_diterima = array(
                    'judul'    => 'Gaji Diterima',
                    'value'    => 'Rp. '.strval(number_format($data_gaji->gaji_diterima)),
                    'bold'     => true
                );

                $data = [
                    $penerimaan,
                    $potongan,
                    $tbp,
                ];

                $komponen_lain = [
                    $gaji_diterima
                ];
                $data = array(
                    'detail_gaji' => $data,
                    'komponen_lain' => $komponen_lain
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