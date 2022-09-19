<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use URL;
    use App\Http\Helpers\DateFormat;
    use App\Http\Helpers\Convertion;
    use App\Http\Controllers\Uploads_c;
    
    class Dashboard_m extends Model{

       public static function getLogo($id_company){
            $logo = DB::table('master_company')
                ->select('url_logo')
                ->where('id_company', $id_company)
                ->first();
            if($logo!=null) $logo = Uploads_c::cekFoto($logo->url_logo)?Uploads_c::retrieve_file_url($logo->url_logo, 'file'):'-';
            else $logo = '-';
            return $logo;
       }
    }