@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">{{ $flow->title }}</h1>
    
    <div class="prose max-w-none">
        {!! $renderedContent !!}
    </div>
</div>

{{-- Video.js CSS and JS --}}
@push('styles')
<link href="https://vjs.zencdn.net/8.6.1/video-js.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://vjs.zencdn.net/8.6.1/video.min.js"></script>
@endpush
@endsection