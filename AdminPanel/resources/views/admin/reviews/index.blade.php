@extends('adminlte::page')

@section('title', 'Quản lý Đánh giá')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-star"></i> Quản lý Đánh giá
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Đánh giá</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row mb-3">
        @php
            $stats = [
                'total' => $reviews->total(),
                'pending' => \App\Models\Review::where('status', 'pending')->count(),
                'published' => \App\Models\Review::where('status', 'published')->count(),
                'hidden' => \App\Models\Review::where('status', 'hidden')->count(),
                'avg_rating' => \App\Models\Review::where('status', 'published')->avg('rating'),
            ];
        @endphp
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total'] }}</h3>
                    <p>Tổng đánh giá</p>
                </div>
                <div class="icon">
                    <i class="fas fa-comments"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['pending'] }}</h3>
                    <p>Chờ duyệt</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['published'] }}</h3>
                    <p>Đã xuất bản</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ number_format($stats['avg_rating'], 1) }}</h3>
                    <p>Đánh giá TB</p>
                </div>
                <div class="icon">
                    <i class="fas fa-star"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i> Danh sách Đánh giá
            </h3>
            <div class="card-tools">
                {{-- Search Box --}}
                <form action="{{ route('admin.reviews.index') }}" method="GET" class="form-inline mr-2" style="display: inline-block;">
                    <div class="input-group input-group-sm">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Tìm sản phẩm, KH..." 
                               value="{{ request('search') }}"
                               style="width: 200px;">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <input type="hidden" name="rating" value="{{ request('rating') }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request('search'))
                            <a href="{{ route('admin.reviews.index', request()->except('search', 'page')) }}" 
                               class="btn btn-default">
                                <i class="fas fa-times"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                </form>
                
                <button type="button" class="btn btn-tool" id="btn-refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        
        {{-- Filter Tabs --}}
        <div class="card-body pb-0">
            <ul class="nav nav-pills mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ (request('status') == 'all' || !request('status')) ? 'active' : '' }}" 
                       href="{{ route('admin.reviews.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}">
                        <i class="fas fa-list"></i> Tất cả 
                        <span class="badge badge-light">{{ $stats['total'] }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'pending' ? 'active' : '' }}" 
                       href="{{ route('admin.reviews.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}">
                        <i class="fas fa-clock"></i> Pending 
                        <span class="badge badge-warning">{{ $stats['pending'] }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'published' ? 'active' : '' }}" 
                       href="{{ route('admin.reviews.index', array_merge(request()->except('status', 'page'), ['status' => 'published'])) }}">
                        <i class="fas fa-check-circle"></i> Published 
                        <span class="badge badge-success">{{ $stats['published'] }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'hidden' ? 'active' : '' }}" 
                       href="{{ route('admin.reviews.index', array_merge(request()->except('status', 'page'), ['status' => 'hidden'])) }}">
                        <i class="fas fa-eye-slash"></i> Hidden 
                        <span class="badge badge-danger">{{ $stats['hidden'] }}</span>
                    </a>
                </li>
            </ul>
            
            {{-- Rating Filter --}}
            <div class="btn-group btn-group-sm mb-3">
                <a href="{{ route('admin.reviews.index', array_merge(request()->except('rating', 'page'), ['rating' => 'all'])) }}" 
                   class="btn {{ (request('rating') == 'all' || !request('rating')) ? 'btn-secondary' : 'btn-default' }}">
                    <i class="fas fa-star"></i> Tất cả Rating
                </a>
                <a href="{{ route('admin.reviews.index', array_merge(request()->except('rating', 'page'), ['rating' => '5'])) }}" 
                   class="btn {{ request('rating') == '5' ? 'btn-warning' : 'btn-default' }}">
                    5 <i class="fas fa-star"></i>
                </a>
                <a href="{{ route('admin.reviews.index', array_merge(request()->except('rating', 'page'), ['rating' => '4'])) }}" 
                   class="btn {{ request('rating') == '4' ? 'btn-warning' : 'btn-default' }}">
                    4 <i class="fas fa-star"></i>
                </a>
                <a href="{{ route('admin.reviews.index', array_merge(request()->except('rating', 'page'), ['rating' => '3'])) }}" 
                   class="btn {{ request('rating') == '3' ? 'btn-warning' : 'btn-default' }}">
                    3 <i class="fas fa-star"></i>
                </a>
                <a href="{{ route('admin.reviews.index', array_merge(request()->except('rating', 'page'), ['rating' => 'low'])) }}" 
                   class="btn {{ request('rating') == 'low' ? 'btn-danger' : 'btn-default' }}">
                    ≤2 <i class="fas fa-star"></i>
                </a>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 60px" class="text-center">ID</th>
                            <th style="width: 25%">Sản phẩm</th>
                            <th style="width: 15%">Khách hàng</th>
                            <th style="width: 10%" class="text-center">Rating</th>
                            <th style="width: 25%">Nội dung</th>
                            <th style="width: 10%">Ngày</th>
                            <th style="width: 10%" class="text-center">Trạng thái</th>
                            <th style="width: 100px" class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reviews as $review)
                        <tr>
                            <td class="text-center">
                                <strong class="text-primary">#{{ $review->reviewID }}</strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @php
                                        $mainImage = $review->product->images->where('imageType', 'main')->first();
                                    @endphp
                                    @if($mainImage)
                                        <img src="{{ asset('storage/' . $mainImage->imageUrl) }}" 
                                             alt="Product" 
                                             class="img-thumbnail mr-2"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                    @else
                                        <div class="mr-2" style="width: 40px; height: 40px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <strong>{{ $review->product->productName ?? 'N/A' }}</strong>
                                        @if($review->media->count() > 0)
                                            <br><small class="text-muted">
                                                <i class="fas fa-images"></i> {{ $review->media->count() }} media
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <i class="fas fa-user"></i>
                                    <strong>{{ $review->customer->fullName ?? 'Khách ẩn danh' }}</strong>
                                </div>
                                @if($review->customer && $review->customer->email)
                                    <small class="text-muted">{{ $review->customer->email }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <div>
                                    <span class="badge badge-warning" style="font-size: 0.95em;">
                                        {{ $review->rating }} <i class="fas fa-star"></i>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }}" style="font-size: 0.7em;"></i>
                                    @endfor
                                </small>
                            </td>
                            <td>
                                @if($review->reviewContent)
                                    <div style="max-height: 60px; overflow: hidden;">
                                        {{ Str::limit($review->reviewContent, 100) }}
                                    </div>
                                @else
                                    <small class="text-muted">(Không có nội dung)</small>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <i class="far fa-calendar-alt"></i>
                                    {{ \Carbon\Carbon::parse($review->reviewDate)->format('d/m/Y') }}
                                </div>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($review->reviewDate)->format('H:i') }}
                                </small>
                            </td>
                            <td class="text-center">
                                @php
                                    $statusColors = [
                                        'published' => 'success',
                                        'pending' => 'warning',
                                        'hidden' => 'danger'
                                    ];
                                    $color = $statusColors[$review->status] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $color }}">
                                    {{ ucfirst($review->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.reviews.show', $review) }}" 
                                   class="btn btn-info btn-sm"
                                   title="Xem & Duyệt">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Không có đánh giá nào</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($reviews->hasPages())
        <div class="card-footer clearfix">
            <div class="float-left">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Hiển thị {{ $reviews->firstItem() }} - {{ $reviews->lastItem() }} 
                    trong tổng số <strong>{{ $reviews->total() }}</strong> đánh giá
                </small>
            </div>
            <div class="float-right">
                {{ $reviews->appends(request()->except('page'))->links('pagination::bootstrap-4') }}
            </div>
        </div>
        @endif
    </div>
@stop

@section('css')
<style>
    .small-box h3 {
        font-size: 2rem;
    }
    
    .card-outline {
        border-top: 3px solid #007bff;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa !important;
    }
    
    .badge {
        font-size: 0.85em;
        padding: 0.4em 0.6em;
    }
    
    .img-thumbnail {
        border-radius: 4px;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    console.log('✅ Reviews index loaded');
    
    // Refresh button
    $('#btn-refresh').on('click', function() {
        const $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        setTimeout(function() {
            location.reload();
        }, 500);
    });
    
    // Auto dismiss alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
@stop