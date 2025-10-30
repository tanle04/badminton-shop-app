@extends('adminlte::page')

@section('title', 'Quản lý Sản phẩm')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-box"></i> Quản lý Sản phẩm
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Sản phẩm</li>
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
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $products->total() }}</h3>
                    <p>Tổng sản phẩm</p>
                </div>
                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <a href="{{ route('admin.products.index', ['status' => 'all']) }}" class="small-box-footer">
                    Xem tất cả <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $products->where('is_active', 1)->count() }}</h3>
                    <p>Đang bán</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <a href="{{ route('admin.products.index', ['status' => 'active']) }}" class="small-box-footer">
                    Chi tiết <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $products->where('is_active', 0)->count() }}</h3>
                    <p>Đã đóng băng</p>
                </div>
                <div class="icon">
                    <i class="fas fa-ban"></i>
                </div>
                <a href="{{ route('admin.products.index', ['status' => 'inactive']) }}" class="small-box-footer">
                    Chi tiết <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    @php
                        $lowStock = $products->filter(function($p) { 
                            return $p->variants->sum('stock') < 10; 
                        })->count();
                    @endphp
                    <h3>{{ $lowStock }}</h3>
                    <p>Sắp hết hàng</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <a href="#" class="small-box-footer">
                    Cảnh báo <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i> 
                @if($status == 'active')
                    Sản phẩm Đang bán
                @elseif($status == 'inactive')
                    Sản phẩm Đã đóng băng
                @else
                    Tất cả Sản phẩm
                @endif
            </h3>
            <div class="card-tools">
                {{-- Filter Buttons --}}
                <div class="btn-group mr-2">
                    <a href="{{ route('admin.products.index', ['status' => 'active']) }}" 
                       class="btn btn-sm {{ $status == 'active' ? 'btn-success' : 'btn-default' }}"
                       title="Đang bán">
                        <i class="fas fa-check-circle"></i> Đang bán
                    </a>
                    <a href="{{ route('admin.products.index', ['status' => 'inactive']) }}" 
                       class="btn btn-sm {{ $status == 'inactive' ? 'btn-warning' : 'btn-default' }}"
                       title="Đã đóng băng">
                        <i class="fas fa-ban"></i> Đã đóng băng
                    </a>
                    <a href="{{ route('admin.products.index', ['status' => 'all']) }}" 
                       class="btn btn-sm {{ $status == 'all' ? 'btn-secondary' : 'btn-default' }}"
                       title="Tất cả">
                        <i class="fas fa-list"></i> Tất cả
                    </a>
                </div>
                
                {{-- Add Button --}}
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Thêm Sản phẩm
                </a>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 60px" class="text-center">ID</th>
                            <th style="width: 80px" class="text-center">Ảnh</th>
                            <th style="width: 30%">Tên sản phẩm</th>
                            <th style="width: 12%">Danh mục</th>
                            <th style="width: 12%">Thương hiệu</th>
                            <th style="width: 10%" class="text-center">Tồn kho</th>
                            <th style="width: 15%">Giá bán</th>
                            <th style="width: 180px" class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                        <tr class="{{ $product->is_active ? '' : 'table-secondary' }}">
                            <td class="text-center">
                                <strong>#{{ $product->productID }}</strong>
                            </td>
                            <td class="text-center">
                                @php
                                    $mainImage = $product->images->where('imageType', 'main')->first() ?? $product->images->first();
                                @endphp
                                @if ($mainImage)
                                    <img src="{{ asset('storage/' . $mainImage->imageUrl) }}" 
                                         alt="{{ $product->productName }}" 
                                         class="img-thumbnail"
                                         style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;"
                                         onclick="showImageModal('{{ asset('storage/' . $mainImage->imageUrl) }}', '{{ $product->productName }}')">
                                @else
                                    <div class="text-muted">
                                        <i class="far fa-image fa-3x"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="product-name">
                                    <strong>{{ $product->productName }}</strong>
                                </div>
                                @if (!$product->is_active)
                                    <span class="badge badge-warning mt-1">
                                        <i class="fas fa-ban"></i> Đóng Băng
                                    </span>
                                @endif
                                @php
                                    $totalStock = $product->variants->sum('stock');
                                @endphp
                                @if ($product->is_active && $totalStock < 10)
                                    <span class="badge badge-danger mt-1">
                                        <i class="fas fa-exclamation-triangle"></i> Sắp hết
                                    </span>
                                @endif
                                <div class="text-muted small mt-1">
                                    <i class="fas fa-cubes"></i> {{ $product->variants->count() }} biến thể
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    {{ $product->category->categoryName ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    {{ $product->brand->brandName ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php
                                    $totalStock = $product->variants->sum('stock');
                                    $reservedStock = $product->variants->sum('reservedStock');
                                    $availableStock = $totalStock - $reservedStock;
                                @endphp
                                <div>
                                    <strong class="text-{{ $totalStock < 10 ? 'danger' : 'success' }}">
                                        {{ $totalStock }}
                                    </strong>
                                </div>
                                @if ($reservedStock > 0)
                                    <small class="text-muted">
                                        Giữ: {{ $reservedStock }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $minPrice = $product->variants->min('price');
                                    $maxPrice = $product->variants->max('price');
                                @endphp
                                <div class="price-range">
                                    @if ($minPrice == $maxPrice)
                                        <strong class="text-primary">
                                            {{ number_format($minPrice, 0, ',', '.') }}đ
                                        </strong>
                                    @else
                                        <div>
                                            <small>Từ:</small>
                                            <strong class="text-primary">
                                                {{ number_format($minPrice, 0, ',', '.') }}đ
                                            </strong>
                                        </div>
                                        <div>
                                            <small>Đến:</small>
                                            <strong class="text-success">
                                                {{ number_format($maxPrice, 0, ',', '.') }}đ
                                            </strong>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    {{-- Nút Sửa --}}
                                    <a href="{{ route('admin.products.edit', $product) }}" 
                                       class="btn btn-info"
                                       title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    @if ($product->is_active)
                                        {{-- Nút ĐÓNG BĂNG --}}
                                        <button type="button" 
                                                class="btn btn-warning btn-freeze-product" 
                                                data-product-id="{{ $product->productID }}"
                                                data-product-name="{{ $product->productName }}"
                                                title="Đóng băng">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @else
                                        {{-- Nút KÍCH HOẠT LẠI --}}
                                        <button type="button" 
                                                class="btn btn-success btn-activate-product" 
                                                data-product-id="{{ $product->productID }}"
                                                data-product-name="{{ $product->productName }}"
                                                title="Kích hoạt lại">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    @endif
                                </div>
                                
                                {{-- Hidden Forms --}}
                                <form id="freeze-form-{{ $product->productID }}" 
                                      action="{{ route('admin.products.destroy', $product) }}" 
                                      method="POST" 
                                      style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                
                                <form id="activate-form-{{ $product->productID }}" 
                                      action="{{ route('admin.products.update', $product) }}" 
                                      method="POST" 
                                      style="display:none;">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action_reactivate" value="1">
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Không có sản phẩm nào</p>
                                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tạo sản phẩm đầu tiên
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($products->hasPages())
        <div class="card-footer clearfix">
            <div class="float-left">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Hiển thị {{ $products->firstItem() }} - {{ $products->lastItem() }} 
                    trong tổng số <strong>{{ $products->total() }}</strong> sản phẩm
                </small>
            </div>
            <div class="float-right">
                {{ $products->appends(['status' => $status])->links('pagination::bootstrap-4') }}
            </div>
        </div>
        @endif
    </div>

    {{-- Image Modal --}}
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Ảnh sản phẩm</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="">
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .small-box h3 {
        font-size: 2.2rem;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa !important;
    }
    
    .table-secondary {
        opacity: 0.7;
    }
    
    .product-name {
        font-size: 1.05em;
    }
    
    .price-range {
        line-height: 1.3;
    }
    
    .img-thumbnail {
        border: 2px solid #dee2e6;
        transition: transform 0.2s;
    }
    
    .img-thumbnail:hover {
        transform: scale(1.1);
        border-color: #007bff;
        box-shadow: 0 0 10px rgba(0,123,255,0.3);
    }
    
    .badge {
        font-size: 0.85em;
        padding: 0.3em 0.6em;
    }
    
    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .card-outline {
        border-top: 3px solid #007bff;
    }
</style>
@stop

@section('js')
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    console.log('✅ Products index page ready');
    
    // ========================================================================
    // ĐÓNG BĂNG SẢN PHẨM
    // ========================================================================
    $('.btn-freeze-product').on('click', function() {
        const productId = $(this).data('product-id');
        const productName = $(this).data('product-name');
        
        console.log('🧊 Freezing product:', productId, productName);
        
        Swal.fire({
            title: 'Xác nhận ĐÓNG BĂNG?',
            html: `Bạn có chắc chắn muốn đóng băng sản phẩm<br><strong>"${productName}"</strong>?<br><br>
                   <small class="text-muted">Sản phẩm sẽ bị ẩn khỏi trang bán hàng</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-ban"></i> Đóng băng',
            cancelButtonText: '<i class="fas fa-times"></i> Hủy',
            customClass: {
                confirmButton: 'btn btn-warning mr-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('✅ Freeze confirmed, submitting form');
                $(`#freeze-form-${productId}`).submit();
            }
        });
    });
    
    // ========================================================================
    // KÍCH HOẠT LẠI SẢN PHẨM
    // ========================================================================
    $('.btn-activate-product').on('click', function() {
        const productId = $(this).data('product-id');
        const productName = $(this).data('product-name');
        
        console.log('✅ Activating product:', productId, productName);
        
        Swal.fire({
            title: 'Xác nhận KÍCH HOẠT?',
            html: `Bạn có chắc chắn muốn kích hoạt lại sản phẩm<br><strong>"${productName}"</strong>?<br><br>
                   <small class="text-muted">Sản phẩm sẽ hiển thị trở lại trên trang bán hàng</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check-circle"></i> Kích hoạt',
            cancelButtonText: '<i class="fas fa-times"></i> Hủy',
            customClass: {
                confirmButton: 'btn btn-success mr-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('✅ Activation confirmed, submitting form');
                $(`#activate-form-${productId}`).submit();
            }
        });
    });
    
    // ========================================================================
    // AUTO DISMISS ALERTS
    // ========================================================================
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});

// ============================================================================
// SHOW IMAGE MODAL
// ============================================================================
function showImageModal(imageUrl, productName) {
    $('#modalImage').attr('src', imageUrl);
    $('#imageModalLabel').text(productName);
    $('#imageModal').modal('show');
}
</script>
@stop