@extends('adminlte::page')

@section('title', 'Quản lý Sản phẩm')

@section('content_header')
    <h1>Danh sách Sản phẩm</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sản phẩm Cầu lông</h3>
            <div class="card-tools">
                <div class="btn-group mr-2">
                    {{-- Giả định biến $status được truyền từ ProductController@index --}}
                    <a href="{{ route('admin.products.index', ['status' => 'active']) }}" class="btn btn-sm {{ $status == 'active' ? 'btn-primary' : 'btn-default' }}">Đang bán</a>
                    <a href="{{ route('admin.products.index', ['status' => 'inactive']) }}" class="btn btn-sm {{ $status == 'inactive' ? 'btn-warning' : 'btn-default' }}">Đã đóng băng</a>
                    <a href="{{ route('admin.products.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status == 'all' ? 'btn-secondary' : 'btn-default' }}">Tất cả</a>
                </div>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Thêm Sản phẩm
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>Ảnh chính</th>
                        <th>Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Thương hiệu</th>
                        <th>Tồn kho tổng</th>
                        <th>Giá (Min/Max)</th>
                        <th style="width: 150px">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                    <tr class="{{ $product->is_active ? '' : 'bg-light text-muted' }}">
                        <td>{{ $product->productID }}</td>
                        <td>
                            @php
                            $mainImage = $product->images->where('imageType', 'main')->first() ?? $product->images->first();
                            @endphp
                            @if ($mainImage)
                            <img src="{{ asset('storage/' . $mainImage->imageUrl) }}" alt="{{ $product->productName }}" style="width: 50px; height: 50px; object-fit: cover;">
                            @else
                            <i class="far fa-image"></i>
                            @endif
                        </td>
                        <td>
                            {{ $product->productName }}
                            @if (!$product->is_active)
                                <span class="badge badge-warning">Đóng Băng</span>
                            @endif
                        </td>
                        <td>{{ $product->category->categoryName ?? 'N/A' }}</td>
                        <td>{{ $product->brand->brandName ?? 'N/A' }}</td>
                        <td>
                            {{ $product->variants->sum('stock') }} (Đang giữ: {{ $product->variants->sum('reservedStock') }})
                        </td>
                        <td>
                            @php
                            $minPrice = $product->variants->min('price');
                            $maxPrice = $product->variants->max('price');
                            @endphp
                            @if ($minPrice == $maxPrice)
                            {{ number_format($minPrice, 0, ',', '.') }} VNĐ
                            @else
                            {{ number_format($minPrice, 0, ',', '.') }} - {{ number_format($maxPrice, 0, ',', '.') }} VNĐ
                            @endif
                        </td>
                        
                        <td>
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-info btn-xs">Sửa</a>
                            
                            @if ($product->is_active)
                                {{-- Nút ĐÓNG BĂNG (Sử dụng destroy route nhưng đổi logic trong Controller) --}}
                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-warning btn-xs" onclick="return confirm('Xác nhận ĐÓNG BĂNG sản phẩm này? Sản phẩm sẽ bị ẩn khỏi trang bán hàng.')">Đóng Băng</button>
                                </form>
                            @else
                                {{-- Nút KÍCH HOẠT LẠI (Tạo route mới hoặc sử dụng update) --}}
                                <form action="{{ route('admin.products.update', $product) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PUT')
                                    {{-- Thêm trường ẩn để báo cho Controller biết đây là yêu cầu kích hoạt lại --}}
                                    <input type="hidden" name="action_reactivate" value="1">
                                    <button type="submit" class="btn btn-success btn-xs" onclick="return confirm('Xác nhận KÍCH HOẠT LẠI sản phẩm?')">Kích hoạt lại</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $products->links('pagination::bootstrap-4') }}
        </div>
    </div>
@stop