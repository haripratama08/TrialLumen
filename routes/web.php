 <?php
use App\Http\Helpers\TimezoneMapper;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// $router->get('/tester', function () use ($router) {
//     $res['success'] = true;
//     $res['result'] = "Hello there welcome to web api using lumen tutorial!";
//     return response($res);
// });

// BEGIN SPLASH SCREEN
$router->get('/cek-version', 'VersionApp_c@cek_version_apps');
$router->get('/php-info', 'VersionApp_c@cek_php');
// END SPLASH SCREEN

// BEGIN USER
$router->group(['prefix'=>'users'], function () use ($router) {
    $router->get('active', 'User_c@active');
    $router->get('login', 'User_c@login');
    $router->get('logout', 'User_c@logout');
    $router->patch('reset-password', 'User_c@resetPassword');
    $router->get('cek-password', 'User_c@cekPassword');
    $router->post('login-payroll', 'User_c@loginPayroll');
    $router->get('detail', 'User_c@detail');
    $router->get('logo', 'User_c@getLogoPerusahaan');
    $router->get('cek-user', 'User_c@getUserTerkini');
    $router->patch('ubah-password/{token_fcm}/{password_lama}/{password_baru}', 'User_c@ubahPassword');
    $router->patch('ubah-profil/{token_fcm}/{no_telp}', 'User_c@ubahProfil');
    $router->get('komponen-paket', 'User_c@getKomponenPaket');
    $router->post('upload-foto', 'User_c@uploadFotoProfil');
    $router->post('face-registration', 'User_c@face_registration');
});
// END USER

// BEGIN DASHBOARD
$router->group(['prefix'=>'dashboard'], function () use ($router) {
    $router->get('/jam_kerja', 'Jam_kerja_c@getJamKerja');
    $router->get('/rekap-absensi', 'Absensi_c@getRekapAbsensi');
});
// END DASHBOARD

// BEGIN ABSENSI
$router->group(['prefix'=>'absensi'], function () use ($router) {
    $router->get('lokasi', 'Absensi_c@lokasiAbsen');
    $router->get('jam-kerja', 'Absensi_c@jam_kerja');
    $router->get('hari-ini', 'Absensi_c@DataAbsensiHariIni');
    $router->get('cek_absen', 'Absensi_c@cekAbsen');
    $router->post('tambah', 'Absensi_c@add_absensi');
    $router->get('data', 'Absensi_c@dataAbsensi');   
    $router->get('pilih-shift', 'Absensi_c@getBebasShift');  
});
// END ABSENSI

// BEGIN IZIN
$router->group(['prefix'=>'izin'], function () use ($router) {
    $router->post('pengajuan', 'Izin_c@pengajuan');
    /*$router->patch('batalkan/{id_izin}', 'Izin_c@batalkan');*/
    $router->patch('tolak/{id_izin}/{id_karyawan}', 'Izin_c@tolak');
    $router->patch('hrd_mewakili_tolak/{id_izin}/{id_karyawan}', 'Izin_c@hrd_mewakili_tolak');
    $router->patch('setujui/{id_izin}/{id_karyawan}/{id_company}', 'Izin_c@setujui');
    $router->patch('hrd_mewakili_setujui/{id_izin}/{id_karyawan}/{id_company}', 'Izin_c@hrd_mewakili_setujui');
    $router->post('karyawan', 'Izin_c@dataIzinByKaryawan');
    $router->post('filter', 'Izin_c@dataIzinByDepartemen');
    $router->get('data', 'Izin_c@getDataIzin');
    $router->get('jenis', 'Izin_c@getJenisIzin');
    $router->patch('batalkan/{id_izin}/{id_karyawan}', 'Izin_c@batalkan');
    $router->get('sisa_cuti', 'Izin_c@cekSisaCutiKaryawan');
    $router->get('jumlah_izin', 'Izin_c@cekJumlahIzinKaryawan');
});
// END IZIN

// BEGIN LEMBUR
$router->group(['prefix'=>'lembur'], function () use ($router) {
    $router->post('/data-lembur-by-karyawan', 'Lembur_c@dataLemburByKaryawan');
    $router->post('/data-lembur-by-departemen', 'Lembur_c@dataLemburByDepartemen');
    $router->patch('/setujui/{id_lemmulai}/{id_karyawan}/{id_company}', 'Lembur_c@setujui');
    $router->patch('/hrd_mewakili_setujui/{id_lemmulai}/{id_karyawan}/{id_company}', 'Lembur_c@hrd_mewakili_setujui');
    $router->patch('/tolak/{id_lemmulai}/{id_karyawan}', 'Lembur_c@tolak');
    $router->patch('/hrd_mewakili_tolak/{id_lemmulai}/{id_karyawan}', 'Lembur_c@hrd_mewakili_tolak');
    $router->patch('/batalkan/{id_lemmulai}/{id_karyawan}', 'Lembur_c@batalkan');
    $router->get('/data', 'Lembur_c@getDataLembur');
    $router->get('/rekap', 'Lembur_c@getDataRekapLembur');
    $router->post('/pengajuan', 'Lembur_c@add_lembur');
    $router->get('/cek_absen', 'Lembur_c@cekAbsen');
     $router->patch('/test/{id_lemmulai}', 'Lembur_c@test');


});
// END LEMBUR

