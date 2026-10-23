@extends('errors.layout')

@section('code', '419')
@section('title', "Your session expired")
@section('icon', 'fa-hourglass-end')
@section('message', "For your security the page timed out. Refresh it and try again.")
@section('actions')
    <a href="javascript:location.reload()" class="sc-btn"><i class="fa-solid fa-rotate-right"></i> Refresh page</a>
    <a href="{{ url('/') }}" class="sc-btn sc-btn-ghost">Back to home</a>
@endsection
