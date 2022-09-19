<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use URL;
    use App\Http\Helpers\DateFormat;
    use App\Models\User_m;
    use App\Http\Helpers\Convertion;
    class Shift_m extends Model{

        public static function getDataShift($data = array()){
            DB::enableQueryLog();
            $data_shift = DB::table('data_shift_karyawan as dsk')->select('dsk.id_shift_karyawan','dsk.tanggal','ms.id_master_shift', 'ms.kode_shift', 'ms.nama_shift', 'ms.jam_masuk', 'ms.jam_pulang', 'ms.flag_jam_pulang', 'ms.libur', 'md.nama as nama_departemen', 'mc.nama as nama_cabang')
                ->join('master_shift as ms', 'ms.id_master_shift', '=', 'dsk.id_master_shift')
                ->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'dsk.id_karyawan')
                ->join('master_departemen as md', 'md.id_departemen', '=', 'dk.id_departemen')
                ->join('master_cabang as mc', 'mc.id_cabang', '=', 'dk.id_cabang')
                ->orderBy('dsk.tanggal', 'asc')
                ->limit($data['limit'])->offset($data['offset']);
            if($data['id_departemen']!=null) $data_shift = $data_shift->where('dk.id_departemen', $data['id_departemen']);
            if($data['id_cabang']!=null) $data_shift = $data_shift->where('dk.id_cabang', $data['id_cabang']);
            if($data['id_company']!=null) $data_shift = $data_shift->where('dsk.id_company', $data['id_company']);
            if($data['month_year']!=null) $data_shift = $data_shift->where(DB::raw("DATE_FORMAT(dsk.tanggal,'%Y-%m')"), $data['month_year']);
            if($data['range_tanggal_mulai']!=null&&$data['range_tanggal_selesai']!=null) $data_shift = $data_shift->where(
                [
                    array('dsk.tanggal', '>=', $data['range_tanggal_mulai']),
                    array('dsk.tanggal', '<=', $data['range_tanggal_selesai'])
                ]
            );
            if($data['konteks'] == 'shiftSaya') $data_shift = $data_shift->where('dsk.id_karyawan', $data['id_karyawan']);
            else if($data['konteks'] == 'shiftTeman' || $data['konteks'] == 'shiftKaryawan') {
                // $data_shift = $data_shift->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'dsk.id_karyawan');
                $data_shift = $data_shift->join('master_jabatan as mj', 'mj.id_jabatan', '=', 'dk.id_jabatan');
                $data_shift = $data_shift->groupBy(DB::raw('concat(dsk.id_master_shift, dsk.tanggal)'));
                $data_shift = $data_shift->where('dsk.id_karyawan', '!=',$data['id_karyawan']);
                if($data['konteks'] == 'shiftTeman') $data_shift = $data_shift->where('mj.level_user', $data['level']);
                else if($data['konteks'] == 'shiftKaryawan'){
                    $data_shift = $data_shift->join('usergroup as ug', 'ug.id', '=', 'mj.level_user');
                     if($data['level']=='7'){//JIKA SPV REQUEST
                        $data_shift = $data_shift->whereIn('ug.id',['3']); //Staff
                        $data_shift = $data_shift->where('dk.supervisi', $data['id_karyawan']);
                    }
                    else if($data['level']=='2'){//JIKA KEDEP REQUEST
                        $data_shift = $data_shift->whereIn('ug.id',['7','3']); //SPV dan Staff
                        $p_kedep = User_m::getPKedep($data['id_karyawan']);
                        if (count($p_kedep)==0) {//HANYA MENJABAT 1 KEPALA DEPARTEMEN
                            $data_shift = $data_shift->where('dk.id_departemen', self::getDataKaryawan($data['id_karyawan'])->id_departemen);
                        }
                        else{//BISA MENJABAT LEBIH DARI 1 KEPALA DEPARTEMEN
                            $data_shift = $data_shift->where(function ($query) use($p_kedep) {
                                $no = 0;
                                foreach ($p_kedep as $row_kedep) {
                                    if($no==0) $query = $query->where('dk.id_departemen', $row_kedep->id_departemen);
                                    else $query = $query->orWhere('dk.id_departemen', self::getDataKaryawan($row_kedep->id_departemen));
                                    $no++;   
                                }
                                        
                            });
                        } 
                    }
                    else if($data['level']=='5'){//JIKA KACAB REQUEST
                        $data_shift = $data_shift->whereIn('ug.id',['7','3','2']); //Kedep, SPV dan Staff
                        $p_kacab = User_m::getPKacab($data['id_karyawan']);
                        if (count($p_kacab)==0) {//HANYA MENJABAT 1 KEPALA CABANG
                            $data_shift = $data_shift->where('dk.id_cabang', self::getDataKaryawan($data['id_karyawan'])->id_cabang);
                        }
                        else{//BISA MENJABAT LEBIH DARI 1 KEPALA CABANG
                            $data_shift = $data_shift->where(function ($query) use($p_kacab) {
                                $no = 0;
                                foreach ($p_kacab as $row_kacab) {
                                    if($no==0) $query = $query->where('dk.id_cabang', $row_kacab->id_cabang);
                                    else $query = $query->orWhere('dk.id_cabang', self::getDataKaryawan($row_kacab->id_cabang));
                                    $no++;   
                                }
                                        
                            });
                        } 
                    }
                    else if($data['level']=='4'){//JIKA DIREKSI REQUEST
                        $data_shift = $data_shift->whereIn('ug.id',['5','2','7','3']); //Kacab, Kedep, SPV dan Staff
                        $data_shift = $data_shift->where('dk.id_company', self::getDataKaryawan($data['id_karyawan'])->id_company);
                    }
                    else if($data['level']=='1'){//JIKA HR REQUEST
                        $data_shift = $data_shift->whereIn('ug.id',['5','2','7','3','4']); //Kacab, Kedep, SPV dan Staff
                        $data_shift = $data_shift->where('dk.id_company', self::getDataKaryawan($data['id_karyawan'])->id_company);
                    }
                }
            }
            $data_shift = $data_shift->get();
            // dd(DB::getQueryLog());exit;
            if(count($data_shift)>0){
                foreach ($data_shift as $row) {
                    $row->id_shift_karyawan = intval($row->id_shift_karyawan);
                    $row->tanggal_raw = $row->tanggal;
                    $row->tanggal = DateFormat::format($row->tanggal,'N d M Y');
                    $row->jam_masuk = date_format(date_create($row->jam_masuk), 'H:i');
                    $row->jam_pulang = date_format(date_create($row->jam_pulang), 'H:i');
                }
                $response = array('success' => true, 'message' => 'data shift berhasil ditemukan', 'data' => $data_shift); 
            }
            else
                $response = array('success'=>false, 'message' => 'data Shift gagal ditemukan');
            return response()->json($response,200);
        }

        public static function getDataShiftTeman($data = array()){
            $data_shift = DB::table('data_shift_karyawan as dsk')
                ->select('dk.nama_lengkap', 'dk.foto', 'dk.id_karyawan', 'mj.nama as nama_jabatan', 'md.nama as nama_departemen', 'mc.nama as nama_cabang')
                ->join('master_shift as ms', 'ms.id_master_shift', '=', 'dsk.id_master_shift')
                ->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'dsk.id_karyawan')
                ->join('master_jabatan as mj', 'mj.id_jabatan', '=', 'dk.id_jabatan')
                ->join('master_departemen as md', 'md.id_departemen', '=', 'dk.id_departemen')
                ->join('master_cabang as mc', 'mc.id_cabang', '=', 'dk.id_cabang')
                ->orderBy('dk.nama_lengkap', 'asc')
                ->groupBy('dsk.id_karyawan')
                ->where('mj.level_user', $data['level'])
                ->where('dsk.id_master_shift', $data['id_master_shift'])
                ->where(DB::raw("DATE_FORMAT(dsk.tanggal,'%Y-%m-%d')"), $data['tanggal'])
                ->limit($data['limit'])->offset($data['offset']);
            if($data['id_karyawan']!=null) $data_shift = $data_shift->where('dsk.id_karyawan', '!=',$data['id_karyawan']);
            if($data['id_departemen']!=null) $data_shift = $data_shift->where('dk.id_departemen', $data['id_departemen']);
            if($data['id_cabang']!=null) $data_shift = $data_shift->where('dk.id_cabang', $data['id_cabang']);
            if($data['id_company']!=null) $data_shift = $data_shift->where('dsk.id_company', $data['id_company']);
            $data_shift = $data_shift->get();
            if(count($data_shift)>0){
                foreach ($data_shift as $row) {
                    $row->foto = Convertion::cekFoto($row->foto)?url('/').$row->foto:'-';
                }
                $response = array('success' => true, 'message' => 'data shift berhasil ditemukan', 'data' => $data_shift); 
            }
            else
                $response = array('success'=>false, 'message' => 'data jenis izin gagal ditemukan');
            return response()->json($response,200);
        }

         public static function getDataShiftKaryawan($data = array()){

            $data_shift = DB::table('data_shift_karyawan as dsk')
                ->select('dk.nama_lengkap', 'dk.foto', 'dk.id_karyawan', 'mj.nama as nama_jabatan', 'md.nama as nama_departemen', 'mc.nama as nama_cabang')
                ->join('master_shift as ms', 'ms.id_master_shift', '=', 'dsk.id_master_shift')
                ->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'dsk.id_karyawan')
                ->join('master_jabatan as mj', 'mj.id_jabatan', '=', 'dk.id_jabatan')
                ->join('master_departemen as md', 'md.id_departemen', '=', 'dk.id_departemen')
                ->join('master_cabang as mc', 'mc.id_cabang', '=', 'dk.id_cabang')
                ->orderBy('mc.nama', 'asc')
                ->orderBy('md.nama', 'asc')
                ->orderBy('dk.nama_lengkap', 'asc')
                ->groupBy('dsk.id_karyawan')
                ->where('dsk.id_master_shift', $data['id_master_shift'])
                ->where('dsk.id_company', $data['id_company'])
                ->where(DB::raw("DATE_FORMAT(dsk.tanggal,'%Y-%m-%d')"), $data['tanggal'])
                ->where('dsk.id_karyawan', '!=',$data['id_karyawan'])
                ->limit($data['limit'])->offset($data['offset']);
            if($data['id_karyawan_select']!=null) $data_shift = $data_shift->where('dsk.id_karyawan', '=',$data['id_karyawan_select']);
            if($data['id_departemen']!=null) $data_shift = $data_shift->where('dk.id_departemen', $data['id_departemen']);
            if($data['id_cabang']!=null) $data_shift = $data_shift->where('dk.id_cabang', $data['id_cabang']);

            $data_shift = $data_shift->join('usergroup as ug', 'ug.id', '=', 'mj.level_user');
            if($data['level']=='7'){//JIKA SPV REQUEST
                $data_shift = $data_shift->whereIn('ug.id',['3']); //Staff
                $data_shift = $data_shift->where('dk.supervisi', $data['id_karyawan']);
            }
            else if($data['level']=='2'){//JIKA KEDEP REQUEST
                $data_shift = $data_shift->whereIn('ug.id',['7','3']); //SPV dan Staff
                $p_kedep = User_m::getPKedep($data['id_karyawan']);
                if (count($p_kedep)==0) {//HANYA MENJABAT 1 KEPALA DEPARTEMEN
                    $data_shift = $data_shift->where('dk.id_departemen', self::getDataKaryawan($data['id_karyawan'])->id_departemen);
                }
                else{//BISA MENJABAT LEBIH DARI 1 KEPALA DEPARTEMEN
                    $data_shift = $data_shift->where(function ($query) use($p_kedep) {
                        $no = 0;
                        foreach ($p_kedep as $row_kedep) {
                            if($no==0) $query = $query->where('dk.id_departemen', $row_kedep->id_departemen);
                            else $query = $query->orWhere('dk.id_departemen', self::getDataKaryawan($row_kedep->id_departemen));
                            $no++;   
                        }
                                
                    });
                } 
            }
            else if($data['level']=='5'){//JIKA KACAB REQUEST
                $data_shift = $data_shift->whereIn('ug.id',['7','3','2']); //Kedep, SPV dan Staff
                $p_kacab = User_m::getPKacab($data['id_karyawan']);
                if (count($p_kacab)==0) {//HANYA MENJABAT 1 KEPALA CABANG
                    $data_shift = $data_shift->where('dk.id_cabang', self::getDataKaryawan($data['id_karyawan'])->id_cabang);
                }
                else{//BISA MENJABAT LEBIH DARI 1 KEPALA CABANG
                    $data_shift = $data_shift->where(function ($query) use($p_kacab) {
                        $no = 0;
                        foreach ($p_kacab as $row_kacab) {
                            if($no==0) $query = $query->where('dk.id_cabang', $row_kacab->id_cabang);
                            else $query = $query->orWhere('dk.id_cabang', self::getDataKaryawan($row_kacab->id_cabang));
                            $no++;   
                        }
                                
                    });
                } 
            }
            else if($data['level']=='4'){//JIKA DIREKSI REQUEST
                $data_shift = $data_shift->whereIn('ug.id',['5','2','7','3']); //Kacab, Kedep, SPV dan Staff
                $data_shift = $data_shift->where('dk.id_company', self::getDataKaryawan($data['id_karyawan'])->id_company);
            }
            else if($data['level']=='1'){//JIKA HR REQUEST
                $data_shift = $data_shift->whereIn('ug.id',['5','2','7','3','4']); //Kacab, Kedep, SPV dan Staff
                $data_shift = $data_shift->where('dk.id_company', self::getDataKaryawan($data['id_karyawan'])->id_company);
            }

            $data_shift = $data_shift->get();
            if(count($data_shift)>0){
                foreach ($data_shift as $row) {
                    $row->foto = Convertion::cekFoto($row->foto)?url('/').$row->foto:'-';
                }
                $response = array('success' => true, 'message' => 'data shift berhasil ditemukan', 'data' => $data_shift); 
            }
            else
                $response = array('success'=>false, 'message' => 'data shift gagal ditemukan');
            return response()->json($response,200);
        }

        private static function getDataKaryawan($id_karyawan){
            return DB::table('data_karyawan')->select('id_company', 'id_departemen', 'id_cabang')->where('id_karyawan', $id_karyawan)->first();
        }

    }