<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use URL;
    use App\Http\Helpers\DateFormat;
    use App\Models\Absensi_m;
    use App\Models\User_m;
    class Summary_m extends Model{

        public static function getDataSummary($id_karyawan = null, $month_year = null){
            $hari_ini = date('Y-m-d');
            $data_hadir_hari_ini = 0;
            $data_karyawan = User_m::get_data_karyawan($id_karyawan);
            $id_company = $data_karyawan->id_company;
            
            $data_summary = DB::table('r_absensi')
            ->select(
                DB::raw('SUM(IF(FIND_IN_SET("reguler", jenis_absen), TRUE, IF(FIND_IN_SET("shift", jenis_absen), TRUE, FALSE))) as hadir'),
                DB::raw('SUM(IF(jenis_absen = "sakit" , TRUE , FALSE)) as sakit'),
                DB::raw('SUM(IF(jenis_absen = "izin" , TRUE , FALSE)) as izin'),
                DB::raw('SUM(IF(jenis_absen = "alpha" , TRUE , FALSE)) as alpha'),
                DB::raw('SUM(IF(jenis_absen = "cuti" , TRUE , FALSE)) as cuti'),
                DB::raw('SUM(IF(jenis_absen = "tugas" , TRUE , FALSE)) as tugas'),
                DB::raw('SUM(IF(FIND_IN_SET("(T)", kode_absensi), TRUE, FALSE)) as terlambat'),
                DB::raw('SUM(IF(FIND_IN_SET("(PC)", kode_absensi), TRUE, FALSE)) as pulang_cepat'),
                DB::raw('SUM(IF(FIND_IN_SET("(TAP)", kode_absensi), TRUE, FALSE)) as tidak_absen_pulang'),
                DB::raw('SUM(IF(FIND_IN_SET("(T)", kode_absensi), terlambat, FALSE)) as menit_terlambat'),
                DB::raw('SUM(IF(FIND_IN_SET("(PC)", kode_absensi), pulang_cepat, FALSE)) as menit_pulang_cepat')
            )
            ->where('id_karyawan', $id_karyawan)
            ->where('id_company', $id_company);
            
            $cekAbsenTerakhir = Absensi_m::_cekAbsensiTerakhir($id_karyawan, $id_company);
            // echo $cekAbsenTerakhir['status'];exit;

            if($cekAbsenTerakhir['status']){
                $tgl_absen = $cekAbsenTerakhir['tgl_absen'];
            }else{
                $tgl_absen = $hari_ini;
            }

            //DATA HADIR HARI INI
            $count_hadir_hari_ini = '0';
            $menit_terlambat_hari_ini = '0';
            $terlambat_hari_ini = '0';
            $pulang_cepat_hari_ini = '0';
            $menit_pulang_cepat_hari_ini = '0';

            if($month_year == date('Y-m')){
                $data_hadir_hari_ini = DB::table('absensi_masuk as am')
                ->leftJoin('absensi_pulang as ap', 'am.id_absensi_masuk', '=', 'ap.id_masuk')
                ->select('am.id_absensi_masuk', 'am.terlambat', 'ap.pulang_cepat')
                ->where('am.id_karyawan', $id_karyawan)
                ->whereIn('am.jenis_absen', ['shift', 'reguler'])
                ->where('am.tgl_absen', $tgl_absen)
                ->where('am.id_company', $id_company)
                ->first();
                if($data_hadir_hari_ini!=null){
                    $count_hadir_hari_ini = '1';
                    $menit_terlambat_hari_ini = $data_hadir_hari_ini->terlambat;
                    $menit_pulang_cepat_hari_ini = $data_hadir_hari_ini->pulang_cepat;
                    if($menit_terlambat_hari_ini!=0) $terlambat_hari_ini = '1';
                    if($menit_pulang_cepat_hari_ini!=0) $pulang_cepat_hari_ini = '1';
                }
            }
            //

            
            if($month_year!=null){
              $data_summary = $data_summary->where(DB::raw("DATE_FORMAT(tgl_absen,'%Y-%m')"), $month_year);
              if($month_year==date('Y-m')){
                $data_summary = $data_summary->where([
                    array('tgl_absen', '<=', date('Y-m-d')) 
                ]);
              }
              
            } 
            $data_summary = $data_summary->first();
            $data_summary->hadir =  strval(($data_summary->hadir??$data_summary->hadir='0')+$count_hadir_hari_ini);
            $data_summary->sakit??$data_summary->sakit='0';
            $data_summary->izin??$data_summary->izin='0';
            $data_summary->alpha??$data_summary->alpha='0';
            $data_summary->cuti??$data_summary->cuti='0';
            $data_summary->tugas??$data_summary->tugas='0';
            $data_summary->menit_terlambat = strval(($data_summary->menit_terlambat??$data_summary->menit_terlambat='0')+$menit_terlambat_hari_ini);
            $data_summary->menit_pulang_cepat = strval(($data_summary->menit_pulang_cepat??$data_summary->menit_pulang_cepat='0')+$menit_pulang_cepat_hari_ini);
            $data_summary->terlambat = strval(($data_summary->terlambat??$data_summary->terlambat='0')+$terlambat_hari_ini);
            $data_summary->pulang_cepat = strval(($data_summary->pulang_cepat??$data_summary->pulang_cepat='0')+$pulang_cepat_hari_ini);
            $data_summary->tidak_absen_pulang??$data_summary->tidak_absen_pulang='0';
            $data_summary->izin_sakit_cuti = strval($data_summary->izin+$data_summary->cuti+$data_summary->sakit);
            
            $data_lembur = DB::table('r_lembur')->where('id_karyawan', $id_karyawan);
            $menit_lembur = DB::table('r_lembur')
            ->select(
                DB::raw('SUM(ttl_lembur) as menit_lembur')
            )
            ->where('id_karyawan', $id_karyawan);
            if($month_year!=null) {
                $data_lembur = $data_lembur->where(DB::raw("DATE_FORMAT(tgl_lembur,'%Y-%m')"), $month_year);
                $menit_lembur = $menit_lembur->where(DB::raw("DATE_FORMAT(tgl_lembur,'%Y-%m')"), $month_year);
            }
            $data_lembur = $data_lembur->count();
            $menit_lembur = $menit_lembur->first();
            $data_summary->lembur = strval($data_lembur);
            $data_summary->menit_lembur = strval($menit_lembur->menit_lembur??'0');
            $data_summary->sisa_cuti = strval(DB::table('data_karyawan')->select('jatah_cuti')->where('id_karyawan', $id_karyawan)->first()->jatah_cuti);
            if ($data_summary!=null) 
                $response = array('success' => true, 'message' => 'data summary berhasil ditemukan', 'data' => $data_summary);
            else 
                $response = array('success'=>false,'message'=> 'data summary gagal ditemukan');
            return response()->json($response,200);
        }
    }