// BEGIN AKTIVITAS
$router->group(['prefix'=>'aktivitas'], function () use ($router) {
    $router->post('tambah', 'Aktivitas_c@addAktivitas');
    $router->get('data', 'Aktivitas_c@getDataAktivitas');
    $router->get('lokasi', 'Aktivitas_c@cekLokasi');
});
// END AKTIVITAS

// BEGIN NOTIFIKASI
$router->group(['prefix'=>'notifikasi'], function () use ($router) {
    $router->get('send', 'Notifikasi_c@send_fcm_web');
    $router->get('kontrak', 'Notifikasi_c@reminder_kontrak');
});
// END NOTIFIKASI

// BEGIN DATA BERITA
$router->get('/berita/data', 'Berita_c@getDataBerita');
// END DATA BERITA

// BEGIN DATA PENGUMUMAN
$router->get('/pengumuman/data', 'Pengumuman_c@getDataPengumuman');
// END DATA PENGUMUMAN

// BEGIN DATA SUMMARY
$router->get('/summary/data', 'Summary_c@getDataSummary');
// END DATA SUMMARY

// BEGIN DATA SHIFT
$router->get('/shift/data', 'Shift_c@getDataShift');
$router->get('/shift/teman', 'Shift_c@getDataShiftTeman');
$router->get('/shift/karyawan', 'Shift_c@getDataShiftKaryawan');
// END DATA SHIFT

// BEGIN DATA GAJI
$router->get('/gaji/data', 'Gaji_c@getDataGaji');
// END DATA GAJI


// BEGIN REIMBURSEMENT
$router->group(['prefix'=>'reimbursement'], function () use ($router) {
    $router->get('/data', 'Reimbursement_c@getDataReimbursement');
    $router->get('/jenis', 'Reimbursement_c@getJenisReimbursement');
    $router->get('/data-item', 'Reimbursement_c@getDataItemTemp');
    $router->post('/tambah-item', 'Reimbursement_c@tambahItemTemp');
    $router->post('/pengajuan', 'Reimbursement_c@pengajuanReimbursement');
    $router->post('/update-status', 'Reimbursement_c@updateStatusPengajuan');
    $router->post('/revisi', 'Reimbursement_c@updateRevisiReimbursement');
    $router->patch('/hapus-item/{token_fcm}/{id}', 'Reimbursement_c@hapusItemTemp');
});
// END REIMBURSEMENT

$router->post('/get-timezone', function() use ($router){
    return TimezoneMapper::latLngToTimezoneString(-8.335106, 115.208115);
});
// END DASHBOARD

// BEGIN DASHBOARD
$router->group(['prefix'=>'dashboard'], function () use ($router) {
    $router->get('data', 'Dashboard_c@getDataDashboard');
});
// END DASHBOARD

// BEGIN DATA DROPDOWN
$router->get('/dropdown/cabang', 'Dropdown_c@get_data_cabang');
$router->get('/dropdown/departemen', 'Dropdown_c@get_data_departemen');
$router->get('/dropdown/pegawai', 'Dropdown_c@get_data_pegawai');
$router->get('/dropdown/status', 'Dropdown_c@get_data_status');
$router->get('/dropdown/chip', 'Dropdown_c@get_chip');
// END DATA DROPDOWN

// GET DATA FACE RECOGNITION
$router->get('/face/regis-info', 'FaceRecognition_c@getDataPeringatanRegistrasi');
// END DATA FACE RECOGNITION

// BEGIN SOS
$router->group(['prefix'=>'sos'], function () use ($router) {
    $router->post('add', 'Sos_c@add_sos');
    $router->get('data', 'Sos_c@getDataSos');
});
// END SOS


// BEGIN 
$router->group(['prefix'=>'patroli'], function () use ($router) {
    $router->post('add', 'Patroli_c@add_patroli');
    $router->get('data', 'Patroli_c@getDataPatroli');
    $router->get('list_kartu', 'Patroli_c@list_kartu');
});
// END SOS

