<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use App\Http\Helpers\Convertion;
    use URL;
    use App\Http\Helpers\DateFormat;
    use App\Models\User_m;
    use App\Http\Controllers\Uploads_c;

    class Reimbursement_m extends Model{

        private static function statusApproval($id_status){
            if ($id_status == '0') $status = array(
                'icon_info' => false,
                'title' => '',
                'status' => 'tidak perlu persetujuan',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFF4C4C4C'
            );
            else if($id_status == '1') $status = array(
                'icon_info' => false,
                'title' => '',
                'status' => 'On Progress',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFF4C4C4C'
            );
            else if($id_status == '2') $status = array(
                'icon_info' => false,
                'title' => '',
                'status' => 'Dibatalkan',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFFFFFFFF'
            );
            else if($id_status == '3') $status = array(
                'icon_info' => true,
                'title' => 'Keterangan Ditolak',
                'status' => 'Ditolak',
                'color' => '0XFFFF6666',
                'font_color' => '0XFFFFFFFF'
            );
            else if($id_status == '4') $status = array(
                'icon_info' => true,
                'title' => 'Keterangan Revisi',
                'status' => 'Direvisi',
                'color' => '0xffFCB544',
                'font_color' => '0XFFFFFFFF'
            );
            else if($id_status == '5') $status = array(
                'icon_info' => true,
                'title' => '',
                'status' => 'Disetujui',
                'color' => '0XFF74C077',
                'font_color' => '0XFFFFFFFF'
            );
            else if($id_status == '6') $status = array(
                'icon_info' => false,
                'title' => '',
                'status' => 'On Progress',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFF4C4C4C'
            );
            return $status;
        }

        private static function statusItemProgress($id_status){
            if ($id_status == '0') $status = array(
                'status' => 'tidak perlu persetujuan',
                'color' => '0XFFE0E0E0',
                'font_color' => '0XFF4C4C4C'
            );
            else if($id_status == '1') $status = array(
                'status' => 'On Progress',
                'color' => '0XFF1575d6',
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
                'status' => 'Direvisi',
                'color' => '0xffFCB544',
                'font_color' => '0XFFFFFFFF'
            );
            else if($id_status == '5') $status = array(
                'status' => 'Disetujui',
                'color' => '0XFF74C077',
                'font_color' => '0XFFFFFFFF'
            );
            else if($id_status == '6') $status = array(
                'status' => 'On Progress',
                'color' => '0XFF1575d6',
                'font_color' => '0XFF4C4C4C'
            );
            return $status;
        }

        private static function listApproval(
            $apv_spv, $apv_kedep, $apv_kacab, $apv_finance, $apv_direksi,
            $tgl_apv_spv, $tgl_apv_kedep, $tgl_apv_kacab, $tgl_apv_finance, $tgl_apv_direksi, $konteks='pengajuan',
            $ket_rev_spv=null,$ket_rev_kedep=null,$ket_rev_kacab=null,$ket_rev_finance=null,$ket_rev_direksi=null,
            $ket_tolak_spv=null,$ket_tolak_kedep=null,$ket_tolak_kacab=null,$ket_tolak_finance=null,$ket_tolak_direksi=null){
            $list_approval = array();
            if($apv_spv!='0' && $apv_spv!='6'){
                $statusApproval = self::statusApproval($apv_spv);
                $informasi_keterangan = '';
                if($konteks=='item'){
                    if($apv_spv=='3') $informasi_keterangan = $ket_tolak_spv;
                    else if($apv_spv=='4') $informasi_keterangan = $ket_rev_spv;
                }
                
                $list_approval[] = array(
                    'icon_info'         => $statusApproval['icon_info'],
                    'title'             => $statusApproval['title'],
                    'id_status'         => $apv_spv,
                    'informasi_keterangan' => $informasi_keterangan,
                    'status_approval'   => $statusApproval['status'].' SPV',
                    'tanggal_approval'  => $tgl_apv_spv,
                    'warna_status'      => $statusApproval['color'],
                    'warna_font'        => $statusApproval['font_color']
                );
            }
                
            if($apv_kedep!='0' && $apv_kedep!='6'){
                $statusApproval = self::statusApproval($apv_kedep);
                $informasi_keterangan = '';
                if($konteks=='item'){
                    if($apv_kedep=='3') $informasi_keterangan = $ket_tolak_kedep;
                    else if($apv_kedep=='4') $informasi_keterangan = $ket_rev_kedep;
                }
                
                $list_approval[] = array(
                    'icon_info'         => $statusApproval['icon_info'],
                    'title'             => $statusApproval['title'],
                    'id_status'         => $apv_kedep,
                    'informasi_keterangan' => $informasi_keterangan,
                    'status_approval'   => $statusApproval['status'].' Kepala Departemen',
                    'tanggal_approval'  => $tgl_apv_kedep,
                    'warna_status'      => $statusApproval['color'],
                    'warna_font'        => $statusApproval['font_color']
                );
            }
                
            if($apv_kacab!='0' && $apv_kacab!='6'){
                $statusApproval = self::statusApproval($apv_kacab);
                $informasi_keterangan = '';
                if($konteks=='item'){
                    if($apv_kacab=='3') $informasi_keterangan = $ket_tolak_kacab;
                    else if($apv_kacab=='4') $informasi_keterangan = $ket_rev_kacab;
                }
                
                $list_approval[] = array(
                    'icon_info'         => $statusApproval['icon_info'],
                    'title'             => $statusApproval['title'],
                    'id_status'         => $apv_kacab,
                    'informasi_keterangan' => $informasi_keterangan,
                    'status_approval'   => $statusApproval['status'].' Kepala Cabang',
                    'tanggal_approval'  => $tgl_apv_kacab,
                    'warna_status'      => $statusApproval['color'],
                    'warna_font'        => $statusApproval['font_color']
                );
            }
                
            if($apv_finance!='0' && $apv_finance!='6'){
                $statusApproval = self::statusApproval($apv_finance);
                $informasi_keterangan = '';
                if($konteks=='item'){
                    if($apv_finance=='3') $informasi_keterangan = $ket_tolak_finance;
                    else if($apv_finance=='4') $informasi_keterangan = $ket_rev_finance;
                }
                
                $list_approval[] = array(
                    'icon_info'         => $statusApproval['icon_info'],
                    'title'             => $statusApproval['title'],
                    'id_status'         => $apv_finance,
                    'informasi_keterangan' => $informasi_keterangan,
                    'status_approval'   => $statusApproval['status'].' Finance',
                    'tanggal_approval'  => $tgl_apv_finance,
                    'warna_status'      => $statusApproval['color'],
                    'warna_font'        => $statusApproval['font_color']
                );
            }
                
            if($apv_direksi!='0' && $apv_direksi!='6'){
                $statusApproval = self::statusApproval($apv_direksi);
                $informasi_keterangan = '';
                if($konteks=='item'){
                    if($apv_direksi=='3') $informasi_keterangan = $ket_tolak_direksi;
                    else if($apv_direksi=='4') $informasi_keterangan = $ket_rev_direksi;
                }
                
                $list_approval[] = array(
                    'icon_info'         => $statusApproval['icon_info'],
                    'title'             => $statusApproval['title'],
                    'id_status'         => $apv_direksi,
                    'informasi_keterangan' => $informasi_keterangan,
                    'status_approval'   => $statusApproval['status'].' Direksi',
                    'tanggal_approval'  => $tgl_apv_direksi,
                    'warna_status'      => $statusApproval['color'],
                    'warna_font'        => $statusApproval['font_color']
                );
            }
            return $list_approval;
        }

        private static function statusItem(
            $apv_spv, $apv_kedep, $apv_kacab, $apv_finance, $apv_direksi, $level_user){
            $statusApproval = null;
            $list_approval = null;
            if($apv_spv!='0' && $level_user=='7'){
                $statusApproval = self::statusItemProgress($apv_spv);
            }
            else if($apv_kedep!='0' && $level_user=='2'){
                $statusApproval = self::statusItemProgress($apv_kedep);
            }
                
            else if($apv_kacab!='0' && $level_user=='5'){
                $statusApproval = self::statusItemProgress($apv_kacab);
            }
                
            else if($apv_finance!='0' && $level_user=='6'){
                $statusApproval = self::statusItemProgress($apv_finance);
            }
                
            else if($apv_direksi!='0' && $level_user=='4'){
                $statusApproval = self::statusItemProgress($apv_direksi);
            }

            if($statusApproval!=null)
                $list_approval = array(
                    'status_approval'   => $statusApproval['status'],
                    'warna_status'      => $statusApproval['color'],
                    'warna_font'        => $statusApproval['font_color']
                );
            
            return $list_approval;
        }

        public static function getDataReimbursement(
            $konteks, $id_karyawan, $id_company, $id_cabang, $id_departemen, $level_user, 
            $filter_departemen, $filter_cabang, $filter_id_karyawan,
            $month_year, $range_tanggal_mulai, $range_tanggal_selesai,
            $id_pengajuan, $filter_status, $limit, $offset){

            $data_reimbursement = DB::table('reimbursement_pengajuan as rp')
                ->select(
                    'rp.id as id_pengajuan','rp.no_pengajuan', 'rp.tgl_pengajuan', 'rp.id_karyawan', 'rp.status',
                    DB::raw('COUNT(rd.id) as jml_pengajuan'),
                    DB::raw('SUM(rd.nominal) as total_pengajuan'),
                    DB::raw('SUM(rd.nominal_disetujui) as total_pengajuan_disetujui'),
                    'rp.apv_spv','rp.apv_kedep', 'rp.apv_kacab', 'rp.apv_finance', 'rp.apv_direksi',
                    'rp.tgl_apv_spv', 'rp.tgl_apv_kedep', 'rp.tgl_apv_kacab', 'rp.tgl_apv_finance', 'rp.tgl_apv_direksi',
                    'dk.nama_lengkap as nama_karyawan', 'md.nama as nama_departemen', 'mc.nama as nama_cabang'
                )
                ->join('reimbursement_data as rd', 'rd.id_pengajuan_reimbursement', '=', 'rp.id')
                ->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'rp.id_karyawan')
                ->join('master_departemen as md', 'md.id_departemen', '=', 'dk.id_departemen')
                ->join('master_cabang as mc', 'mc.id_cabang', '=', 'dk.id_cabang')
                ->join('master_jabatan as mj', 'mj.id_jabatan', '=', 'dk.id_jabatan')
                ->join('usergroup as ug', 'ug.id', '=', 'mj.level_user')
                ->groupBy('rp.id')
                ->limit($limit)->offset($offset)
                ->where('rp.id_company', $id_company)
                ->orderBy('rp.tgl_pengajuan', 'DESC');
                    
            //FILTER
            if($range_tanggal_mulai!=null && $range_tanggal_selesai!=null) $data_reimbursement = $data_reimbursement
                ->where([
                    array('tgl_pengajuan', '>=', $range_tanggal_mulai),
                    array('tgl_pengajuan', '<=', $range_tanggal_selesai)
                    ]);
            if($month_year!=null) $data_reimbursement = $data_reimbursement
                ->where(DB::raw("DATE_FORMAT(rp.tgl_pengajuan,'%Y-%m')"), $month_year);
            if($filter_cabang!=null) $data_reimbursement = $data_reimbursement
                ->where('rp.id_cabang', $filter_cabang);
            if($filter_departemen!=null) $data_reimbursement = $data_reimbursement
                ->where('rp.id_departemen', $filter_departemen);
            if($filter_id_karyawan!=null) $data_reimbursement = $data_reimbursement
                ->where('rp.id_karyawan', $filter_id_karyawan);
            if($filter_status!=null) $data_reimbursement = $data_reimbursement->where('rp.status', $filter_status);
                
            

            //KONTEKS REIMBURSEMENT SAYA / KARYAWAN / DETAIL /REKAP
            if($konteks=='reimbursementSaya') //REIMBURSEMENT SAYA
                $data_reimbursement = $data_reimbursement
                    ->where('rp.id_karyawan' , $id_karyawan);
            else if($konteks=='rekapSaya') //REIMBURSEMENT REKAP SAYA
                $data_reimbursement = $data_reimbursement
                    ->where('rp.id_karyawan', $id_karyawan)
                    ->whereNotIn('rp.status' , ['1', '4']);
            else if($konteks=='reimbursementDetail') //REIMBURSEMENT DETAIL
                $data_reimbursement = $data_reimbursement
                ->where('rp.id', $id_pengajuan);
            else if($konteks=='reimbursementKaryawan' || $konteks=='rekapKaryawan'){//REIMBURSEMENT KARYAWAN
                $data_reimbursement = $data_reimbursement->where('ug.urutan', '>',  User_m::get_data_user_by_id($id_karyawan)->urutan);
                $data_reimbursement = $data_reimbursement->where('rp.id_karyawan', '!=', $id_karyawan);
                if($konteks=='rekapKaryawan') $data_reimbursement = $data_reimbursement->whereNotIn('rp.status' , ['1', '4']);
                if($level_user=='6')//finance
                    $data_reimbursement = $data_reimbursement
                    ->where(function ($query) {
                        $query = $query->whereNotIn('rp.apv_finance', ['6','0'])
                            ->orWhere('rp.status', '5');
                                
                    });
                else if($level_user=='2'){//kepala departemen
                    $data_reimbursement = $data_reimbursement
                    ->where(function ($query) {
                        $query = $query->whereNotIn('rp.apv_kedep', ['6','0'])
                            ->orWhere('rp.status', '5');
                                
                    });
                    $p_kedep = User_m::getPKedep($id_karyawan);
                    if (count($p_kedep)==0) {//HANYA MENJABAT 1 KEPALA DEPARTEMEN
                        $data_reimbursement = $data_reimbursement
                        ->where('rp.id_departemen', $id_departemen);
                    }
                    else{//BISA MENJABAT LEBIH DARI 1 KEPALA DEPARTEMEN
                        $list_id_departemen = array();
                        foreach ($p_kedep as $row_kedep) {
                            $list_id_departemen[] = $row_kedep->id_departemen;   
                        } 
                        $data_reimbursement = $data_reimbursement->whereIn('rp.id_departemen', $list_id_departemen); 
                    }
                }
                else if($level_user=='5'){//kepala cabang
                    $data_reimbursement = $data_reimbursement
                    ->where(function ($query) {
                        $query = $query->whereNotIn('rp.apv_kacab', ['6','0'])
                            ->orWhere('rp.status', '5');
                                
                    });
                    $p_kacab = User_m::getPKacab($id_karyawan);
                    if (count($p_kacab)==0) {//HANYA MENJABAT 1 KEPALA CABANG
                        $data_reimbursement = $data_reimbursement
                        ->where('rp.id_cabang', $id_cabang);
                    }
                    else{//BISA MENJABAT LEBIH DARI 1 KEPALA DEPARTEMEN
                        $list_id_cabang = array();
                        foreach ($p_kacab as $row_kacab) {
                            $list_id_cabang[] = $row_kacab->id_cabang;   
                        } 
                        $data_reimbursement = $data_reimbursement->whereIn('rp.id_cabang', $list_id_cabang); 
                    }
                }
                else if($level_user=='4')//direksi
                    $data_reimbursement = $data_reimbursement
                    ->where(function ($query) {
                        $query = $query->whereNotIn('rp.apv_direksi', ['6','0'])
                            ->orWhere('rp.status', '5');
                                
                    });
                else if($level_user=='7')//spv
                    $data_reimbursement = $data_reimbursement
                    ->where(function ($query) {
                        $query = $query->whereNotIn('rp.apv_spv', ['6','0'])
                            ->orWhere('rp.status', '5');
                                
                    })
                    ->where('dk.supervisi', $id_karyawan);  
            }

            $data_reimbursement = $data_reimbursement->get();
            $respon = array();
            foreach($data_reimbursement as $row){
                //TAMPILKAN AKSI?
                $aksi_footer = false;
                if($level_user=='6' && $row->apv_finance=='1') $aksi_footer = true;
                else if($level_user=='2' && $row->apv_kedep=='1') $aksi_footer = true;
                else if($level_user=='4' && $row->apv_direksi=='1') $aksi_footer = true;
                else if($level_user=='5' && $row->apv_kacab=='1') $aksi_footer = true;
                else if($level_user=='7' && $row->apv_spv=='1') $aksi_footer = true;
                else if($row->id_karyawan == $id_karyawan && $row->status=='4') $aksi_footer = true;
                //
                $row->list_item_pengajuan = self::getDataItemReimbursement($row->id_pengajuan);
                $data = array(
                    'id_pengajuan'      => $row->id_pengajuan,
                    'no_pengajuan'      => $row->no_pengajuan,
                    'tgl_pengajuan'     => DateFormat::format($row->tgl_pengajuan,'d M Y'),
                    'jml_pengajuan'     => $row->jml_pengajuan,
                    'total_pengajuan'   => $row->total_pengajuan,
                    'total_pengajuan_disetujui'   => $row->total_pengajuan_disetujui,
                    'nama_karyawan'     => $row->nama_karyawan,
                    'nama_departemen'   => $row->nama_departemen,
                    'nama_cabang'       => $row->nama_cabang,
                    'aksi_footer'       => $aksi_footer,
                    'list_approval'     => self::listApproval(
                        $row->apv_spv, $row->apv_kedep, $row->apv_kacab, $row->apv_finance, $row->apv_direksi,
                        $row->tgl_apv_spv, $row->tgl_apv_kedep, $row->tgl_apv_kacab, $row->tgl_apv_finance, $row->tgl_apv_direksi
                        )
                );
                if($konteks=='reimbursementDetail'){
                    if($row->id_karyawan!=$id_karyawan)//DETAIL REQUEST BY ATASAN
                        $data['list_item_pengajuan'] = self::getDataItemReimbursement($row->id_pengajuan, $id_karyawan, $level_user, $id_company, $id_cabang, $id_departemen); 
                    else 
                        $data['list_item_pengajuan'] = self::getDataItemReimbursement($row->id_pengajuan, $id_karyawan); 
                }
                    
                $respon[] = $data;
            }

            return $respon;
        }

        private static function getDataItemReimbursement($id_pengajuan, $id_karyawan=null, $level_user=null, $id_company=null, $id_cabang=null, $id_departemen=null){
            $data_item_reimbursement = DB::table('reimbursement_data as rd')
                ->select(
                    'rd.id as id_item','rj.jenis_plafon as jenis', 'rd.tgl_bukti as tgl_nota', 
                    'rd.keterangan', 'rd.nominal', 'rd.nominal_disetujui', 'rd.file', 'rd.id_karyawan',
                    'rd.apv_spv','rd.apv_kedep', 'rd.apv_kacab', 'rd.apv_finance', 'rd.apv_direksi',
                    'rd.tgl_apv_spv', 'rd.tgl_apv_kedep', 'rd.tgl_apv_kacab', 'rd.tgl_apv_finance', 'rd.tgl_apv_direksi',
                    'rd.id_jenis_reimbursement as id_jenis',
                    'rd.ket_rev_spv','rd.ket_rev_kedep','rd.ket_rev_kacab','rd.ket_rev_finance','rd.ket_rev_direksi',
                    'rd.ket_tolak_spv','rd.ket_tolak_kedep','rd.ket_tolak_kacab','rd.ket_tolak_finance','rd.ket_tolak_direksi',
                    'rj.max_nominal_plafon'
                    )
                ->join('data_karyawan as dk', 'dk.id_karyawan', '=', 'rd.id_karyawan')
                ->join('reimbursement_jenis as rj', 'rj.id_jenis_reimbursement', '=', 'rd.id_jenis_reimbursement')
                ->where('rd.id_pengajuan_reimbursement', $id_pengajuan);
            
            if($level_user!=null){//DETAIL REQUEST BY ATASAN
                // if($level_user=='6')//finance
                //     $data_item_reimbursement = $data_item_reimbursement
                //     ->whereNotIn('rd.apv_finance', ['0']);
                if($level_user=='2'){//kepala departemen
                    // $data_item_reimbursement = $data_item_reimbursement
                    // ->whereNotIn('rd.apv_kedep', ['0']);
                    $p_kedep = User_m::getPKedep($id_karyawan);
                    if (count($p_kedep)==0) {//HANYA MENJABAT 1 KEPALA DEPARTEMEN
                        $data_item_reimbursement = $data_item_reimbursement
                        ->where('dk.id_departemen', $id_departemen);
                    }
                    else{//BISA MENJABAT LEBIH DARI 1 KEPALA DEPARTEMEN
                        $list_id_departemen = array();
                        foreach ($p_kedep as $row_kedep) {
                            $list_id_departemen[] = $row_kedep->id_departemen;   
                        } 
                        $data_item_reimbursement = $data_item_reimbursement->whereIn('dk.id_departemen', $list_id_departemen); 
                    }
                }
                else if($level_user=='5'){//kepala cabang
                    // $data_item_reimbursement = $data_item_reimbursement
                    // ->whereNotIn('rd.apv_kacab', ['6','0']);
                    $p_kedep = User_m::getPKacab($id_karyawan);
                    if (count($p_kedep)==0) {//HANYA MENJABAT 1 KEPALA DEPARTEMEN
                        $data_item_reimbursement = $data_item_reimbursement
                        ->where('dk.id_cabang', $id_cabang);
                    }
                    else{//BISA MENJABAT LEBIH DARI 1 KEPALA DEPARTEMEN
                        $list_id_cabang = array();
                        foreach ($p_kacab as $row_kacab) {
                            $list_id_cabang[] = $row_kacab->id_cabang;   
                        } 
                        $data_item_reimbursement = $data_item_reimbursement->whereIn('dk.id_cabang', $list_id_cabang); 
                    }
                }
                // else if($level_user=='4')//direksi
                //     $data_item_reimbursement = $data_item_reimbursement
                //     ->whereNotIn('rd.apv_direksi', ['6','0']);
                else if($level_user=='7')//spv
                    $data_item_reimbursement = $data_item_reimbursement
                    // ->whereNotIn('rd.apv_spv', ['6','0'])
                    ->where('dk.supervisi', $id_karyawan);  
            }

            $data_item_reimbursement = $data_item_reimbursement->get();
            

            $respon = array();
            $jumlah = 0;
            foreach($data_item_reimbursement as $row){
                //TAMPILKAN AKSI?
                $aksi = false;
                $tombol_aksi_revisi = false;
                if($level_user=='6' && $row->apv_finance=='1') {
                    $aksi = true;
                    $jumlah++;
                }
                else if($level_user=='2' && $row->apv_kedep=='1') {
                    $aksi = true;
                    $jumlah++;
                }
                else if($level_user=='4' && $row->apv_direksi=='1'){
                    $aksi = true;
                    $jumlah++;
                }
                else if($level_user=='5' && $row->apv_kacab=='1'){
                    $aksi = true;
                    $jumlah++;
                } 
                else if($level_user=='7' && $row->apv_spv=='1'){
                    $aksi = true;
                    $jumlah++;
                } 
                else if($id_karyawan==$row->id_karyawan && 
                ($row->apv_spv=='4'||$row->apv_kedep=='4'||$row->apv_kacab=='4'||
                $row->apv_finance=='4'||$row->apv_direksi=='4')){ //KARYAWAN YG MENGAJUKAN REIMBURSE REQUEST DATA DAN REIMBURSEMENT ITEM DALAM STATUS REVISI
                    $tombol_aksi_revisi = true;
                    $jumlah++;
                }
                //
                
                $respon[] = array(
                    'id_item'       => $row->id_item,
                    'jenis'         => $row->jenis,
                    'id_jenis'      => $row->id_jenis,
                    'tgl_nota'      => DateFormat::format($row->tgl_nota,'d M Y'),
                    'tgl_nota_raw'  => $row->tgl_nota,
                    'keterangan'    => $row->keterangan??'-',
                    'nominal'       => $row->nominal,
                    'nominal_disetujui' => $row->nominal_disetujui,
                    'file'              => Uploads_c::cekFoto($row->file)?Uploads_c::retrieve_file_url($row->file, 'file'):'-',
                    'max_nominal_plafon' => $row->max_nominal_plafon,
                    'aksi'              => $aksi,
                    'tombol_aksi_revisi' => $tombol_aksi_revisi,
                    'status'            => self::statusItem($row->apv_spv, $row->apv_kedep, $row->apv_kacab, $row->apv_finance, $row->apv_direksi,$level_user),
                    'list_approval'     => self::listApproval(
                        $row->apv_spv, $row->apv_kedep, $row->apv_kacab, $row->apv_finance, $row->apv_direksi,
                        $row->tgl_apv_spv, $row->tgl_apv_kedep, $row->tgl_apv_kacab, $row->tgl_apv_finance, $row->tgl_apv_direksi, 'item',
                        $row->ket_rev_spv,$row->ket_rev_kedep,$row->ket_rev_kacab,$row->ket_rev_finance,$row->ket_rev_direksi,
                        $row->ket_tolak_spv,$row->ket_tolak_kedep,$row->ket_tolak_kacab,$row->ket_tolak_finance,$row->ket_tolak_direksi
                    )
                );
            }
            
            return array('jumlah' => $jumlah, 'data_item' => $respon);
        }

        public static function getJenisReimbursement($id_company, $id_cabang){
            return $data_jenis = DB::table('reimbursement_jenis')
                ->select('id_jenis_reimbursement as id', 'jenis_plafon as nama')
                ->where(['id_company' => $id_company, 'id_cabang' => $id_cabang])
                ->get();
        }

        public static function getDataItemTemp($id_karyawan, $id_company){
            $data_item_temp = DB::table('reimbursement_temp as rt')
                ->select('rt.id', 'rt.id_jenis_reimbursement','rj.jenis_plafon', 'nominal', 'keterangan', 'tgl_bukti', 'file')
                ->join('reimbursement_jenis as rj', 'rj.id_jenis_reimbursement', '=', 'rt.id_jenis_reimbursement')
                ->where(['rt.id_karyawan' => $id_karyawan, 'rt.id_company' => $id_company])
                ->get();
            return $data_item_temp;
        }
        

        public static function tambahItemTemp($data = array()){
            return DB::table('reimbursement_temp')->insert($data);
        }

        public static function cekMaxNominalPerItem($id){
            return DB::table('reimbursement_jenis')->select('max_nominal_plafon', 'jenis_plafon')
                ->where('id_jenis_reimbursement', $id)->first();
        }

        public static function hapusItemTemp($id){
            return DB::table('reimbursement_temp')->where('id', $id)->delete();
        }

        public static function getId($id_company = null, $table = null){
            $curent_month = date('Ym');
            $select = collect(DB::select("SELECT MAX(id) as id
                                        FROM $table
                                        WHERE id_company = '$id_company'
                                        AND SUBSTR(id,-10,6) = DATE_FORMAT(CURRENT_DATE(),'%Y%m')
                                        "))->first();
            if(!empty($select->id)){
                $maxid = substr($select->id,-4);
                
                $nextid = $id_company.$curent_month.sprintf("%04d", ($maxid+1));
            }else{
                $nextid = $id_company.$curent_month.'0001';
            }
            return $nextid;
        }

        public static function getNomorPengajuan($id_company){
            $curent_month = date('Ym');
            $select = collect(DB::select("SELECT MAX(no_pengajuan) as no_pengajuan
                                        FROM reimbursement_pengajuan
                                        WHERE id_company = '$id_company'
                                        AND SUBSTR(no_pengajuan,-10,6) = DATE_FORMAT(CURRENT_DATE(),'%Y%m')
                                        "))->first();
            if(!empty($select->id)){
                $maxid = substr($select->id,-4);
                
                $nextid = $curent_month.sprintf("%04d", ($maxid+1));
            }else{
                $nextid = $curent_month.'0001';
            }
            return $nextid;
        }

        public static function get_p_approval($level_user, $id_company, $id_cabang){
            $data = DB::table('p_approval_reimbursement')
            ->select('level_approval')
            ->where(
                [
                    'level_user' => $level_user, 
                    'id_company' => $id_company,
                    'id_cabang'  => $id_cabang
                ]
            )->first();
            if ($data!=null) {
                return $data->level_approval;
            }
            else return null; 
        }

        public static function insertPengajuan($data = array()){
            return DB::table('reimbursement_pengajuan')->insert($data);
        }

        public static function insertItemPengajuan($data = array()){
            return DB::table('reimbursement_data')->insert($data);
        }

        public static function updateStatusItemPengajuan($data = array(), $id_item){
            return DB::table('reimbursement_data')->where('id', $id_item)->update($data);
        }

        public static function updateStatusPengajuan($data = array(), $id_pengajuan){
            return DB::table('reimbursement_pengajuan')->where('id', $id_pengajuan)->update($data);
        }

        public static function cek_approval_per_column($table, $where){
            $data = DB::table($table)->where($where)
            ->count();
            return $data;
        }

        public static function getDataReimbursementById($id_pengajuan){
            return DB::table('reimbursement_pengajuan as rp')
                ->select('rp.user_spv', 'rp.id_cabang', 'rp.id_departemen', 'rp.id_karyawan')
                ->where('rp.id', $id_pengajuan)
                ->first();
        }
        
        


    }