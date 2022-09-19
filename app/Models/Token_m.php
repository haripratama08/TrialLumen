<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use URL;
    use App\Http\Helpers\DateFormat;
    class Token_m extends Model{

       public static function cekToken($token_fcm){
            $id_karyawan = DB::table('users')->select('id_karyawan')->where('token_fcm', $token_fcm)->first();
            if($id_karyawan!=null) return $id_karyawan->id_karyawan;
            else return false;
        }

        public static function get_data_karyawan_by_token($token_fcm){
            $data = DB::table('users')
            ->join('data_karyawan', 'data_karyawan.id_karyawan', '=', 'users.id_karyawan')
            ->join('master_jabatan', 'master_jabatan.id_jabatan', '=', 'data_karyawan.id_jabatan')
            ->join('usergroup', 'usergroup.id', '=', 'master_jabatan.level_user')
            ->select(
                'users.id_karyawan', 'users.token_fcm', 
                'data_karyawan.id_departemen', 'data_karyawan.id_cabang', 
                'usergroup.id as level_user', 'data_karyawan.id_company')
            ->where('users.token_fcm', $token_fcm)->first();
            return $data;
        }

    }