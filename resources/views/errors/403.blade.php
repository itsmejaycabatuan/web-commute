@extends('errors.layout')

@section('code', '403')
@section('title', "You don't have access to this page")
@section('icon', 'fa-lock')
@section('message', $exception->getMessage() ?: "Your account doesn't have permission to open this page. If you think that's a mistake, contact an administrator.")

