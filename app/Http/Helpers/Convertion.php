<?php
namespace App\Http\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Convertion {

    public static function jenisIzin($jenis = null){
        switch ($jenis) {
            case "I":
                return "Izin";
            break;
            case "S":
                return "Sakit";
            break;
            case "C":
                return "Cuti";
            break;
            case "CE":
                return "Cuti Ekstra";
            break;
            case "DL":
                return "Dinas Luar";
            break;
            default:
                return '-';
        }
    }

    public static function statusIzin($status = null){
        switch ($status) {
            case "1":
                return "Belum Disetujui";
            break;
            case "2":
                return "Dibatalkan";
            break;
            case "3":
                return "Ditolak";
            break;
            case "4":
                return "Disetujui";
            break;
            default:
                return '-';
        }
    } 

    public static function kepDepartemen($id_karyawan = null, $id_company = null){
        $select = DB::select("SELECT id_departemen 
                                FROM kep_departemen
                                WHERE id_company = '$id_company'
                                AND id_karyawan = '$id_karyawan'");
        $id_departemen_arr = array();

        if(count($select) > 0){
            foreach($select as $rows){
                $id_departemen_arr[] = $rows->id_departemen;
            }
        }

        if(array_sum($id_departemen_arr) > 0){
            $id_departemen = implode("','",$id_departemen_arr);
        }else{
            $id_departemen = null;
        }

        return $id_departemen;
    }

    public static function getIdKaryawan($token_fcm){
        $id_karyawan = DB::table('users')->select('id_karyawan')->where('token_fcm', $token_fcm)->first();
        if($id_karyawan!=null) return $id_karyawan->id_karyawan;
        else return null;
    }

    public static function namaDanGelar($gelar_depan = null, $nama_karyawan, $gelar_belakang){
        return $gelar_depan == '' ?''.$nama_karyawan.' '.$gelar_belakang:$gelar_depan.' '.$nama_karyawan.' '.$gelar_belakang;
    }

    public static function cekFoto($file){
            if($file=="" OR $file=="-"){
                return false;
            }else{
                //is_file
                $url = storage_path('../../web/').$file;
                if (file_exists($url)) {
                    return true;
                } else {
                    return false;
                }                           
                
            }
        }
}