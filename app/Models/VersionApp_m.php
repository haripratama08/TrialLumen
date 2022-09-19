<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;

    class VersionApp_m extends Model{

        public static function cek_version_apps($versi_mobile,$os){ #UPDATE JIKA RESPON['STATUS'] = TRUE
            $v_aplikasi = DB::table('v_aplikasi')
                ->select('version_code', 'version_name', 'keterangan_update', 'aktif','force_update', 'link_update', 'link_update_android', 'link_update_ios')
                ->where('aktif', '1')
                ->first(); 

            if($v_aplikasi == null){ # BELUM DI SETTING DI V_APLIKASI RETURN DEFAULT
                $respon['success'] = false;
                $respon['message'] = "Belum disetting, aplikasi tetap bisa digunakan";
            }else{
                if ($os == 'Android') {
                    $v_aplikasi->link_update = $v_aplikasi->link_update_android;
                }elseif($os == 'IOS'){
                    $v_aplikasi->link_update = $v_aplikasi->link_update_ios;
                }else{
                    $v_aplikasi->link_update = $v_aplikasi->link_update_android;
                }
                
                if($versi_mobile != $v_aplikasi->version_code){ # UPDATE APK
                    $respon['success'] = true;
                    $respon['message'] = "Terdapat update aplikasi";
                    $respon['version'] = $v_aplikasi;
                }else{
                    $respon['success'] = false;
                    $respon['message'] = "Aplikasi sudah terbaru";
                }
                
            }
            

            return response()->json($respon);
        }

    }