<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Helpers\TimezoneMapper;
    use App\Models\Dashboard_m;
    use DateTime;
    use DateTimeZone;

    class Dashboard_c extends Controller{
        public $path;

        public function __construct(){
            $this->path = storage_path('app/public/images');
        }

        public function index(){
            
        }

        public function getDataDashboard(Request $request){
            $id_company = $request->get('id_company');
            return response()->json(array(
                'success'   => true,
                'message'   => 'Logo berhasil ditemukan',
                'data'      => array(
                    'logo'  => Dashboard_m::getLogo($id_company)
                )
            ));
        }
    }