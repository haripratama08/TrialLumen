<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use App\Models\Absensi_m;
    use App\Models\User_m;
    use App\Http\Helpers\DateFormat;
    use App\Http\Helpers\Convertion;
    use DateTime;
    use DatePeriod;
    use DateInterval;
    use URL;
    use App\Models\Jam_kerja_m;
    use App\Http\Controllers\Uploads_c;

    class Izin_m extends Model{

        public static function getId($id_company = null){
            $curent_month = date('Ym');

            $select = collect(DB::select("SELECT MAX(id_izin) AS id
                                        FROM data_izin
                                        WHERE id_company = '$id_company'
                                        AND SUBSTR(id_izin,10,6) = DATE_FORMAT(CURRENT_DATE(),'%Y%m')"))->first();
            
            if(!empty($select->id)){
                $maxid = substr($select->id,-5);
                $nextid = $id_company.$curent_month.sprintf("%05d", ($maxid+1));
            }else{
                $nextid = $id_company.$curent_month.'00001';
            }

            return $nextid;
            
        }

        public static function data($data = array()){
            $id_company         = $data['id_company'];
            $id_karyawan        = $data['id_karyawan'];
            $level_user         = $data['level_user'];
            $level_user         = $data['level_user'];
            $filter_karyawan    = $data['filter_karyawan'];
            $filter_bulan       = $data['filter_bulan'];
            $start_date         = $data['start_date'];
            $end_date           = $data['end_date'];
            $limit_start        = $data['limit_start'];
            $limit_end          = $data['limit_end'];
            if(in_array($level_user,array('1','4'))){ // Administrator, Direksi
                $id_cabang = null;
                $id_departemen = null;
            }else if($level_user == '2'){ // Kepala Departememn
                $id_cabang = Convertion::idCabang($id_karyawan, $id_company);
                $id_departemen = Convertion::kepDepartemen($id_karyawan, $id_company);
            }else{ 
                $select_data = collect(DB::select("SELECT id_cabang, id_departemen
                                                    FROM data_karyawan
                                                    WHERE id_karyawan = '$id_karyawan'
                                                    AND id_company = '$id_company'"))->first();
                if($level_user == '5'){ //Kepala Cabang
                    $id_cabang = $select_data->id_cabang;
                    $id_departemen = null;
                    $filter_karyawan = $id_karyawan;
                }else{ // Staff level 3, Finance level 6
                    $id_cabang = $select_data->id_cabang;
                    $id_departemen = $select_data->id_departemen;
                    $filter_karyawan = $id_karyawan;
                }
            }

            // DB::enableQueryLog();
            $data_izin = DB::select("SELECT data_izin.id_izin,
                                            data_karyawan.nama AS nama_karyawan,
                                            master_cabang.nama AS nama_cabang,
                                            master_departemen.nama AS nama_departemen,
                                            master_jabatan.nama AS nama_jabatan,
                                            data_izin.tgl_mulai_izin,
                                            data_izin.tgl_selesai_izin,
                                            data_izin.jenis_izin,
                                            data_izin.keterangan,
                                            data_izin.tgl_pengajuan,
                                            data_izin.status,
                                            data_izin.file
                                    FROM data_izin
                                    LEFT JOIN data_karyawan ON data_karyawan.id_karyawan = data_izin.id_karyawan
                                    LEFT JOIN master_cabang ON master_cabang.id_cabang = data_karyawan.id_cabang
                                    LEFT JOIN master_departemen ON master_departemen.id_departemen = data_karyawan.id_departemen
                                    LEFT JOIN master_jabatan ON master_jabatan.id_jabatan = data_karyawan.id_jabatan
                                    WHERE data_izin.id_company = '$id_company' ".
                                    ((empty($id_cabang))?" ":" AND data_karyawan.id_cabang = '$id_cabang' ").
                                    ((empty($id_departemen))?" ":" AND data_karyawan.id_departemen IN ('$id_departemen') ").
                                    (($filter_karyawan == 'all')?" ":" AND data_karyawan.id_karyawan = '$filter_karyawan' ").
                                    ((empty($filter_bulan))?" ":" AND DATE_FORMAT(data_izin.tgl_pengajuan,'%Y-%m') = '$filter_bulan' ").
                                    ((empty($start_date) && empty($end_date))?" ":" AND (DATE_FORMAT(data_izin.tgl_pengajuan,'%Y-%m-%d') >= '$start_date' AND DATE_FORMAT(data_izin.tgl_pengajuan,'%Y-%m-%d') <= '$end_date') ").
                                    " ORDER BY data_izin.tgl_pengajuan DESC
                                    LIMIT $limit_start,$limit_end");
            $query = DB::getQueryLog();

            print_r($query);
            die();

            $total_data = collect(DB::select("SELECT data_izin.id_izin
                                            FROM data_izin
                                            LEFT JOIN data_karyawan ON data_karyawan.id_karyawan = data_izin.id_karyawan
                                            LEFT JOIN master_cabang ON master_cabang.id_cabang = data_karyawan.id_cabang
                                            LEFT JOIN master_departemen ON master_departemen.id_departemen = data_karyawan.id_departemen
                                            LEFT JOIN master_jabatan ON master_jabatan.id_jabatan = data_karyawan.id_jabatan
                                            WHERE data_izin.id_company = '$id_company' ".
                                            ((empty($id_cabang))?" ":" AND data_karyawan.id_cabang = '$id_cabang' ").
                                            ((empty($id_departemen))?" ":" AND data_karyawan.id_departemen IN ('$id_departemen') ").
                                            (($filter_karyawan == 'all')?" ":" AND data_karyawan.id_karyawan = '$filter_karyawan' ").
                                            ((empty($filter_bulan))?" ":" AND DATE_FORMAT(data_izin.tgl_pengajuan,'%Y-%m') = '$filter_bulan' ").
                                            ((empty($start_date) && empty($end_date))?" ":" AND (DATE_FORMAT(data_izin.tgl_pengajuan,'%Y-%m-%d') >= '$start_date' AND DATE_FORMAT(data_izin.tgl_pengajuan,'%Y-%m-%d') <= '$end_date') ").
                                            " ORDER BY data_izin.tgl_pengajuan DESC"))->count();


            if(count($data_izin) > 0){
                $data = array();
                foreach($data_izin as $rows){
                    $data[] = array("id_izin" => $rows->id_izin,
                                    "nama_karyawan" => $rows->nama_karyawan,
                                    "nama_cabang" => $rows->nama_cabang,
                                    "nama_departemen" => $rows->nama_departemen,
                                    "nama_jabatan" => $rows->nama_jabatan,
                                    "tgl_pengajuan" => DateFormat::format($rows->tgl_pengajuan,'N d M Y H i s'),
                                    "tgl_mulai_izin" =>  DateFormat::format($rows->tgl_mulai_izin,'d M Y'),
                                    "tgl_selesai_izin" =>  DateFormat::format($rows->tgl_selesai_izin,'d M Y'),
                                    "kode_izin" => $rows->jenis_izin,
                                    "jenis_izin" => Convertion::jenisIzin($rows->jenis_izin),
                                    "keterangan" => $rows->keterangan,
                                    "status" => Convertion::statusIzin($rows->status),
                                    "file" => URL::to($rows->file));
                }

                if(count($data) > 0){
                    $response = array('success'=>true,
                                        'data'=>array('jml_semua_data' => $total_data,
                                                        'jml_tampil' => ($limit_start + count($data_izin)),
                                                        'list_data' => $data));
                    $json = response()->json($response,200);
                }else{
                    $response = array('success'=>false,
                                    'message'=>'Tidak ada data untuk ditampilkan');
                    $json = response()->json($response,400);
                }

            }else{
                $response = array('success'=>false,
                                    'message'=>'Tidak ada data untuk ditampilkan');
                $json = response()->json($response,400);
            }

            return $json;
        }

       /* public static function batalkan($id_izin = null){
            $update = DB::update("UPDATE data_izin
                                SET status = '2'
                                WHERE id_izin = '$id_izin'");
            if($update){
                $response = array('success'=>true,
                                'message'=>'Izin berhasil dibatalkan');
                $json = response()->json($response,200);
            }else{
                $response = array('success'=>false,
                                'message'=>'izin gagal dibatalkan');
                $json = response()->json($response,400);
            }

            return $json;
        }*/

        public static function tolak($id_izin = null){
            $data_izin = collect(DB::select("SELECT id_karyawan,
                                                    id_company,
                                                    tgl_mulai_izin,
                                                    tgl_selesai_izin
                                            FROM data_izin
                                            WHERE id_izin = '$id_izin'"))->first();

            $id_karyawan = $data_izin->id_karyawan;
            $id_company = $data_izin->id_company;
            $tgl_mulai_izin = date_format(date_create($data_izin->tgl_mulai_izin),'Y-m-d');
            $tgl_selesai_izin = date_format(date_create($data_izin->tgl_selesai_izin),'Y-m-d');

            if(date('Y-m-d') > $tgl_mulai_izin){
                $response = array('success'=>false,
                                'message'=>'Tanggal izin sudah terlewat, izin tidak bisa dibatalkan');
                return response()->json($response,400);
            }

            $begin = new DateTime($tgl_mulai_izin);
            $end = new DateTime(date('Y-m-d',strtotime($tgl_selesai_izin . "+1 days")));

            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($begin, $interval, $end);

            foreach ($period as $dt) {
                $tgl_izin = $dt->format("Y-m-d");

                //HAPUS R_ABSENSI
                DB::delete("DELETE FROM r_absensi 
                            WHERE id_company='$id_company'
                            AND id_karyawan ='$id_karyawan'
                            AND tgl_absen = '$tgl_izin'");

                //HAPUS R_ABSENSI
                DB::delete("DELETE FROM absensi_masuk 
                            WHERE id_company='$id_company'
                            AND id_karyawan ='$id_karyawan'
                            AND tgl_absen = '$tgl_izin'");

            }

            $update = DB::update("UPDATE data_izin
                                    SET status = '3'
                                    WHERE id_izin = '$id_izin'");
            if($update){
                $response = array('success'=>true,
                                'message'=>'Izin berhasil ditolak');
                $json = response()->json($response,200);
            }else{
                $response = array('success'=>false,
                                'message'=>'izin gagal ditolak');
                $json = response()->json($response,400);
            }

            return $json;
        }

        /*public static function setujui($id_izin = null){
            $data_izin = collect(DB::select("SELECT id_karyawan,
                                                id_company,
                                                tgl_mulai_izin,
                                                tgl_selesai_izin,
                                                jenis_izin,
                                                keterangan,
                                                status,
                                                file
                                        FROM data_izin
                                        WHERE id_izin = '$id_izin'"))->first();

            $id_karyawan = $data_izin->id_karyawan;
            $id_company = $data_izin->id_company;
            $tgl_mulai_izin = $data_izin->tgl_mulai_izin;
            $tgl_selesai_izin = $data_izin->tgl_selesai_izin;
            $jenis_izin = $data_izin->jenis_izin;
            $keterangan = $data_izin->keterangan;
            $status = $data_izin->status;
            $file = $data_izin->file;

            // if($status == '4'){
            //     $response = array('success'=>false,
            //                     'message'=> Convertion::jenisIzin($jenis_izin).' sudah disetujui');
            //     return response()->json($response,400);
            // }

            $begin = new DateTime($tgl_mulai_izin);
            $end = new DateTime(date('Y-m-d',strtotime($tgl_selesai_izin . "+1 days")));

            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($begin, $interval, $end);

            foreach ($period as $dt) {
                $tgl_izin = $dt->format("Y-m-d");

                $id_absensi = Absensi_m::getId($id_company, $table='absensi_masuk');

                $data = array('id_absensi_masuk' => $id_absensi,
                            'jenis_absen' => 'reguler',
                            'kode_absen' => $jenis_izin,
                            'id_company' => $id_company,
                            'id_karyawan' => $id_karyawan,
                            'tgl_absen' => $tgl_izin,
                            'keterangan' => $keterangan,
                            'foto' => $file);
                
                Absensi_m::addAbsensi($table='absensi_masuk', $data);
            }

            $update = DB::update("UPDATE data_izin
                                    SET status = '4'
                                    WHERE id_izin = '$id_izin'");
            if($update){
                $response = array('success'=>true,
                                'message'=> Convertion::jenisIzin($jenis_izin).' berhasil disetujui');
                $json = response()->json($response,200);
            }else{
                $response = array('success'=>false,
                                'message'=> Convertion::jenisIzin($jenis_izin).' gagal disetujui');
                $json = response()->json($response,400);
            }

            return $json;

        }*/

        //-----------------------------------------------SURYA-------------------------------------------------------//

        private static function statusApproval($id_status){
            if ($id_status == '0') $status = array(
                'status' => 'tidak perlu persetujuan',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFF4C4C4C'
            );
            else if($id_status == '1') $status = array(
                'status' => 'Belum disetujui',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFF4C4C4C'
            );
            else if($id_status == '2') $status = array(
                'status' => 'Dibatalkan',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFFFFFFFF'
            );
            else if($id_status == '3') $status = array(
                'status' => 'Ditolak',
                'color' => '0XFFFF6666',
                'font_color' => '0XFFFFFFFF'
            );
            else if($id_status == '4') $status = array(
                'status' => 'Disetujui',
                'color' => '0XFF74C077',
                'font_color' => '0XFFFFFFFF'
            );
            else if($id_status == '5') $status = array(
                'status' => 'Belum disetujui',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFF4C4C4C'
            );
            return $status;
        }

        public static function getDataIzin($data = array()){
            $id_company         = $data['id_company'];
            $id_karyawan        = $data['id_karyawan'];
            $id_karyawan_select = $data['id_karyawan_select'];
            $id_cabang          = $data['id_cabang'];
            $id_departemen         = $data['id_departemen'];
            $level_user         = $data['level_user'];
            $limit              = $data['limit'];
            $offset             = $data['offset'];
            $konteks            = $data['konteks'];
            $id_izin            = $data['id_izin'];
            $month_year         = $data['month_year'];
            $range_tanggal_mulai     = $data['range_tanggal_mulai'];
            $range_tanggal_selesai   = $data['range_tanggal_selesai'];

            $data_izin = DB::table('data_izin as DI')
            ->select('DI.*', 'DK.nama_lengkap')
            ->selectRaw('IF(DI.approval_hrd="5"&&'.$level_user.'="1", TRUE, FALSE) as button_approval_hrd')
            ->join('data_karyawan as DK', 'DI.id_karyawan', '=', 'DK.id_karyawan')
            ->join('master_jabatan as MJ', 'MJ.id_jabatan', '=', 'DK.id_jabatan')
            ->join('usergroup as UG', 'UG.id', '=', 'MJ.level_user')
            ->orderBy('tgl_pengajuan', 'desc')
            ->limit($limit)->offset($offset)
            ->where('DK.id_company', $id_company);
            

            //KONTEKS PENGAJUAN, REKAP, ATAU KARYAWAN
            if($konteks=='izinSaya') $where = [array('DI.id_karyawan', '=', $id_karyawan)];
            else if($konteks=='rekapSaya') $where = [array('DI.id_karyawan', '=', $id_karyawan), array('DI.status', '=', '4'),];
            else if($konteks=='izinPegawai' || $konteks=='rekapPegawai'){
                    $where = array();
                    $data_izin = $data_izin->where('UG.urutan', '>',  User_m::get_data_user_by_id($id_karyawan)->urutan)
                        ->where('DK.id_karyawan', '!=', $id_karyawan);
                    if($level_user=='1') {
                        // $where[] = array('approval_hrd', '<>', '5');
                        // $where[] = array('approval_hrd', '<>', '0');
                        // $data_izin = $data_izin->where(function ($query) {
                        //     $query = $query->whereNotIn('approval_hrd', ['0'])
                        //         ->orWhere('DI.status', '4');  
                        // });
                    }
                    else if($level_user=='2') {
                        // $where[] = array('approval_kedep', '<>', '5');
                        // $where[] = array('approval_kedep', '<>', '0');
                        $data_izin = $data_izin->where(function ($query) {
                            $query = $query->whereNotIn('approval_kedep', ['5','0'])
                                ->orWhere('DI.status', '4');  
                        });
                        $p_kedep = User_m::getPKedep($id_karyawan);
                        if (count($p_kedep)==0) {//HANYA MENJABAT 1 KEPALA DEPARTEMEN
                            $where[] = array('DK.id_departemen', '=', User_m::get_data_karyawan($id_karyawan)->id_departemen);
                        }
                        else{//BISA MENJABAT LEBIH DARI 1 KEPALA DEPARTEMEN
                            $list_id_departemen = array();
                            foreach ($p_kedep as $row_kedep) {
                                $list_id_departemen[] = $row_kedep->id_departemen;   
                            } 
                            $data_izin = $data_izin->whereIn('DK.id_departemen', $list_id_departemen); 
                        }
                    }
                    else if($level_user=='4') {
                        // $where[] = array('approval_direksi', '<>', '5');
                        // $where[] = array('approval_direksi', '<>', '0');
                        $data_izin = $data_izin->where(function ($query) {
                            $query = $query->whereNotIn('approval_direksi', ['5','0'])
                                ->orWhere('DI.status', '4');  
                        });
                    }
                    else if($level_user=='5') {
                        // $where[] = array('approval_kacab', '<>', '5');
                        // $where[] = array('approval_kacab', '<>', '0');
                        $data_izin = $data_izin->where(function ($query) {
                            $query = $query->whereNotIn('approval_kacab', ['5','0'])
                                ->orWhere('DI.status', '4');    
                        });
                        $p_kacab = User_m::getPKacab($id_karyawan);
                        if (count($p_kacab)==0) {//HANYA MENJABAT 1 KEPALA CABANG
                            $where[] = array('DK.id_cabang', '=', User_m::get_data_karyawan($id_karyawan)->id_cabang);
                        }
                        else{//BISA MENJABAT LEBIH DARI 1 KEPALA CABANG
                            $list_id_cabang = array();
                            foreach ($p_kacab as $row_kacab) {
                                $list_id_cabang[] = $row_kacab->id_cabang;   
                            } 
                            $data_izin = $data_izin->whereIn('DK.id_cabang', $list_id_cabang);
                        }
                    }
                    else if($level_user=='7') {
                        // $where[] = array('approval_spv', '<>', '5');
                        // $where[] = array('approval_spv', '<>', '0');
                        $data_izin = $data_izin->where(function ($query) {
                            $query = $query->whereNotIn('approval_spv', ['5','0'])
                                ->orWhere('DI.status', '4');  
                        });
                        $where[] = array('DK.supervisi', '=', $id_karyawan);
                    }
                if($konteks=='rekapPegawai') {
                    $data_izin = $data_izin->whereNotIn('DI.status', ['1', '2']);
                }
                
            }
            else if($konteks=='izinDetail') $where[] = array('id_izin', '=', $id_izin);
            //
            if($id_cabang!=null) $where[] = array('DK.id_cabang', '=', $id_cabang);
            if($id_departemen!=null) $where[] = array('DK.id_departemen', '=', $id_departemen);
            if($id_karyawan_select!=null) $where[] = array('DK.id_karyawan', '=', $id_karyawan_select);
            if($month_year!=null) $data_izin = $data_izin->where(DB::raw("DATE_FORMAT(DI.tgl_mulai_izin,'%Y-%m')"), $month_year);

            if($range_tanggal_mulai!=null && $range_tanggal_selesai!=null) {
                 $where[] = array('tgl_mulai_izin', '>=', $range_tanggal_mulai);
                 $where[] = array('tgl_selesai_izin', '<=', $range_tanggal_selesai);
            }
               
            $data_izin = $data_izin->where($where)->get();
            //

            $respon = array();
            foreach ($data_izin as $row) {
                $aksi_batalkan = true;
                $list_approval = array();
                if($row->status!='1'||$row->approval_spv=='4'||$row->approval_kedep=='4'||$row->approval_kacab=='4'||$row->approval_hrd=='4'||$row->approval_direksi=='4') $aksi_batalkan = false;
                if($row->approval_spv!='0'){
                    $statusApproval = self::statusApproval($row->approval_spv);
                    $list_approval[] = array(
                        'status_approval'   => $statusApproval['status'].' SPV',
                        'tanggal_approval'  => $row->tgl_apv_spv,
                        'warna_status'      => $statusApproval['color'],
                        'warna_font'        => $statusApproval['font_color']
                    );
                }
                    
                if($row->approval_kedep!='0'){
                    $statusApproval = self::statusApproval($row->approval_kedep);
                    $list_approval[] = array(
                        'status_approval'   => $statusApproval['status'].' Kepala Departemen',
                        'tanggal_approval'  => $row->tgl_apv_kedep,
                        'warna_status'      => $statusApproval['color'],
                        'warna_font'        => $statusApproval['font_color']
                    );
                }
                    
                if($row->approval_kacab!='0'){
                    $statusApproval = self::statusApproval($row->approval_kacab);
                     $list_approval[] = array(
                        'status_approval'   => $statusApproval['status'].' Kepala Cabang',
                        'tanggal_approval'  => $row->tgl_apv_kacab,
                        'warna_status'      => $statusApproval['color'],
                        'warna_font'        => $statusApproval['font_color']
                    );
                }

                if($row->approval_direksi!='0'){
                    $statusApproval = self::statusApproval($row->approval_direksi);
                    $list_approval[] = array(
                        'status_approval'   => $statusApproval['status'].' Direksi',
                        'tanggal_approval'  => $row->tgl_apv_direksi,
                        'warna_status'      => $statusApproval['color'],
                        'warna_font'        => $statusApproval['font_color']
                    );
                }
                   
                if($row->approval_hrd!='0'){
                    $statusApproval = self::statusApproval($row->approval_hrd);
                    $list_approval[] = array(
                        'status_approval'   => $statusApproval['status'].' HRD',
                        'tanggal_approval'  => $row->tgl_apv_hrd,
                        'warna_status'      => $statusApproval['color'],
                        'warna_font'        => $statusApproval['font_color']
                    );
                }
                    
                

                //TAMPILKAN AKSI?
                $aksi = false;
                if($level_user=='1' && $row->status=='1') $aksi = true;
                else if($level_user=='2' && $row->approval_kedep=='1' && $row->status=='1') $aksi = true;
                else if($level_user=='4' && $row->approval_direksi=='1' && $row->status=='1') $aksi = true;
                else if($level_user=='5' && $row->approval_kacab=='1' && $row->status=='1') $aksi = true;
                else if($level_user=='7' && $row->approval_spv=='1' && $row->status=='1') $aksi = true;
                //
                    
                $respon[] = array(
                    'id_izin'           => $row->id_izin,
                    'id_karyawan'       => $row->id_karyawan,
                    'status'            => self::statusApproval($row->status)['status'],
                    'tgl_pengajuan'     => date_format(date_create($row->tgl_pengajuan), "d-m-Y"),
                    'nama_izin'         => $row->nama_izin,
                    'nama'              => $row->nama_lengkap,
                    'ket_izin'          => $row->ket_izin,
                    'jumlah_izin'       => strval($row->jml_izin),
                    'tgl_mulai_izin'    => date_format(date_create($row->tgl_mulai_izin), "d-m-Y"),
                    'tgl_selesai_izin'  => date_format(date_create($row->tgl_selesai_izin), "d-m-Y"),
                    'list_approval'     => $list_approval,
                    'list_image'        => self::getListImage($row->id_izin),
                    'aksi'              => $aksi,
                    'aksi_batalkan'     => $aksi_batalkan,
                    'button_approval_hrd'     => $row->button_approval_hrd==1?TRUE:FALSE
                );   
            }

            if (count($data_izin)>0) 
                $response = array('success' => true, 'message' => 'data izin berhasil ditemukan', 'data' => $respon);
            else 
                $response = array('success'=>false,'message'=> 'data izin gagal ditemukan');
            return response()->json($response,200);
        }

        public static function getListImage($id_izin){
            $data =  DB::table('file_izin')->select('file')->where('id_izin', $id_izin)->get();
            $path_foto = "absensi/".env('NAME_APPLICATION')."/";
            foreach($data as $rows){
                // $rows->file = Convertion::cekFoto($rows->file)?url('../web').$rows->file:'-';
                $rows->file = ($rows->file == ''?'-':(Uploads_c::cekFoto($path_foto.$rows->file)?Uploads_c::retrieve_file_url($rows->file, 'file'):'-'));
            }
            return $data;
        }

        public static function getJenisIzin($id_company){
            $data_jenis_izin = DB::table('master_jenis_izin')
            ->where('id_company', $id_company)
            ->select('kode_izin AS id', 'nama', 'jenis_izin', 'flag')->get();
            if (count($data_jenis_izin)>0) 
                $response = array('success' => true, 'message' => 'data jenis izin berhasil ditemukan', 'data' => $data_jenis_izin); 
            else
                $response = array('success'=>false, 'message' => 'data jenis izin gagal ditemukan');
            return response()->json($response,200);
        }

        public static function batalkan($id_izin = null, $id_karyawan = null){
            $update = DB::table('data_izin')->where(
                [
                    'id_izin' => $id_izin, 
                    'id_karyawan' => $id_karyawan
                ])
            ->whereNotIn('approval_spv', ['3', '4'])
            ->whereNotIn('approval_kedep', ['3', '4'])
            ->whereNotIn('approval_kacab', ['3', '4'])
            ->whereNotIn('approval_hrd', ['3', '4'])
            ->whereNotIn('approval_direksi', ['3', '4'])
            ->update(['status' => '2',]);

            if($update){
                $response = array('success'=>true,
                                'message'=>'Izin berhasil dibatalkan');
            }else{
                $response = array('success'=>true,
                                'message'=>'izin tidak dapat dibatalkan');
            }
            return response()->json($response,200);
        }

        public static function get_p_approval($level_user, $id_company, $id_cabang){
            $data = DB::table('p_approval_izin')
            ->select('level_approval')
            ->where(
                [
                    'level_user' => $level_user, 
                    'id_company' => $id_company,
                    'id_cabang' => $id_cabang
                ]
            )->first();
            if ($data!=null) {
                return $data->level_approval;
            }
            else return null; 
        }

        public static function get_data_user($id_jabatan, $id_company){
            $data = DB::table('users')->join('data_karyawan', 'data_karyawan.id_karyawan', '=', 'users.id_karyawan')->select('users.id_karyawan', 'users.token_fcm')->where(['data_karyawan.id_jabatan' => $id_jabatan, 'users.id_company' => $id_company])->first();
            return $data;
        }

        public static function get_master_jabatan($level_approval, $id_company, $id_departemen){
            $data = DB::table('master_jabatan as mj')
                ->select('mj.level_user', 'mj.nama', 'mj.id_jabatan')
                ->leftJoin('data_karyawan as dk', 'dk.id_jabatan', '=', 'mj.id_jabatan')
                ->groupBy('dk.id_jabatan')
                ->whereIn('mj.level_user', explode(',', $level_approval))
                ->where('dk.id_departemen', $id_departemen)
                ->where('dk.id_company', $id_company)
                ->get();
            return $data;
        }

        public static function get_id_izin($id_company){
            $nomor_urut_terakhir = DB::table('data_izin')
                                    ->select('id_izin')
                                    ->orderBy('id_izin', 'DESC')
                                    ->where('id_company', '=', $id_company)
                                    ->where('tgl_pengajuan', 'like', '%'.date('Y-m').'%')
                                    ->first();
            if($nomor_urut_terakhir!=null){
                return substr($nomor_urut_terakhir->id_izin,2)+1;
            }
            else return $id_company.date('Ym').sprintf("%04d", 1);
            
        }

        public static function get_id_absensi($id_company){
            $nomor_urut_terakhir = DB::table('absensi_masuk')
                                    ->where('id_company', '=', $id_company)
                                    ->where('tgl_input', 'like', '%'.date('Y-m').'%')
                                    ->count();
            return $id_company.date('Ym').sprintf("%05d", $nomor_urut_terakhir+1);
        }

        public static function insert_file($id_izin, $alamat_foto, $id_company){
            $insert = DB::table('file_izin')->insert(['id_izin' => $id_izin, 'file' => $alamat_foto, 'id_company' => $id_company]);
            return $insert;
        }

        public static function pengajuan($data = array()){
            $insert = DB::table('data_izin')->insert($data);
            return $insert;
        }

        public static function setujui($data = array(), $id_izin){
            $update = DB::table('data_izin')->where('id_izin', '=', $id_izin)->update($data);
            return $update;
        }

        public static function insertAbsensiIzin($id_izin){
            $data_izin = DB::table('data_izin as di')->select('di.id_karyawan','di.tgl_mulai_izin',
             'di.tgl_selesai_izin', 'di.jml_izin', 'di.id_cabang', 'di.id_company', 'di.kode_izin',
              'di.nama_izin', 'di.ket_izin', 'dk.nama_lengkap', 'dk.nik','mc.kode as kode_cabang',
               'mc.nama as nama_cabang', 'di.id_departemen', 'md.kode as kode_departemen',
               'di.jenis_izin',
                'md.nama as nama_departemen', 'dk.id_jabatan', 'mj.kode_jabatan', 'mj.nama as nama_jabatan')
            ->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'di.id_karyawan')
            ->leftJoin('master_cabang as mc', 'mc.id_cabang', 'dk.id_cabang')
            ->leftJoin('master_departemen as md', 'md.id_departemen', '=', 'di.id_departemen')
            ->join('master_jabatan as mj', 'mj.id_jabatan', '=', 'dk.id_jabatan')
            ->where('id_izin', strval($id_izin))->first();

            $id_karyawan        =   $data_izin->id_karyawan;
            $nik                =   $data_izin->nik;
            $nama_karyawan      =   $data_izin->nama_lengkap;
            $id_cabang          =   $data_izin->id_cabang;
            $kode_cabang        =   $data_izin->kode_cabang??'';
            $nama_cabang        =   $data_izin->nama_cabang??'';
            $id_departemen      =   $data_izin->id_departemen??'';
            $kode_departemen    =   $data_izin->kode_departemen??'';
            $nama_departemen    =   $data_izin->nama_departemen??'';
            $id_jabatan         =   $data_izin->id_jabatan;
            $kode_jabatan       =   $data_izin->kode_jabatan;
            $nama_jabatan       =   $data_izin->nama_jabatan;
            $jenis_absen        =   $data_izin->jenis_izin;
            $kode_absensi       =   $data_izin->kode_izin;
            $ket_kode           =   $data_izin->nama_izin;
            $id_company         =   $data_izin->id_company;
            
            $tanggal_mulai = $data_izin->tgl_mulai_izin;
            $tanggal_selesai = $data_izin->tgl_selesai_izin;
            // $jumlah_hari = date("d", strtotime($tanggal_selesai))-date("d", strtotime($tanggal_mulai))+1;
            $date_tanggal_mulai= new DateTime($tanggal_mulai);
            $date_tanggal_selesai= new DateTime($tanggal_selesai);
            $jumlah_hari = ($date_tanggal_selesai->diff($date_tanggal_mulai))->days+1;
            for($i=0;$i<$jumlah_hari;$i++){
                $tgl_absen = date('Y-m-d',strtotime($tanggal_mulai . "+".$i." days"));
                if(Absensi_m::cekHariKerjaKaryawan($id_karyawan, $id_cabang, $id_company,$tgl_absen)){
                //HAPUS EXISTING ABSENSI
                DB::table('r_absensi')->where([
                    'id_karyawan' => $id_karyawan,
                    'tgl_absen' => $tgl_absen,
                    'id_company'  => $id_company
                ])->delete();
                DB::table('absensi_masuk')->where([
                    'id_karyawan' => $id_karyawan,
                    'tgl_absen' => $tgl_absen,
                    'id_company'  => $id_company
                ])->delete();
                //
                $data_insert_r_absensi = array(
                    'id_karyawan'       => $id_karyawan,
                    'nik'               => $nik,
                    'nama_karyawan'     => $nama_karyawan,
                    'id_cabang'         => $id_cabang,
                    'kode_cabang'       => $kode_cabang,
                    'nama_cabang'       => $nama_cabang,
                    'id_departemen'     => $id_departemen,
                    'kode_departemen'   => $kode_departemen,
                    'nama_departemen'   => $nama_departemen,
                    'id_jabatan'        => $id_jabatan,
                    'kode_jabatan'      => $kode_jabatan,
                    'nama_jabatan'      => $nama_jabatan,
                    'tgl_absen'         => $tgl_absen,
                    'jenis_absen'       => $jenis_absen,
                    'absen_masuk'       => '00:00:00',
                    'absen_pulang'      => '00:00:00',
                    'kode_absensi'      => $kode_absensi,
                    'ket_kode'          => $ket_kode,
                    'id_company'        => $id_company,
                );
                $data_insert_absen_masuk = array(
                    'id_absensi_masuk'  =>  Absensi_m::getId($id_company,'absensi_masuk'),
                    'id_karyawan'       => $id_karyawan,
                    'tgl_absen'         => $tgl_absen,
                    'jenis_absen'       => $jenis_absen,
                    'jam_kerja'         => '00:00:00',
                    'jam_absen'         => '00:00:00',
                    'terlambat'         => '',
                    'kode_absen'        => $kode_absensi,
                    'ket_kode'          => $ket_kode,
                    'lokasi_absen'      => '',
                    'timezone'          => '',
                    'gmt'               => '',
                    'latitude'          => '',
                    'longitude'         => '',
                    'keterangan'        => '',
                    'foto'              => '',
                    'id_company'        => $id_company,
                );

                DB::table('r_absensi')->insert($data_insert_r_absensi);
                DB::table('absensi_masuk')->insert($data_insert_absen_masuk);
                } 
            }
            return true;
        }

        public static function getJamKerja($day, $id_company, $id_cabang){
            return DB::table('jam_kerja')->select('masuk', 'pulang', 'min_masuk', 'max_masuk', 'libur')->where([
                'hari' => $day,
                'id_company' => $id_company,
                'id_cabang' => $id_cabang
            ])->first();
        }

        public static function getFlagPengurangCuti($kode_izin, $id_company){
            return DB::table('master_jenis_izin')->select('flag')->where(['kode_izin' => $kode_izin, 'id_company' => $id_company])->first()->flag;
        }

        public static function updateSisaCuti($id_izin){
            $data_izin = DB::table('data_izin')->select('kode_izin', 'jml_izin', 'data_izin.id_company', 'jatah_cuti', 'data_izin.id_karyawan', 'data_izin.flag')->join('data_karyawan', 'data_karyawan.id_karyawan', '=', 'data_izin.id_karyawan')->where('id_izin', $id_izin)->first();
            if ($data_izin->flag=='1'&&$data_izin->jatah_cuti>=$data_izin->jml_izin) {
                   DB::table('data_karyawan')->where(['id_karyawan' => $data_izin->id_karyawan])->update(['jatah_cuti' => $data_izin->jatah_cuti-$data_izin->jml_izin]); 
                return true;
            }
            else return false;
        }

        public static function cek_approval_per_column($where){
            $data = DB::table('data_izin')->where($where)
            ->count();
            return $data;
        }

        public static function cek_approval_per_column_by_hrd($id_izin, $approval_column){
            $data = DB::table('data_izin')->where('id_izin', $id_izin)->whereIn($approval_column, ['1', '5'])
            ->count();
            return $data;
        }

        public static function cek_sisa_cuti($id_izin){
            $data_izin = DB::table('data_izin')->select('kode_izin', 'jml_izin', 'data_izin.id_company', 'jatah_cuti', 'data_izin.id_karyawan')->join('data_karyawan', 'data_karyawan.id_karyawan', '=', 'data_izin.id_karyawan')->where('id_izin', $id_izin)->first();
            if (self::getFlagPengurangCuti($data_izin->kode_izin, $data_izin->id_company)) {
                if($data_izin->jatah_cuti>=$data_izin->jml_izin) return true;
                else return false;
            }
            else return true;
        }

        public static function getDataIzinById($id_izin){
            return DB::table('data_izin as di')
                        ->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'di.id_karyawan')
                        ->select('di.nama_izin', 'dk.nama_lengkap', 'di.id_karyawan', 'di.nama_izin', 'di.flag',
                                    'di.user_spv', 'di.id_departemen', 'di.id_cabang', 'di.id_company')
                        ->where('id_izin', $id_izin)->first();
        }

        public static function cekTanggalIzin($id_karyawan, $tanggal_mulai, $tanggal_selesai){
            return DB::table('data_izin')
            ->where(function ($query) use($tanggal_mulai, $tanggal_selesai) {
                    $query->where([array('tgl_mulai_izin', '>=', $tanggal_mulai)])
                    ->where([array('tgl_mulai_izin', '<=', $tanggal_selesai)])
                    ->orWhere([array('tgl_selesai_izin', '<=', $tanggal_selesai)])
                    ->where([array('tgl_selesai_izin', '>=', $tanggal_mulai)])
                    ->orWhere([array('tgl_mulai_izin', '<=', $tanggal_mulai)])
                    ->where([array('tgl_selesai_izin', '>=', $tanggal_selesai)]);
                })
            ->where('id_karyawan',$id_karyawan)
            ->whereNotIn('status', ['2','3'])
            ->count();
        }

        public static function cekMaxPengajuanIzin($kode_izin, $id_company){
           return DB:: table('master_jenis_izin')
            ->select('max_izin', 'nama', 'flag')
            ->where(['kode_izin' => $kode_izin, 'id_company' => $id_company])
            ->first();
        
        }
        
        public static function cekSisaCutiKaryawan($id_karyawan){
            return DB::table('data_karyawan')->select('jatah_cuti')->where('id_karyawan', $id_karyawan)->first();
        }

    }