@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-start">
        @include('layouts.left-menu')
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-10 col-xl-10 col-xxl-10">
            <div class="row pt-2">
                <div class="col ps-4">
                    <h1 class="display-6 mb-3">
                        <i class="bi bi-person-lines-fill"></i> Add Teacher
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Add Teacher</li>
                        </ol>
                    </nav>

                    @include('session-messages')

                    <div class="mb-4">
                        <form class="row g-3" action="{{route('school.teacher.create')}}" method="POST">
                            @csrf
                            <div class="col-md-3">
                                <label for="inputFirstName" class="form-label">First Name<sup><i class="bi bi-asterisk text-primary"></i></sup></label>
                                <input type="text" class="form-control" id="inputFirstName" name="first_name" placeholder="First Name" required value="{{old('first_name')}}">
                            </div>
                            <div class="col-md-3">
                                <label for="inputLastName" class="form-label">Last Name<sup><i class="bi bi-asterisk text-primary"></i></sup></label>
                                <input type="text" class="form-control" id="inputLastName" name="last_name" placeholder="Last Name" required value="{{old('last_name')}}">
                            </div>
                            <div class="col-md-6">
                                <label for="inputEmail" class="form-label">Email<sup><i class="bi bi-asterisk text-primary"></i></sup></label>
                                <input type="email" class="form-control" id="inputEmail" name="email" required value="{{old('email')}}">
                            </div>
                            <div class="col-md-6">
                                <label for="inputPassword" class="form-label">Password<sup><i class="bi bi-asterisk text-primary"></i></sup></label>
                                <input type="password" class="form-control" id="inputPassword" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="formFile" class="form-label">Photo</label>
                                <input class="form-control" type="file" id="formFile" onchange="previewFile()">
                                <div id="previewPhoto"></div>
                                <input type="hidden" id="photoHiddenInput" name="photo" value="">
                            </div>
                            <div class="col-md-12">
                                <label for="inputAddress" class="form-label">Address<sup><i class="bi bi-asterisk text-primary"></i></sup></label>
                                <input type="text" class="form-control" id="inputAddress" name="address" placeholder="Putalisadak, Kathmandu" required value="{{old('address')}}">
                            </div>
                            <div class="col-md-12">
                                <label for="inputAddress2" class="form-label">Address 2</label>
                                <input type="text" class="form-control" id="inputAddress2" name="address2" placeholder="Ward 10, near City Center" value="{{old('address2')}}">
                            </div>
                            <div class="col-md-4">
                                <label for="inputCity" class="form-label">City<sup><i class="bi bi-asterisk text-primary"></i></sup></label>
                                <input list="city-options" type="text" class="form-control" id="inputCity" name="city" placeholder="Kathmandu" required value="{{old('city', 'Kathmandu')}}">
                            </div>
                            <div class="col-md-4">
                                <label for="inputZip" class="form-label">Zip<sup><i class="bi bi-asterisk text-primary"></i></sup></label>
                                <input type="text" class="form-control" id="inputZip" name="zip" required value="{{old('zip', '44600')}}">
                            </div>
                            <div class="col-md-4">
                                <label for="inputPhone" class="form-label">Phone<sup><i class="bi bi-asterisk text-primary"></i></sup></label>
                                <input type="text" class="form-control" id="inputPhone" name="phone" placeholder="+977 98XXXXXXXX" required value="{{old('phone', '+977 9812345678')}}">
                            </div>
                            <div class="col-md-4">
                                <label for="inputGender" class="form-label">Gender<sup><i class="bi bi-asterisk text-primary"></i></sup></label>
                                <select id="inputGender" class="form-select" name="gender" required>
                                    <option value="Male" {{old('gender') == 'male' ? 'selected' : ''}}>Male</option>
                                    <option value="Female" {{old('gender') == 'female' ? 'selected' : ''}}>Female</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="inputNationality" class="form-label">Nationality<sup><i class="bi bi-asterisk text-primary"></i></sup></label>
                                <input list="nationality-options" type="text" class="form-control" id="inputNationality" name="nationality" placeholder="e.g. Nepali, ..." required value="{{old('nationality', 'Nepali')}}">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-person-plus"></i> Add</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @include('layouts.footer')
        </div>
    </div>
</div>

@include('components.photos.photo-input')
<datalist id="city-options">
    <option value="Kathmandu">
    <option value="Lalitpur">
    <option value="Bhaktapur">
    <option value="Pokhara">
    <option value="Biratnagar">
    <option value="Other">
</datalist>
<datalist id="nationality-options">
    <option value="Nepali">
    <option value="Indian">
    <option value="Other">
</datalist>
@endsection
