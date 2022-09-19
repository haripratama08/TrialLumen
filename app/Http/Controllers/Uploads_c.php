<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;


    class Uploads_c extends Controller{
        public $path;

        public function __construct()
        {
        }

        public function index(){
            
        }

        public static function cekFoto($file){
            $upload_dir = env('PATH_UPLOAD');
            if($file=="" OR $file=="-"){
                return false;
            }else{
                //is_file
                $url = base_path($upload_dir).$file;
                if (file_exists($url)) {
                    return true;
                } else {
                    return false;
                }                           
                
            }
        }

        public static function retrieve_file_url($url, $type, $jenis='image'){
            return env('WEB').$jenis."?_t=".$type."&_d=".$url;
        }
        

        public static function upload_file($base64, $kategori, $dirTarget, $imageName){
            $upload_dir = env('PATH_UPLOAD');
            if(!is_dir(base_path($upload_dir.$kategori.$dirTarget))){ 
                mkdir(base_path($upload_dir.$kategori.$dirTarget),0777,TRUE); 
            }
            
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
            file_put_contents(base_path($upload_dir.$kategori.$dirTarget."/".$imageName), $imageData);
            return $dirTarget."/".$imageName;;
        }


    
    }

 
