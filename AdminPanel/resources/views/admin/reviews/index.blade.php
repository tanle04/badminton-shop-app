@extends('adminlte::page')

@section('title', 'Quản lý Đánh giá')

@section('content_header')
    <h1>Danh sách Đánh giá Sản phẩm</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Đánh giá cần duyệt/quản lý</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>Sản phẩm</th>
                        <th>Khách hàng</th>
                        <th>Rating</th>
                        <th>Nội dung</th>
                        <th>Ngày</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reviews as $review)
                    <tr>
                        <td>{{ $review->reviewID }}</td>
                        <td>{{ $review->product->productName ?? 'N/A' }}</td>
                        <td>{{ $review->customer->fullName ?? 'Khách ẩn danh' }}</td>
                        <td>
                            {{ $review->rating }} <i class="fas fa-star text-warning"></i> 
                        </td>
                        <td>{{ Str::limit($review->reviewContent, 50) }}</td>
                        <td>{{ \Carbon\Carbon::parse($review->reviewDate)->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge 
                                @if($review->status == 'published') badge-success 
                                @elseif($review->status == 'pending') badge-warning 
                                @else badge-danger @endif">
                                {{ $review->status }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.reviews.show', $review) }}" class="btn btn-primary btn-xs">Xem & Duyệt</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $reviews->links('pagination::bootstrap-4') }}
        </div>
    </div>
@stop