<?php
namespace App\Http\Helpers;
use Illuminate\Http\Request;

class DateFormat {

    public static function format($date = null, $format = null){
        if($date !== null || !empty($date)){
            $hari = date_format(date_create($date),'N');
            $tanggal = date_format(date_create($date),'d');
            $bulan = date_format(date_create($date),'m');
            $tahun = date_format(date_create($date),'Y');
            $jam = date_format(date_create($date),'H');
            $menit = date_format(date_create($date),'i');
            $detik = date_format(date_create($date),'s');
            switch ($format) {
                case "d M Y":
                    return $tanggal.' '.static::bulan($bulan).' '.$tahun;
                break;
                case "N d M Y":
                    return static::hari($hari).', '.$tanggal.' '.static::bulan($bulan).' '.$tahun;
                break;
                case "N d M Y H i s":
                    return static::hari($hari).', '.$tanggal.' '.static::bulan($bulan).' '.$tahun.' '.$jam.':'.$menit.':'.$detik;
                break;
                default:
                    return '-';
            }
        }else{
            return '-';
        }
        
    }

    private static function bulan($value = null){
        $bulan = array ('01'=>'Januari',
                        '02'=>'Februari',
                        '03'=>'Maret',
                        '04'=>'April',
                        '05'=>'Mei',
                        '06'=>'Juni',
                        '07'=>'Juli',
                        '08'=>'Agustus',
                        '09'=>'September',
                        '10'=>'Oktober',
                        '11'=>'November',
                        '12'=>'Desember');
        if($value !== null){
            if(in_array($value,array('01','02','03','04','05','06','07','08','09','10','11','12'))){
                return $bulan[$value];
            }else{
                return $value;
            }
            
        }else{
            return $value;
        }
        
    }

    private static function hari($value = null){
        $bulan = array ("1"=>"Senin",
                        "2"=>"Selasa",
                        "3"=>"Rabu",
                        "4"=>"Kamis",
                        "5"=>"Jum'at",
                        "6"=>"Sabtu",
                        "7"=>"Minggu");
        if($value !== null){
            if(in_array($value,array('1','2','3','4','5','6','7'))){
                return $bulan[$value];
            }else{
                return $value;
            }
            
        }else{
            return $value;
        }
        
    }
}