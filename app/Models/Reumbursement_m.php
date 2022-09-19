<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use App\Http\Helpers\Convertion;
    use URL;
    use App\Http\Helpers\DateFormat;

    class Reumbursement_m extends Model{


        public static function getDataReumbursement($data = array()){
            $id_karyawan = Convertion::getIdKaryawan($data['token_fcm']);
            if($id_karyawan != NULL){
                $data_reumburse = DB::table('reimbursement_pengajuan as rp')
                                    ->select('rp.id', 'rp.no_pengajuan', 'rp.tgl_pengajuan', 'rp.id_karyawan', 
                                             'dk.nama_lengkap as nama_karyawan','dk.gelar_depan', 'dk.gelar_belakang',
                                             )
                                    ->join('data_karyawan AS dk', 'dk.id_karyawan','=','rp.id_karyawan')
                                    ->where('rp.id_karyawan', $id_karyawan)->get();
                $data = [];
                foreach($data_reumburse as $rows){
                    // $reimburse_data_per_item = $this->db->select('count(id) as jml,sum(nominal) as nominal')->from('reimbursement_data')->where('id_pengajuan_reimbursement', $idx['id'])->get()->row();
                    $reimburse_data_per_item = DB::table('reimbursement_data')
                                                 ->select(DB::raw('count(id) as jml, sum(nominal) as nominal'))
                                                 ->where('id_pengajuan_reimbursement', $rows->id)
                                                 ->first();
                    $jml = $reimburse_data_per_item->jml;
                    // echo $jml;exit;
                    $data[] = array(
                        'id_karyawan'   => $rows->id_karyawan,
                        'nama_karyawan' => Convertion::namaDanGelar($rows->gelar_depan??'', $rows->nama_karyawan??'', $rows->gelar_belakang??''),
                        'id'            => $rows->id??'',
                        'tgl_pengajuan' => DateFormat::format($rows->tgl_pengajuan,'N d M Y'),
                        'no_pengajuan'  => $rows->no_pengajuan??'',
                        'jumlah_pengajuan'  => strval($jml??''),
                        'nominal'   => "Rp. ".number_format($reimburse_data_per_item->nominal??0,0,",","."),
                        'status'   => "Approved hod",
                        'flag'   => "1",
                        
                    );
                }
                $response = array('success'=>true, 'message' => 'berhasil ambil data reimbursement',
                'data_reimbursment'=>$data);
            }else{
                $response = array('success'=>false,'message'=> 'data karyawan tidak ditemukan');
            }
            return response()->json($response,200);
        }


    }