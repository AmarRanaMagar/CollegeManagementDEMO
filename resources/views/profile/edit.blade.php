@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-start">
        @include('layouts.left-menu')
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-10 col-xl-10 col-xxl-10">
            <div class="row pt-2">
                <div class="col ps-4">
                    <h1 class="display-6 mb-3"><i class="bi bi-person-circle"></i> My Profile</h1>
                    @include('session-messages')
                    <div class="col-md-6 border p-3 shadow-sm">
                        <p class="mb-3">
                            <strong>{{$user->first_name}} {{$user->last_name}}</strong><br>
                            <span class="text-muted">{{$user->email}}</span>
                        </p>
                        @if ($user->photo)
                            <img src="{{asset('/storage'.$user->photo)}}" class="rounded mb-3" alt="Current profile photo" height="120">
                        @endif
                        <form action="{{route('profile.update')}}" method="POST">
                            @csrf
                            <label for="formFile" class="form-label">Profile photo</label>
                            <input class="form-control" type="file" id="formFile" accept=".jpg,.jpeg,.png" onchange="previewFile()">
                            <div id="previewPhoto" class="mt-2"></div>
                            <input type="hidden" id="photoHiddenInput" name="photo" value="">
                            <button type="submit" class="mt-3 btn btn-outline-primary"><i class="bi bi-upload"></i> Save photo</button>
                        </form>
                    </div>
                </div>
            </div>
            @include('layouts.footer')
        </div>
    </div>
</div>
@include('components.photos.photo-input')
@endsection
