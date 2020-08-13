{{--
  Template Name: Custom Main Page
--}}
@extends('layouts.app')

@section('content')
  @include('components.search-header')
  @include('components.catering')
  @include('components.establishments')
@endsection



