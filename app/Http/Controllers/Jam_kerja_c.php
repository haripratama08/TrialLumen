<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\Jam_kerja_m;

    class Jam_kerja_c extends Controller{

        public function index(){
            
        }

        public function getJamKerja(Request $request){
            $id_company = $request->input('id_company');
            $id_karyawan = $request->input('id_karyawan');
            $current_date = $request->input('current_date');
            $day_now = date('N');
            return Jam_kerja_m::getJamKerja($id_company, $id_karyawan, $current_date, $day_now);
        }
    }