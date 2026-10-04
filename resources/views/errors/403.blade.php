@extends('errors.layout')

@section('code', '403')
@section('title', 'Access restricted')
{{-- The reason differs by cause (administrators only, a feature switched off, a read-only view), so show it when there is one. --}}
@section('message', filled($exception->getMessage()) && $exception->getMessage() !== 'This action is unauthorized.'
    ? $exception->getMessage()
    : 'Your account does not have permission to view this page. Contact your farm administrator if you need access.')
@section('icon')
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
@endsection
