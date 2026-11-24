@extends('errors.layout')

@section('code', '419')
@section('title', 'Sesi Anda Telah Berakhir')
@section('message', 'Halaman terlalu lama dibiarkan terbuka sehingga token keamanan kedaluwarsa. Muat ulang halaman lalu kirim ulang formulir Anda.')
@section('action')
    <a class="btn" href="{{ url()->current() }}">Muat Ulang Halaman</a>
@stop
