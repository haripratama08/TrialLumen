<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Http\Response;
    use App\Http\Helpers\DateFormat;
    use URL;
    use App\Http\Helpers\Convertion;

    class Notifikasi_m extends Model{

        private static function checkCompany($id_company = null){
            $status_company = DB::table('master_company')
                            ->select('id_company')
                            ->where('id_company', '=', ''.$id_company.'')
                            ->where('flag', '=', '1')
                            ->count();
            if($status_company > 0){
                return true;
            }else{
                $response = array('success'=>false,
                                'message'=>'Perusahaan Anda sudah tidak aktif di Aplikasi Absenku');
                return $response;
            }
        }
        public static function notifikasi_khusus($id_company, $where_field){
            return DB::table('p_notification')->select('token_fcm')->join('users', 'users.id_karyawan', '=', 'p_notification.id_karyawan')
            ->where('p_notification.id_company', $id_company)
            ->where($where_field, '1')->get();
        }


        public static function reminder_kontrak(){
            $data = DB::table('data_karyawan as dk')
                      ->join('users as u','dk.id_karyawan','=','u.id_karyawan')
                      ->join('master_jabatan as mj','dk.id_jabatan','=','mj.id_jabatan')
                      ->select('dk.id_karyawan','dk.id_company','dk.tgl_aktif_bekerja','dk.nama_lengkap','dk.tgl_berhenti_bekerja','u.token_fcm','mj.level_user')
                      ->where('dk.status','1')
                      ->where('dk.tgl_berhenti_bekerja', date('Y-m-d', strtotime('+1 month', strtotime(date('Y-m-d')))))
                      ->get();
            return $data;
        }

    }