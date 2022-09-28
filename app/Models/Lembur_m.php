<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use App\Http\Helpers\DateFormat;
    use App\Http\Helpers\Users;
    use App\Models\Absensi_m;
    use App\Models\User_m;
    use App\Http\Helpers\Convertion;
    use App\Http\Controllers\Uploads_c;

    class Lembur_m extends Model{

        public static function dataLemburByKaryawan($id_company = null, $id_karyawan = null, $filter_bulan = null, $limit_start=null, $limit_end=null){

            if(date('Y-m') == $filter_bulan){
                $select_lembur = DB::select("SELECT * 
                                            FROM (SELECT lm.id_lemmulai AS id_lembur,
                                                        lm.tgl_absen,
                                                        lm.jam_absen AS absen_masuk,
                                                        lm.lokasi_absen AS lokasi_masuk,
                                                        lm.keterangan AS ket_masuk,
                                                        ls.jam_absen AS absen_pulang,
                                                        lm.approve
                                                    FROM lembur_mulai AS lm
                                                    LEFT JOIN lembur_selesai AS ls ON ls.id_karyawan = lm.id_karyawan AND lm.tgl_absen = ls.tgl_absen
                                                    WHERE lm.tgl_absen = CURRENT_DATE()
                                                    AND lm.id_karyawan = '$id_karyawan'
                                                    AND lm.id_company = '$id_company'
                                                    UNION ALL
                                                    SELECT id_lembur,
                                                            tgl_absen,
                                                            absen_masuk,
                                                            lokasi_masuk,
                                                            ket_masuk,
                                                            absen_pulang,
                                                            approve
                                                    FROM r_lembur
                                                    WHERE id_karyawan = '$id_karyawan'
                                                    AND id_company = '$id_company'
                                                    AND DATE_FORMAT(tgl_absen,'%Y-%m') = '$filter_bulan') data_lembur
                                            ORDER BY tgl_absen DESC
                                            LIMIT $limit_start,$limit_end");

                $total_data = collect(DB::select("SELECT lm.tgl_absen
                                                FROM lembur_mulai AS lm
                                                LEFT JOIN lembur_selesai AS ls ON ls.id_karyawan = lm.id_karyawan AND lm.tgl_absen = ls.tgl_absen
                                                WHERE lm.tgl_absen = CURRENT_DATE()
                                                AND lm.id_karyawan = '$id_karyawan'
                                                AND lm.id_company = '$id_company'
                                                UNION ALL
                                                SELECT tgl_absen
                                                FROM r_lembur
                                                WHERE id_karyawan = '$id_karyawan'
                                                AND id_company = '$id_company'
                                                AND DATE_FORMAT(tgl_absen,'%Y-%m') = '$filter_bulan'"))->count();
                                            
            }else{
                $select_lembur = DB::select("SELECT id_lembur,
                                                tgl_absen,
                                                absen_masuk,
                                                lokasi_masuk,
                                                ket_masuk,
                                                absen_pulang,
                                                approve
                                            FROM r_lembur
                                            WHERE id_karyawan = '$id_karyawan'
                                            AND id_company = '$id_company'
                                            AND DATE_FORMAT(tgl_absen,'%Y-%m') = '$filter_bulan'
                                            ORDER BY tgl_absen  DESC
                                            LIMIT $limit_start,$limit_end");

                $total_data = collect(DB::select("SELECT id_lembur
                                                FROM r_lembur
                                                WHERE id_karyawan = '$id_karyawan'
                                                AND id_company = '$id_company'
                                                AND DATE_FORMAT(tgl_absen,'%Y-%m') = '$filter_bulan'"))->count();
            }
            if(count($select_lembur) > 0){
                $data_lembur = array();

                foreach($select_lembur as $rows){
                    $data_lembur[] = array( 'id_lembur'     => $rows->id_lembur,
                                            'tgl_absensi'    => DateFormat::format($rows->tgl_absen,'N d M Y'),
                                            'absen_masuk'   => $rows->absen_masuk,
                                            'lokasi_masuk'  => $rows->lokasi_masuk,
                                            'keterangan'    => $rows->ket_masuk,
                                            'absen_pulang'  => $rows->absen_pulang,
                                            'approve'       => $rows->approve
                                        );
                }

                if(count($data_lembur) > 0){
                    $response = array('success'=>true,
                                        'data'=>array('jml_semua_data' => $total_data,
                                                        'jml_tampil' => ($limit_start + count($select_lembur)),
                                                        'list_data' => $data_lembur));

                    $json = response()->json($response,200);
                }else{
                    $response = array('success'=>false,
                                    'message'=>'Data tidak ditemukan');

                    $json = response()->json($response,401);
                }
            }else{
                $response = array('success'=>false,
                                    'message'=>'Data tidak ditemukan');

                $json = response()->json($response,401);
            }

            return $json;
        }

        public static function dataLemburByDepartemen($id_company=null, $level_user=null, $filter_departemen=null, $filter_karyawan=null, $filter_bulan=null, $filter_date=null, $limit_start=null, $limit_end=null){
            DB::connection()->enableQueryLog();
            if($filter_karyawan == 'semua'){
                if(date('Y-m-d') == $filter_date){
                    $select_lembur = DB::select("SELECT lm.id_lemmulai AS id_lembur,
                                                        lm.tgl_absen,
                                                        dk.id_company,
                                                        dk.id_karyawan,
                                                        dk.nama AS nama_karyawan,
                                                        lm.jam_absen AS absen_masuk,
                                                        lm.lokasi_absen AS lokasi_masuk,
                                                        lm.keterangan AS ket_masuk,
                                                        ls.jam_absen AS absen_pulang,
                                                        lm.approve
                                                    FROM lembur_mulai AS lm
                                                    LEFT JOIN lembur_selesai AS ls ON ls.id_karyawan = lm.id_karyawan AND lm.tgl_absen = ls.tgl_absen
                                                    LEFT JOIN data_karyawan AS dk ON dk.id_karyawan = lm.id_karyawan
                                                    WHERE lm.tgl_absen = CURRENT_DATE() ".
                                                    (($level_user == '1')?" ":" AND dk.id_departemen IN ('$id_departemen') ").
                                                    " AND lm.id_company = '$id_company'
                                                    ORDER BY lm.tgl_absen DESC
                                                    LIMIT $limit_start,$limit_end");

                    $total_data = collect(DB::select("SELECT lm.tgl_absen
                                                    FROM lembur_mulai AS lm
                                                    LEFT JOIN data_karyawan AS da ON dk.id_karyawan = lm.id_karyawan
                                                    WHERE lm.tgl_absen = CURRENT_DATE() ".
                                                    (($level_user == '1')?" ":" AND dk.id_departemen IN ('$id_departemen') ").
                                                    " AND lm.id_company = '$id_company'"))->count();
                }else{
                    $select_lembur = DB::select("SELECT id_lembur,
                                                    tgl_absen,
                                                    id_company,
                                                    id_karyawan,
                                                    nama_karyawan,
                                                    absen_masuk,
                                                    lokasi_masuk,
                                                    ket_masuk,
                                                    absen_pulang,
                                                    approve
                                                FROM r_lembur
                                                WHERE id_company = '$id_company' ".
                                                (($level_user == '1')?" ":" AND id_departemen IN ('$id_departemen') ").
                                                " AND tgl_absen = '$filter_date'
                                                ORDER BY tgl_absen  DESC
                                                LIMIT $limit_start,$limit_end");

                    $total_data = collect(DB::select("SELECT tgl_absen
                                                    FROM r_lembur
                                                    WHERE id_company = '$id_company' ".
                                                    (($level_user == '1')?" ":" AND id_departemen IN ('$id_departemen') ").
                                                    " AND tgl_absen = '$filter_date'"))->count();
                }
            }else{
                if(date('Y-m') == $filter_bulan){
                    $select_lembur = DB::select("SELECT * 
                                                FROM (SELECT lm.id_lemmulai AS id_lembur,
                                                        lm.tgl_absen,
                                                        dk.id_company,
                                                        dk.id_karyawan,
                                                        dk.nama AS nama_karyawan,
                                                        lm.jam_absen AS absen_masuk,
                                                        lm.lokasi_absen AS lokasi_masuk,
                                                        lm.keterangan AS ket_masuk,
                                                        ls.jam_absen AS absen_pulang,
                                                        lm.approve
                                                    FROM lembur_mulai AS lm
                                                    LEFT JOIN lembur_selesai AS ls ON ls.id_karyawan = lm.id_karyawan AND lm.tgl_absen = ls.tgl_absen
                                                    LEFT JOIN data_karyawan AS dk ON dk.id_karyawan = lm.id_karyawan
                                                    WHERE lm.tgl_absen = '$filter_bulan'
                                                    AND lm.id_karyawan = '$filter_karyawan'
                                                    AND lm.id_company = '$id_company'
                                                    UNION ALL
                                                    SELECT id_lembur,
                                                        tgl_absen,
                                                        id_company,
                                                        id_karyawan,
                                                        nama_karyawan,
                                                        absen_masuk,
                                                        lokasi_masuk,
                                                        ket_masuk,
                                                        absen_pulang,
                                                        approve
                                                    FROM r_lembur
                                                    WHERE id_karyawan = '$filter_karyawan'
                                                    AND id_company = '$id_company'
                                                    AND DATE_FORMAT(tgl_absen,'%Y-%m') = '$filter_bulan') data_lembur
                                                ORDER BY tgl_absen DESC");

                    $total_data = collect(DB::select("SELECT lm.tgl_absen
                                                        FROM lembur_mulai AS lm
                                                        WHERE lm.tgl_absen = '$filter_bulan'
                                                        AND lm.id_karyawan = '$filter_karyawan'
                                                        AND lm.id_company = '$id_company'
                                                        UNION ALL
                                                        SELECT tgl_absen
                                                        FROM r_lembur
                                                        WHERE id_karyawan = '$filter_karyawan'
                                                        AND id_company = '$id_company'
                                                        AND DATE_FORMAT(tgl_absen,'%Y-%m') = '$filter_bulan'"))->count();
                }else{
                    $select_lembur = DB::select("SELECT id_lembur,
                                                    tgl_absen,
                                                    absen_masuk,
                                                    id_company,
                                                    id_karyawan,
                                                    nama_karyawan
                                                    lokasi_masuk,
                                                    ket_masuk,
                                                    absen_pulang,
                                                    approve
                                                FROM r_lembur
                                                WHERE id_karyawan = '$filter_karyawan'
                                                AND id_company = '$id_company'
                                                AND DATE_FORMAT(tgl_absen,'%Y-%m') = '$filter_bulan'
                                                ORDER BY tgl_absen  DESC");

                    $total_data = collect(DB::select("SELECT tgl_absen
                                                        FROM r_lembur
                                                        WHERE id_karyawan = '$filter_karyawan'
                                                        AND id_company = '$id_company'
                                                        AND DATE_FORMAT(tgl_absen,'%Y-%m') = '$filter_bulan'"))->count();
                }
            }

            // $queries    = DB::getQueryLog();
            // $lastQuery = end($queries);
            
            // echo dd($lastQuery);

            if(count($select_lembur) > 0){
                $data_lembur = array();

                foreach($select_lembur as $rows){
                    $data_lembur[] = array( 'id_lembur'     => $rows->id_lembur,
                                            'tgl_absensi'   => DateFormat::format($rows->tgl_absen,'N d M Y'),
                                            'tanggal'       => $rows->tgl_absen,
                                            'id_company'    => $rows->id_company,
                                            'id_karyawan'   => $rows->id_karyawan,
                                            'nama_karyawan' => $rows->nama_karyawan,
                                            'absen_masuk'   => $rows->absen_masuk,
                                            'lokasi_masuk'  => $rows->lokasi_masuk,
                                            'keterangan'    => $rows->ket_masuk,
                                            'absen_pulang'  => $rows->absen_pulang,
                                            'approve'       => $rows->approve);
                }

                if(count($data_lembur) > 0){
                    $response = array('success'=>true,
                                    'data'=>array('jml_semua_data' => $total_data,
                                                    'jml_tampil' => ($limit_start + count($select_lembur)),
                                                    'list_data' => $data_lembur));

                    $json = response()->json($response,200);
                }else{
                    $response = array('success'=>false,
                                    'message'=>'Data tidak ditemukan');

                    $json = response()->json($response,401);
                }
            }else{
                $response = array('success'=>false,
                                    'message'=>'Data tidak ditemukan');

                $json = response()->json($response,401);
            }

            return $json;
        }


       /* public static function setujui($id_company=null, $id_lembur=null){
            $update_absensi_lembur = DB::update("UPDATE lembur_mulai
                                                SET approve = '3'
                                                WHERE id_lemmulai = '$id_lembur'
                                                AND id_company = '$id_company'");
            if($update_absensi_lembur){
                $update_r_lembur = DB::update("UPDATE r_lembur
                                                SET approve = '3'
                                                WHERE id_lembur = '$id_lembur'
                                                AND id_company = '$id_company'");
                if($update_r_lembur){
                    $response = array('success'=>true,
                                    'message'=>'Lembur berhasil disetujui');

                    $json = response()->json($response,200);
                }else{
                    $update_absensi_lembur = DB::update("UPDATE lembur_mulai
                                                        SET approve = '1'
                                                        WHERE id_lemmulai = '$id_lembur'
                                                        AND id_company = '$id_company'");

                    $response = array('success'=>true,
                                        'message'=>'Lembur berhasil disetujui');

                    $json = response()->json($response,200);
                }

            }else{
                $response = array('success'=>false,
                                    'message'=>'Lembur gagal disetujui');

                $json = response()->json($response,401);
            }

            return $json;
        }

        public static function tolak($id_company=null, $id_lembur=null){
            $update_absensi_lembur = DB::update("UPDATE lembur_mulai
                                                SET approve = '2'
                                                WHERE id_lemmulai = '$id_lembur'
                                                AND id_company = '$id_company'");
            if($update_absensi_lembur){
                $update_r_lembur = DB::update("UPDATE r_lembur
                                                SET approve = '2'
                                                WHERE id_lembur = '$id_lembur'
                                                AND id_company = '$id_company'");
                if($update_r_lembur){
                    $response = array('success'=>true,
                                    'message'=>'Lembur berhasil ditolak');

                    $json = response()->json($response,200);
                }else{
                    $update_absensi_lembur = DB::update("UPDATE lembur_mulai
                                                        SET approve = '1'
                                                        WHERE id_lemmulai = '$id_lembur'
                                                        AND id_company = '$id_company'");

                    $response = array('success'=>true,
                                        'message'=>'Lembur berhasil ditolak');

                    $json = response()->json($response,200);
                }

            }else{
                $response = array('success'=>false,
                                    'message'=>'Lembur gagal ditolak');

                $json = response()->json($response,401);
            }

            return $json;
        }*/

        //SURYA
         private static function statusApproval($id_status){
            if ($id_status == '0') $status = array(
                'status' => 'tidak perlu persetujuan',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFF4C4C4C'
            );
            else if($id_status == '1') $status = array(
                'status' => 'Belum disetujui',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFF4C4C4C'
            );
            else if($id_status == '2') $status = array(
                'status' => 'Dibatalkan',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFFFFFFFF'
            );
            else if($id_status == '3') $status = array(
                'status' => 'Ditolak',
                'color' => '0XFFFF6666',
                'font_color' => '0XFFFFFFFF'
            );
            else if($id_status == '4') $status = array(
                'status' => 'Disetujui',
                'color' => '0XFF74C077',
                'font_color' => '0XFFFFFFFF'
            );
            else if($id_status == '5') $status = array(
                'status' => 'Belum disetujui',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFF4C4C4C'
            );
            return $status;
        }

        public static function get_p_approval($level_user, $id_company, $id_cabang){
            $data = DB::table('p_approval_lembur')
            ->select('level_approval')
            ->where(
                [
                    'level_user' => $level_user, 
                    'id_company' => $id_company,
                    'id_cabang' => $id_cabang
                ]
            )->first();
            if ($data!=null) {
                return $data->level_approval;
            }
            else return null; 
        }

        public static function get_master_jabatan($level_approval, $id_company){
            $data = DB::table('master_jabatan')
                ->select('level_user', 'nama', 'id_jabatan')
                ->whereIn('level_user', explode(',', $level_approval))
                ->where('id_company', $id_company)
                ->get();
            return $data;
        }

        public static function get_data_user($id_jabatan, $id_company){
            $data = DB::table('users')->join('data_karyawan', 'data_karyawan.id_karyawan', '=', 'users.id_karyawan')->select('users.id_karyawan', 'users.token_fcm')->where(['data_karyawan.id_jabatan' => $id_jabatan, 'users.id_company' => $id_company])->first();
            return $data;
        }

        public static function getLastRowILemburMulai($id_karyawan){
            $data =  DB::table('lembur_mulai as lm')
            ->select('lm.id_lemmulai')
            ->where(['lm.id_karyawan' => $id_karyawan])
            ->leftJoin('lembur_selesai as ls', 'ls.id_lemmulai', '=', 'lm.id_lemmulai')
            ->Where('ls.id_lemselesai', null)
            ->where('lm.id_karyawan', $id_karyawan)
            ->orderBy('lm.tgl_absen', 'desc')
            ->orderBy('lm.id_lemmulai', 'desc')
            ->orderBy('lm.id_lemmulai', 'desc')
            ->first();
            if($data!=null) return $data->id_lemmulai;
            else return null;
        }

        public static function add_lembur($table, $data=null){
            $insert_lembur = DB::table($table)->insert($data);
            return $insert_lembur;
        }

        public static function cek_approval_per_column($where){
            $data = DB::table('lembur_mulai')->where($where)
            ->count();
            return $data;
        }

        public static function cek_approval_per_column_by_hrd($id_lemmulai, $approval_column){
            $data = DB::table('lembur_mulai')->where('id_lemmulai', $id_lemmulai)->whereIn($approval_column, ['1', '5'])
            ->count();
            return $data;
        }

        public static function setujui($data = array(), $id_lemmulai){
            $update = DB::table('lembur_mulai')->where('id_lemmulai', '=', $id_lemmulai)->update($data);
            return $update;
        }

        public static function batalkan($id_lemmulai = null, $id_karyawan = null){
            $update = DB::table('lembur_mulai')->where(
                [
                    'id_lemmulai' => $id_lemmulai, 
                    'id_karyawan' => $id_karyawan
                ])
            ->whereNotIn('approval_spv', ['3', '4'])
            ->whereNotIn('approval_kedep', ['3', '4'])
            ->whereNotIn('approval_kacab', ['3', '4'])
            ->whereNotIn('approval_hrd', ['3', '4'])
            ->whereNotIn('approval_direksi', ['3', '4'])
            ->update(['status' => '2']);
 
            if($update){
                $response = array('success'=>true,
                                'message'=>'Lembur berhasil dibatalkan');
            }else{
                $response = array('success'=>false,
                                'message'=>'Lembur tidak dapat dibatalkan');
            }
            return response()->json($response,200);
        }

        public static function insertRekapLembur($id_lemmulai){
            $data_lembur = DB::table('lembur_mulai as lm')->select('lm.id_karyawan','lm.tgl_absen', 'dk.nama_lengkap', 'dk.id_cabang', 'mc.nama as nama_cabang', 'dk.id_departemen', 'md.nama as nama_departemen', 'dk.id_jabatan', 'mj.nama as nama_jabatan', 'lm.jam_absen as absen_masuk', 'ls.jam_absen as absen_pulang', 'lm.latitude as lat_masuk', 'ls.latitude as lat_pulang', 'lm.longitude as long_masuk', 'ls.longitude as long_pulang', 'lm.lokasi_absen as lokasi_masuk', 'ls.lokasi_absen as lokasi_pulang', 'lm.keterangan as ket_masuk', 'ls.keterangan as ket_pulang', 'lm.id_company', 'lm.foto as foto_mulai', 'ls.foto as foto_selesai', 'lm.timezone as timezone_mulai', 'lm.gmt as gmt_mulai', 'ls.timezone as timezone_selesai', 'ls.gmt as gmt_selesai')
            ->join('lembur_selesai as ls', 'ls.id_lemmulai', '=', 'lm.id_lemmulai')
            ->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'lm.id_karyawan')
            ->leftJoin('master_cabang as mc', 'mc.id_cabang', 'dk.id_cabang')
            ->leftJoin('master_departemen as md', 'md.id_departemen', '=', 'dk.id_departemen')
            ->join('master_jabatan as mj', 'mj.id_jabatan', '=', 'dk.id_jabatan')
            ->where('lm.id_lemmulai', $id_lemmulai)->first();
            $data_insert = array(
                'id_lembur'         => Absensi_m::getId($data_lembur->id_company, 'r_lembur'),
                'ttl_lembur'        => ceil(abs(strtotime($data_lembur->absen_pulang)-strtotime($data_lembur->absen_masuk))/60),
                'tgl_lembur'         => $data_lembur->tgl_absen,
                'id_karyawan'       => $data_lembur->id_karyawan,
                'nama_karyawan'     => $data_lembur->nama_lengkap,
                'id_cabang'         => $data_lembur->id_cabang??'',
                'nama_cabang'       => $data_lembur->nama_cabang??'',
                'id_departemen'     => $data_lembur->id_departemen??'',
                'nama_departemen'   => $data_lembur->nama_departemen??'',
                'id_jabatan'        => $data_lembur->id_jabatan,
                'nama_jabatan'      => $data_lembur->nama_jabatan,
                'lembur_mulai'       => $data_lembur->absen_masuk,
                'lembur_selesai'      => $data_lembur->absen_pulang,
                'lat_mulai'         => $data_lembur->lat_masuk,
                'long_mulai'        => $data_lembur->long_masuk,
                'lat_selesai'        => $data_lembur->lat_pulang,
                'long_selesai'       => $data_lembur->long_pulang,
                'lok_mulai'      => $data_lembur->lokasi_masuk,
                'lok_selesai'     => $data_lembur->lokasi_pulang,
                'ket_mulai'         => $data_lembur->ket_masuk,
                'ket_selesai'        => $data_lembur->ket_pulang,
                'id_company'        => $data_lembur->id_company,
                'foto_mulai'        => $data_lembur->foto_mulai,
                'foto_selesai'      => $data_lembur->foto_selesai,
                'timezone_mulai'    => $data_lembur->timezone_mulai,
                'timezone_selesai'  => $data_lembur->timezone_selesai,
                'gmt_mulai'         => $data_lembur->gmt_mulai,
                'gmt_selesai'       => $data_lembur->gmt_selesai
            );

            DB::table('r_lembur')->insert($data_insert);
            return true;
        }

        public static function getDataLembur($data = array()){
            $id_company         = $data['id_company'];
            $id_karyawan        = $data['id_karyawan'];
            $id_cabang          = $data['id_cabang'];
            $level_user         = $data['level_user'];
            $limit              = $data['limit'];
            $offset             = $data['offset'];
            $konteks            = $data['konteks'];
            $id_lemmulai        = $data['id_lemmulai'];
            $range_tanggal_mulai    = $data['range_tanggal_mulai'];
            $range_tanggal_selesai  = $data['range_tanggal_selesai'];

            $data_lembur = DB::table('lembur_mulai as lm')
            ->select('lm.*','lm.tgl_absen as tgl_masuk', 'ls.tgl_absen as tgl_pulang', 'lm.jam_absen as jam_masuk', 'ls.jam_absen as jam_pulang', 'dk.nama_lengkap', 'lm.lokasi_absen as lokasi_masuk', 'ls.lokasi_absen as lokasi_pulang', 'lm.foto as foto_masuk', 'ls.foto as foto_pulang', 'lm.keterangan as ket_masuk', 'ls.keterangan as ket_pulang')
            ->selectRaw('IF((lm.approval_hrd="5"||lm.approval_hrd="0")&&'.$level_user.'="1", TRUE, FALSE) as button_approval_hrd')
            ->join('lembur_selesai as ls', 'ls.id_lemmulai', '=', 'lm.id_lemmulai')
            ->join('data_karyawan as dk', 'lm.id_karyawan', '=', 'dk.id_karyawan')
            ->join('master_jabatan as mj', 'mj.id_jabatan', '=', 'dk.id_jabatan')
            ->join('usergroup as ug', 'ug.id', '=', 'mj.level_user')
            ->orderBy('lm.tgl_absen', 'desc')
            ->orderBy('lm.jam_absen', 'desc')
            ->limit($limit)->offset($offset)
            ->where('lm.id_company', $id_company);

            //KONTEKS PENGAJUAN ATAU PEGAWAI
            if($konteks=='lemburSaya') $where = [array('lm.id_karyawan', '=', $id_karyawan)];
            else if($konteks=='lemburPegawai'){
                $data_lembur = $data_lembur->where('ug.urutan', '>', User_m::get_data_user_by_id($id_karyawan)->urutan)
                    ->where('dk.id_karyawan', '!=', $id_karyawan);
                $where = array();
                if($level_user=='1') {
                    // $where[] = array('approval_hrd', '<>', '5');
                    // $where[] = array('approval_hrd', '<>', '0');
                    // $data_lembur = $data_lembur->where(function ($query) {
                    //     $query = $query->whereNotIn('approval_hrd', ['0'])
                    //         ->orWhere('lm.approve', '3');  
                    // });
                }
                else if($level_user=='2') {
                    // $where[] = array('approval_kedep', '<>', '5');
                    // $where[] = array('approval_kedep', '<>', '0');
                    $data_lembur = $data_lembur->where(function ($query) {
                        $query = $query->whereNotIn('approval_kedep', ['5','0'])
                            ->orWhere('lm.approve', '3');  
                    });
                    $p_kedep = User_m::getPKedep($id_karyawan);
                    if (count($p_kedep)==0) {//HANYA MENJABAT 1 KEPALA DEPARTEMEN
                        $where[] = array('dk.id_departemen', '=', User_m::get_data_karyawan($id_karyawan)->id_departemen);
                    }
                    else{//BISA MENJABAT LEBIH DARI 1 KEPALA DEPARTEMEN
                        $list_id_departemen = array();
                        foreach ($p_kedep as $row_kedep) {
                            $list_id_departemen[] = $row_kedep->id_departemen;   
                        } 
                        $data_lembur = $data_lembur->whereIn('dk.id_departemen', $list_id_departemen); 
                    }
                }
                else if($level_user=='4') {
                    // $where[] = array('approval_direksi', '<>', '5');
                    // $where[] = array('approval_direksi', '<>', '0');
                    $data_lembur = $data_lembur->where(function ($query) {
                        $query = $query->whereNotIn('approval_direksi', ['5','0'])
                            ->orWhere('lm.approve', '3');  
                    });
                }
                else if($level_user=='5') {
                    // $where[] = array('approval_kacab', '<>', '5');
                    // $where[] = array('approval_kacab', '<>', '0');
                    $data_lembur = $data_lembur->where(function ($query) {
                        $query = $query->whereNotIn('approval_kacab', ['5','0'])
                            ->orWhere('lm.approve', '3');  
                    });
                    $p_kacab = User_m::getPKacab($id_karyawan);
                    if (count($p_kacab)==0) {//HANYA MENJABAT 1 KEPALA CABANG
                        $where[] = array('dk.id_cabang', '=', User_m::get_data_karyawan($id_karyawan)->id_cabang);
                    }
                    else{//BISA MENJABAT LEBIH DARI 1 KEPALA CABANG
                        $list_id_cabang = array();
                        foreach ($p_kacab as $row_kacab) {
                            $list_id_cabang[] = $row_kacab->id_cabang;   
                        } 
                        $data_lembur = $data_lembur->whereIn('dk.id_cabang', $list_id_cabang);
                    }
                }
                else if($level_user=='7') {
                    // $where[] = array('approval_spv', '<>', '5');
                    // $where[] = array('approval_spv', '<>', '0');
                    $data_lembur = $data_lembur->where(function ($query) {
                        $query = $query->whereNotIn('approval_spv', ['5','0'])
                            ->orWhere('lm.approve', '3');  
                    });
                    $where[] = array('dk.supervisi', '=', $id_karyawan);
                }
            }

            else if($konteks=='lemburDetail') $where[] = array('lm.id_lemmulai', '=', $id_lemmulai);
            //

            if($range_tanggal_mulai!=null && $range_tanggal_selesai!=null) {
                 $where[] = array('lm.tgl_absen', '>=', $range_tanggal_mulai);
                 $where[] = array('ls.tgl_absen', '<=', $range_tanggal_selesai);
            }

            $data_lembur = $data_lembur->where($where)->get();
            $path_foto = "absensi/".env('NAME_APPLICATION')."/";
            $respon = array();
            foreach ($data_lembur as $row) {
                $aksi_batalkan = true;
                $list_approval = array();
                if($row->status!='1'||$row->approval_spv=='4'||$row->approval_kedep=='4'||$row->approval_kacab=='4'||$row->approval_hrd=='4'||$row->approval_direksi=='4') $aksi_batalkan = false;
                if($row->approval_spv!='0'){
                    $statusApproval = self::statusApproval($row->approval_spv);
                    $list_approval[] = array(
                        'status_approval'   => $statusApproval['status'].' SPV',
                        'tanggal_approval'  => $row->tgl_apv_spv,
                        'warna_status'      => $statusApproval['color'],
                        'warna_font'        => $statusApproval['font_color']
                    );
                }
                    
                if($row->approval_kedep!='0'){
                    $statusApproval = self::statusApproval($row->approval_kedep);
                    $list_approval[] = array(
                        'status_approval'   => $statusApproval['status'].' Kepala Departemen',
                        'tanggal_approval'  => $row->tgl_apv_kedep,
                        'warna_status'      => $statusApproval['color'],
                        'warna_font'        => $statusApproval['font_color']
                    );
                }
                    
                if($row->approval_kacab!='0'){
                    $statusApproval = self::statusApproval($row->approval_kacab);
                     $list_approval[] = array(
                        'status_approval'   => $statusApproval['status'].' Kepala Cabang',
                        'tanggal_approval'  => $row->tgl_apv_kacab,
                        'warna_status'      => $statusApproval['color'],
                        'warna_font'        => $statusApproval['font_color']
                    );
                }
                   
                if($row->approval_hrd!='0'){
                    $statusApproval = self::statusApproval($row->approval_hrd);
                    $list_approval[] = array(
                        'status_approval'   => $statusApproval['status'].' HRD',
                        'tanggal_approval'  => $row->tgl_apv_hrd,
                        'warna_status'      => $statusApproval['color'],
                        'warna_font'        => $statusApproval['font_color']
                    );
                }
                    
                if($row->approval_direksi!='0'){
                    $statusApproval = self::statusApproval($row->approval_direksi);
                    $list_approval[] = array(
                        'status_approval'   => $statusApproval['status'].' Direksi',
                        'tanggal_approval'  => $row->tgl_apv_direksi,
                        'warna_status'      => $statusApproval['color'],
                        'warna_font'        => $statusApproval['font_color']
                    );
                }

                //TAMPILKAN AKSI?
                $aksi = false;
                if($level_user=='1' && $row->status=='1') $aksi = true;
                else if($level_user=='2' && $row->approval_kedep=='1' && $row->status=='1') $aksi = true;
                else if($level_user=='4' && $row->approval_direksi=='1' && $row->status=='1') $aksi = true;
                else if($level_user=='5' && $row->approval_kacab=='1' && $row->status=='1') $aksi = true;
                else if($level_user=='7' && $row->approval_spv=='1' && $row->status=='1') $aksi = true;
                //
                    
                $respon[] = array(
                    'id_lemmulai'           => $row->id_lemmulai,
                    'status'                => self::statusApproval($row->status)['status'],
                    'tgl_absen_masuk'       => DateFormat::format($row->tgl_masuk,'N d M Y'),
                    'tgl_absen_pulang'      => DateFormat::format($row->tgl_pulang,'N d M Y'),
                    'nama'                  => $row->nama_lengkap,
                    'ket_lembur_masuk'      => $row->ket_masuk,
                    'ket_lembur_pulang'     => $row->ket_pulang,
                    'jam_masuk'             => date_format(date_create($row->jam_masuk), "H:i"),
                    'jam_pulang'            => date_format(date_create($row->jam_pulang), "H:i"),
                    'lokasi_masuk'          => $row->lokasi_masuk,
                    'lokasi_pulang'         => $row->lokasi_pulang,
                    'foto_masuk'            => ($row->foto_masuk == ''?'-':(Uploads_c::cekFoto($path_foto.$row->foto_masuk)?Uploads_c::retrieve_file_url($row->foto_masuk, 'photo'):'-')),
                    'foto_pulang'           => ($row->foto_pulang == ''?'-':(Uploads_c::cekFoto($path_foto.$row->foto_pulang)?Uploads_c::retrieve_file_url($row->foto_pulang, 'photo'):'-')),
                    'list_approval'     => $list_approval,
                    'aksi'              => $aksi,
                    'aksi_batalkan'     => $aksi_batalkan,
                    'button_approval_hrd'     => $row->button_approval_hrd==1?TRUE:FALSE
                );   
            }

            if (count($data_lembur)>0) 
                $response = array('success' => true, 'message' => 'data lembur berhasil ditemukan', 'data' => $respon);
            else 
                $response = array('success'=>false,'message'=> 'data lembur gagal ditemukan');
            return response()->json($response,200);
        }

        public static function getDataRekapLembur($data=array()){
            $id_lembur      = $data['id_lembur'];
            $id_karyawan        = $data['id_karyawan'];
            $id_karyawan_select = $data['id_karyawan_select'];
            $range_tanggal_mulai    = $data['range_tanggal_mulai'];
            $range_tanggal_selesai  = $data['range_tanggal_selesai'];
            $id_departemen = $data['id_departemen'];
            $id_cabang     = $data['id_cabang'];
            $id_company    = $data['id_company'];
            $konteks       = $data['konteks'];
            $limit         = $data['limit'];
            $offset        = $data['offset'];
            $month_year         = $data['month_year'];

            $where = array();
            if($range_tanggal_mulai!=null && $range_tanggal_selesai!=null) {
                 $where[] = array('tgl_lembur', '>=', $range_tanggal_mulai);
                 $where[] = array('tgl_lembur', '<=', $range_tanggal_selesai);
            }

            if($id_cabang!=null) $where[] = array('rl.id_cabang', '=', $id_cabang);
            if($id_company!=null) $where[] = array('rl.id_company', '=', $id_company);
            if($id_departemen!=null) $where[] = array('rl.id_departemen', '=', $id_departemen);
            if($id_karyawan_select!=null) $where[] = array('rl.id_karyawan', '=', $id_karyawan_select);
            if($month_year!=null) $where[] = array('rl.tgl_lembur', 'like', '%'.$month_year.'%');

             $data_lembur = DB::table('r_lembur as rl')
            ->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'rl.id_karyawan')
            ->join('master_jabatan as mj', 'mj.id_jabatan', '=','rl.id_jabatan')
            ->join('usergroup as ug', 'ug.id', '=', 'mj.level_user')
            ->orderBy('lembur_mulai', 'desc')
            ->limit($limit)->offset($offset)
            ->where($where);

            if($konteks=='rekapSaya') $data_lembur = $data_lembur->where('dk.id_karyawan', $id_karyawan);
            else if($konteks=='detailRekap') $data_lembur = $data_lembur->where('rl.id_lembur', $id_lembur);
            else if($konteks=='rekapPegawai'){//JIKA REKAP PEGAWAI
                $data_lembur = $data_lembur->where('ug.urutan', '>', User_m::get_data_user_by_id($id_karyawan)->urutan)
                    ->where('dk.id_karyawan', '!=', $id_karyawan);
                if($data['level_user']=='7'){//JIKA SPV REQUEST
                    // $data_lembur = $data_lembur->whereIn('ug.id',['3']); //Staff
                    $data_lembur = $data_lembur->where('dk.supervisi', $id_karyawan);
                }
                else if($data['level_user']=='2'){//JIKA KEDEP REQUEST
                    // $data_lembur = $data_lembur->whereIn('ug.id',['7','3']); //SPV dan Staff
                    $p_kedep = User_m::getPKedep($id_karyawan);
                    if (count($p_kedep)==0) {//HANYA MENJABAT 1 KEPALA DEPARTEMEN
                        $data_lembur = $data_lembur->where('dk.id_departemen', User_m::get_data_karyawan($id_karyawan)->id_departemen);
                    }
                    else{//BISA MENJABAT LEBIH DARI 1 KEPALA DEPARTEMEN
                        $list_id_departemen = array();
                        foreach ($p_kedep as $row_kedep) {
                            $list_id_departemen[] = $row_kedep->id_departemen;   
                        } 
                        $data_lembur = $data_lembur->whereIn('dk.id_departemen', $list_id_departemen); 
                    }
                }
                else if($data['level_user']=='5'){//JIKA KACAB REQUEST
                    // $data_lembur = $data_lembur->whereIn('ug.id',['7','3','2']); //Kedep, SPV dan Staff
                    $p_kacab = User_m::getPKacab($id_karyawan);
                    if (count($p_kacab)==0) {//HANYA MENJABAT 1 KEPALA CABANG
                       $data_lembur = $data_lembur->where('dk.id_cabang', User_m::get_data_karyawan($id_karyawan)->id_cabang);
                    }
                    else{//BISA MENJABAT LEBIH DARI 1 KEPALA CABANG
                        $list_id_cabang = array();
                        foreach ($p_kacab as $row_kacab) {
                            $list_id_cabang[] = $row_kacab->id_cabang;   
                        } 
                        $data_lembur = $data_lembur->whereIn('dk.id_cabang', $list_id_cabang);
                    }
                }
                else if($data['level_user']=='4'){//JIKA DIREKSI REQUEST
                    // $data_lembur = $data_lembur->whereIn('ug.id',['5','2','7','3']); //Kacab, Kedep, SPV dan Staff
                    $data_lembur = $data_lembur->where('dk.id_company', self::getDataKaryawan($id_karyawan)->id_company);
                }
                else if($data['level_user']=='1'){//JIKA HR REQUEST
                    // $data_lembur = $data_lembur->whereIn('ug.id',['5','2','7','3','4']); //Kacab, Kedep, SPV dan Staff
                    $data_lembur = $data_lembur->where('dk.id_company', self::getDataKaryawan($id_karyawan)->id_company);
                }
            }

            $data_lembur = $data_lembur->get();

           
            $path_foto = "absensi/".env('NAME_APPLICATION')."/";
            $respon = array();
            foreach ($data_lembur as $row) {
                $respon[] = array(
                    'id_lembur'             => $row->id_lembur,
                    'tgl_absen_masuk'       => DateFormat::format($row->lembur_mulai,'N d M Y'),
                    'tgl_absen_pulang'      => DateFormat::format($row->lembur_selesai,'N d M Y'),
                    'nama'                  => $row->nama_karyawan,
                    'ket_lembur_masuk'      => $row->ket_mulai,
                    'ket_lembur_pulang'     => $row->ket_selesai,
                    'jam_masuk'             => date_format(date_create($row->lembur_mulai), "H:i"),
                    'jam_pulang'            => date_format(date_create($row->lembur_selesai), "H:i"),
                    'lokasi_masuk'          => $row->lok_mulai,
                    'lokasi_pulang'         => $row->lok_selesai,
                    'foto_masuk'            => Convertion::cekFoto($path_foto.$row->foto_mulai)?url('../web').$row->foto_mulai:'-',
                    'foto_pulang'           => Convertion::cekFoto($path_foto.$row->foto_selesai)?url('../web').$row->foto_selesai:'-',
                );   
            }
            
            

            if (count($data_lembur)>0) 
                $response = array('success' => true, 'message' => 'data lembur berhasil ditemukan', 'data' => $respon);
            else 
                $response = array('success'=>false,'message'=> 'data lembur gagal ditemukan');
            return response()->json($response,200);
        }

        public static function cekAbsen($data = array()){
            
            $lokasi_kantor = Absensi_m::_getLokasi($data['id_karyawan'], $data['id_company'], $data['lat_absen'], $data['long_absen']);
            /* $lokasi_kantor = DB::table('data_lokasi_kantor as dlk')->select('nama_kantor', 'lat_asli', 'id_lokasi_kantor', 'lat_min', 'lat_max', 'long_asli', 'long_min', 'long_max')->join('data_karyawan as dk', 'dk.id_cabang', '=', 'dlk.id_cabang')->where(['dlk.id_company' => $data['id_company'], 'dk.id_karyawan' => $data['id_karyawan']])->get();

            $lokasi = false;
            if(count($lokasi_kantor) > 0){
                foreach($lokasi_kantor as $rows){
                    $lat_min_kantor = $rows->lat_min;
                    $lat_max_kantor = $rows->lat_max;
                    $long_min_kantor = $rows->long_min;
                    $long_max_kantor = $rows->long_max;
                    $nama_lokasi = $rows->nama_kantor;
                    $id_lokasi = $rows->id_lokasi_kantor;
                    if($lokasi == false){
                        if(($data['lat_absen'] >= $lat_min_kantor && $data['long_absen'] <= $long_min_kantor) && ($data['lat_absen'] <= $lat_max_kantor && $data['long_absen'] >= $long_max_kantor)){
                            $lokasi = true;
                            $id_lokasi_absen = $id_lokasi;
                            $lokasi_absen = $nama_lokasi;
                        }
                    }                    
                }
                if($lokasi){
                    $response_lokasi = array(
                        'success'=>true,
                        'id_lok' => $id_lokasi_absen,
                        'nama_kantor'=>$lokasi_absen);
                }else{
                    $response_lokasi = array('success'=>true,
                                    'nama_kantor'=>'Anda berada di luar radius kantor');
                }

            }else{
                $response_lokasi = array('success'=>false,
                                    'nama_kantor'=>'Lokasi kantor belum disetting');

                $json = response()->json($response_lokasi,401);
                // return $json;
            } */

            $cek_lembur = DB::table('lembur_mulai as lm')
                ->select('lm.tgl_absen as tgl_lembur_mulai', 'ls.tgl_absen as tgl_lembur_selesai', 'lm.jam_absen as jam_lembur_mulai', 'ls.jam_absen as jam_lembur_selesai')
                ->leftJoin('lembur_selesai as ls', 'ls.id_lemmulai', '=', 'lm.id_lemmulai')
                ->Where('ls.id_lemselesai', null)
                ->where('lm.id_karyawan', $data['id_karyawan'])
                ->orderBy('lm.tgl_absen', 'desc')
                ->orderBy('lm.id_lemmulai', 'desc')
                ->first();
            if($cek_lembur!=null) $toggle_absen_lembur = false;
            else $toggle_absen_lembur = true;
            
            


             $data = array('jam_lembur_mulai' => ((isset($cek_lembur->jam_lembur_mulai) && !empty($cek_lembur->jam_lembur_mulai))?date_format(date_create($cek_lembur->jam_lembur_mulai), 'H:i'):"-"),
                        'jam_lembur_selesai' => ((isset($cek_lembur->jam_lembur_selesai) && !empty($cek_lembur->jam_lembur_selesai))?date_format(date_create($cek_lembur->jam_lembur_selesai), 'H:i'):"-"),
                        'tgl_lembur_mulai' => ((isset($cek_lembur->tgl_lembur_mulai) && !empty($cek_lembur->tgl_lembur_mulai))?DateFormat::format($cek_lembur->tgl_lembur_mulai,'d M Y'):"-"),
                        'tgl_lembur_selesai' => ((isset($cek_lembur->tgl_lembur_selesai) && !empty($cek_lembur->tgl_lembur_selesai))?DateFormat::format($cek_lembur->tgl_lembur_mulai,'d M Y'):"-"),
                        );
            //BUTTON STATUS ABSENSI ENABLED OR DISABLED BOOL
            $status_button_absensi = array(
                'enabled_absen_lembur'  => $lokasi_kantor['boleh_absen']=='1'?true:false,
                'toggle_absen_lembur'  => $toggle_absen_lembur
            );
            //

            return $json = response()->json(array(
                'data_lokasi' => $lokasi_kantor,
                'status_button_absensi'=> $status_button_absensi,
                'data_hari_ini'=>$data,
            ));
        }

        private static function getDataKaryawan($id_karyawan){
            return DB::table('data_karyawan')->select('id_company', 'id_departemen', 'id_cabang')->where('id_karyawan', $id_karyawan)->first();
        }

        public static function getDataLemburById($id_lemmulai){
            return DB::table('lembur_mulai as lm')->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'lm.id_karyawan')->select('dk.nama_lengkap', 'lm.id_karyawan', 'lm.user_spv', 'dk.id_departemen', 'dk.id_cabang')->where('id_lemmulai', $id_lemmulai)->first();
        }

    }

    