<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use App\Http\Helpers\DateFormat;
    use App\Http\Controllers\Uploads_c;

    class Pengumuman_m extends Model{
        public function __construct($basePath = null)
        {
            date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

            $this->basePath = $basePath;
            $this->bootstrapContainer();
            $this->registerErrorHandling();
        }

        public static function getdataPengumuman($data = array()){
            $id_pengumuman      = $data['id_pengumuman'];
            $id_company         = $data['id_company'];
            $id_departemen      = $data['id_departemen'];
            $id_cabang          = $data['id_cabang'];
            $id_karyawan        = $data['id_karyawan'];
            $id_company         = $data['id_company'];
            $keywords           = $data['keywords'];
            $limit              = $data['limit'];
            $offset             = $data['offset'];
            $range_tanggal_mulai     = $data['range_tanggal_mulai'];
            $range_tanggal_selesai   = $data['range_tanggal_selesai'];
            $where = array();
            if($id_company!=null) $where[] = array('id_company', '=', $id_company);
            if($id_pengumuman!=null) $where[] = array('id_pengumuman', '=', $id_pengumuman);
            if($keywords!=null) $where[] = array('judul', 'like', '%'.$keywords.'%');
            $data_pengumuman = DB::table('data_pengumuman')
            ->select('id_pengumuman', 'target', 'target_id', 
                'judul', 'content', 'date_created')
            ->where($where)
            ->where(function($query) use ($id_departemen, $id_karyawan, $id_cabang)
                {
                    $query->orWhere(function($query)
                            {
                                $query->where('target', '=', '1')
                                ->where('target_id', '=', '');
                            })
                    ->orWhere(function($query) use ($id_cabang)
                    {
                        $query->where('target', '=', '2')
                        ->where('target_id', 'like', '%'.$id_cabang.'%');
                    })
                    ->orWhere(function($query) use ($id_departemen, $id_cabang)
                            {
                                $query->where('target', '=', '3')
                                ->where('target_id', 'like', '%'.$id_departemen.'%')
                                ->where('id_cabang', $id_cabang);
                            })
                    ->orWhere(function($query) use ($id_karyawan)
                            {
                                $query->where('target', '=', '3')
                                ->where('target_id', 'like', '%'.$id_karyawan.'%');
                            })
					->orWhere(function($query) use ($id_karyawan)
					{
						$query->where('target', '=', '4')
						->where('target_id', 'like', '%'.$id_karyawan.'%');
						// ->whereRaw('FIND_IN_SET('.$id_karyawan.',target_id)');
					});
                })
            ->orderBy('date_created', 'DESC')->limit($limit)->offset($offset);
            if($range_tanggal_mulai!=null && $range_tanggal_selesai!=null) {
                $data_pengumuman = $data_pengumuman->whereRaw("DATE_FORMAT(date_created, '%Y-%m-%d') >= '$range_tanggal_mulai'");
                $data_pengumuman = $data_pengumuman->whereRaw("DATE_FORMAT(date_created, '%Y-%m-%d') <= '$range_tanggal_selesai'");
            }
            $data_pengumuman = $data_pengumuman->get();
            foreach ($data_pengumuman as $row) {
                $row->tanggal_created = DateFormat::format($row->date_created,'N d M Y');
                $row->waktu_created = date_format(date_create($row->date_created), "H:i");
                $row->file = '-';
                $row->ext = '';
            }
            if (count($data_pengumuman)>0) 
                $response = array('success' => true, 'message' => 'data pengumuman berhasil ditemukan', 'data' => $data_pengumuman);
            else 
                $response = array('success'=>false,'message'=> 'data pengumuman gagal ditemukan');
            return response()->json($response,200);
        }
    }

    