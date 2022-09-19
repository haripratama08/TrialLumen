<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use App\Http\Helpers\DateFormat;
    use App\Models\User_m;

    class Dropdown_m extends Model{

        public static function get_data_cabang($id_company, $id_karyawan, $level_user){
            if ($level_user=='5') {
                $p_kacab = User_m::getPKacab($id_karyawan);
                    if (count($p_kacab)==0) {
                        $id_cabang = User_m::get_data_karyawan($id_karyawan)->id_cabang;
                        $data = DB::table('master_cabang')->select('id_cabang as id', 'nama')->where('id_cabang', $id_cabang)->get();
                    }
                    else{
                        $data = DB::table('master_cabang')->select('id_cabang as id', 'nama');
                        foreach ($p_kacab as $row_kacab) {
                            $data = $data->where('id_cabang', $row_kacab->id_cabang);
                        }
                        $data = $data->get();
                    }
            }
            else{
                $data = DB::table('master_cabang')->select('id_cabang as id', 'nama')->where('id_company', $id_company)->get();
            }
            if(count($data) > 0) {
                $initial_data = [
                    array(
                        'id' => null,
                        'nama' => "Semua Cabang"
                    )
                ];
                $data_final = array_merge($initial_data, $data->toArray());
                $response = array('success' => true, 'message' => 'data cabang berhasil ditemukan', 'data' => $data_final);
            }
            else $response = array('success' => false, 'message' => 'data cabang gagal ditemukan');
            return response()->json($response,200);
        }

        public static function get_data_departemen($id_cabang, $id_karyawan, $level_user){
            if($level_user=='2'){
                $p_kedep = User_m::getPKedep($id_karyawan);
                if (count($p_kedep)==0) {
                    $id_departemen = User_m::get_data_karyawan($id_karyawan)->id_departemen;
                    $data = DB::table('master_departemen as md')->select('md.id_departemen as id', 'md.nama')
                    ->groupBy('md.id_departemen', 'md.nama')->where('md.id_departemen', $id_departemen)->get();
                }
                else{
                    $data = DB::table('master_departemen as md')->select('md.id_departemen as id', 'md.nama')
                    ->groupBy('md.id_departemen', 'md.nama');
                    foreach ($p_kedep as $row_kedep) {
                        $data = $data->orWhere('md.id_departemen', $row_kedep->id_departemen);
                    }
                    $data = $data->get();
                }
            }
            else{
                $data = DB::table('data_karyawan as dk')->select('md.id_departemen as id', 'md.nama')
                ->join('master_departemen as md', 'md.id_departemen', '=', 'dk.id_departemen')->groupBy('md.id_departemen', 'md.nama')->where('dk.id_cabang', $id_cabang)->get();
            }
            if(count($data) > 0) {
                $initial_data = [
                    array(
                        'id' => null,
                        'nama' => "Semua Departemen"
                    )
                ];
                $data_final = array_merge($initial_data, $data->toArray());
                $response = array('success' => true, 'message' => 'data departemen berhasil ditemukan', 'data' => $data_final);
            }
            else $response = array('success' => false, 'message' => 'data departemen gagal ditemukan');
            return response()->json($response,200);
        }

        public static function get_data_pegawai($id_company, $id_departemen, $id_cabang, $id_karyawan, $level_user, $keywords){
            $query = DB::table('data_karyawan as dk')->select('dk.id_karyawan as id', 'dk.nama_lengkap as nama')
            ->join('master_jabatan as mj', 'mj.id_jabatan', '=','dk.id_jabatan')
            ->join('usergroup as ug', 'ug.id', '=', 'mj.level_user')
            ->where('dk.nama_lengkap', 'like', '%'.$keywords.'%')
            ->where('dk.id_company', $id_company);

            $dataKaryawan = User_m::get_data_karyawan($id_karyawan);
            if($level_user!='4' && $level_user!='1'){
                if($id_departemen == null) $id_departemen = $dataKaryawan->id_departemen;
                if($id_cabang == null) $id_cabang = $dataKaryawan->id_cabang;
            }
            
            

            if($level_user=='7'){//JIKA SPV REQUEST
                $query = $query->where('dk.id_departemen', $id_departemen);
                $query = $query->where('dk.id_cabang', $id_cabang);
                $query = $query->whereIn('ug.id',['3']); //Staff

            }
            else if($level_user=='2'){//JIKA KEDEP REQUEST
                $query = $query->where('dk.id_departemen', $id_departemen);
                $query = $query->whereIn('ug.id',['7','3']); //SPV dan Staff
            }
            else if($level_user=='5'){//JIKA KACAB REQUEST
                $query = $query->where('dk.id_cabang', $id_cabang);
                $query = $query->whereIn('ug.id',['7','3','2']); //Kedep, SPV dan Staff
            }
            else if($level_user=='4' || $level_user=='1'){//JIKA DIREKSI REQUEST
                if($id_departemen!=null) $query = $query->where('dk.id_departemen', $id_departemen);
                if($id_cabang!=null) $query =  $query->where('dk.id_cabang', $id_cabang);
                $query = $query->whereIn('ug.id',['5','2','7','3']); //Kacab, Kedep, SPV dan Staff
            }
                $query = $query->get();
    
            if(count($query) > 0) $response = array('success' => true, 'message' => 'data pegawai berhasil ditemukan', 'data' => $query);
            else $response = array('success' => false, 'message' => 'data pegawai gagal ditemukan');
            return response()->json($response,200);
        }

        public static function get_data_status($level_user, $id_company){
            $cek_p_approval = DB::table('p_approval_reimbursement')
                ->where('id_company', $id_company)
                ->where('level_approval', 'like', '%'.$level_user.'%')
                ->get();
            if(count($cek_p_approval)>0){
                $data = [
                    array(
                        'id' => null,
                        'nama'  => 'Semua Status'
                    ),
                    array(
                        'id' => '1',
                        'nama'  => 'On Progress'
                    ),
                    array(
                        'id' => '3',
                        'nama'  => 'Ditolak'
                    ),
                    array(
                        'id' => '4',
                        'nama'  => 'Direvisi'
                    ),
                    array(
                        'id' => '5',
                        'nama'  => 'Disetujui'
                    ),
                ];
                $response = array(
                    'success' => true, 
                    'message' => 'data status berhasil ditemukan', 
                    'data' => $data
                );
            }
            else{
                $response = array(
                    'success' => false, 
                    'message' => 'data status gagal ditemukan',
                );
            }
            return response()->json($response,200); 
        }
    
    }