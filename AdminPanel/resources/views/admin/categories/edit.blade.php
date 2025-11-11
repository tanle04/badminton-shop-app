@extends('adminlte::page')

@section('title', 'Chỉnh sửa Danh mục')

@section('content_header')
    <h1>Chỉnh sửa Danh mục: {{ $category->categoryName }}</h1>
@stop

@section('content')
<div class="row">
    {{-- Form chỉnh sửa thông tin cơ bản --}}
    <div class="col-md-6">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">Thông tin Danh mục</h3>
            </div>
            <form action="{{ route('admin.categories.update', $category->categoryID) }}" method="POST">
                @csrf
                @method('PUT') 

                <div class="card-body">
                    {{-- Tên Danh mục --}}
                    <div class="form-group">
                        <label for="categoryName">Tên Danh mục</label>
                        <input type="text" name="categoryName" class="form-control @error('categoryName') is-invalid @enderror" id="categoryName" placeholder="Nhập tên danh mục" value="{{ old('categoryName', $category->categoryName) }}" required>
                        
                        @error('categoryName')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- ⭐ THÊM MỚI: Trạng thái (is_active) --}}
                    <div class="form-group">
                        <label for="is_active">Trạng thái</label>
                        <select name="is_active" id="is_active" class="form-control @error('is_active') is-invalid @enderror" required>
                            <option value="1" {{ old('is_active', $category->is_active) == 1 ? 'selected' : '' }}>Đang hiển thị</option>
                            <option value="0" {{ old('is_active', $category->is_active) == 0 ? 'selected' : '' }}>Đã ẩn</option>
                        </select>
                        @error('is_active')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">Cập nhật</button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-default">Hủy</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ⭐ THÊM MỚI: Hiển thị các thuộc tính đã gán (theo yêu cầu của bạn) --}}
    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Các Thuộc tính đã gán</h3>
                
                {{-- ⭐ ĐÃ XÓA NÚT CẤU HÌNH --}}
                
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tên Thuộc tính</th>
                            <th>Các Giá trị được phép</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignedAttributes as $attribute)
                            <tr>
                                <td><strong>{{ $attribute->attributeName }}</strong></td>
                                <td>
                                    @php
                                        // Lấy các giá trị được phép dựa trên range (nếu có)
                                        $allowedValues = $category->getAttributeValues($attribute->attributeID);
                                    @endphp

                                    @if($allowedValues->isEmpty())
                                        <span class="badge badge-success">Tất cả giá trị</span>
                                    @else
                                        @foreach($allowedValues as $value)
                                            <span class="badge badge-secondary">{{ $value->valueName }}</span>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">
                                    <p class="mb-2 mt-2">Chưa có thuộc tính nào được gán.</p>
                                    
                                    {{-- ⭐ ĐÃ XÓA NÚT GÁN THUỘC TÍNH --}}

                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
