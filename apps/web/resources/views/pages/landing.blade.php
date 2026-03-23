@extends('layouts.app')

@php
    $phoneDisplay = '+38 (066) 781-07-07';
    $phoneHref = 'tel:+380667810707';
@endphp

@section('content')
    @include('sections.hero')
    @include('sections.benefits')
    @include('sections.works')
    @include('sections.cta')
    @include('sections.lead-form')
    @include('sections.footer')
@endsection
