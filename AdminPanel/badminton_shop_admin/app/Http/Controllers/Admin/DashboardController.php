<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB; // Cần dùng DB Facade cho các truy vấn phức tạp

class DashboardController extends Controller
{
    public function index()
    {
        $data = [];
        
        // 1. DỮ LIỆU CƠ BẢN (Widgets Info Box)
        
        // Dữ liệu chung (tất cả vai trò)
        $data['totalOrders'] = Order::count();

        if (Gate::allows('admin') || Gate::allows('staff')) {
            // Dữ liệu cho Admin/Staff
            $data['newOrders'] = Order::where('status', 'Pending')->count();
            // Đảm bảo cột is_active tồn tại và bạn đang dùng Model Product
            $data['lowStockProducts'] = Product::where('stock', '<', 5)->where('is_active', 1)->count(); 
            $data['pendingReviews'] = Review::where('status', 'pending')->count();
        }

        if (Gate::allows('admin') || Gate::allows('marketing')) {
            // Dữ liệu cho Admin/Marketing
            $data['totalRevenue'] = Order::where('paymentStatus', 'Paid')->sum('total');
        }
        
        // 2. DỮ LIỆU BIỂU ĐỒ (Charts)

        // 2A. BIỂU ĐỒ TOP 5 SẢN PHẨM BÁN CHẠY (Top Selling Products)
        $topSelling = DB::table('orderdetails')
            ->select('products.productName', DB::raw('SUM(orderdetails.quantity) as total_quantity'))
            ->join('product_variants', 'orderdetails.variantID', '=', 'product_variants.variantID')
            ->join('products', 'product_variants.productID', '=', 'products.productID')
            ->groupBy('products.productName')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();
            
        $data['chart_top_labels'] = $topSelling->pluck('productName')->toArray();
        $data['chart_top_data'] = $topSelling->pluck('total_quantity')->toArray();

        // 2B. BIỂU ĐỒ DOANH THU THEO TRẠNG THÁI (Revenue by Status)
        $revenueByStatus = Order::select('status', DB::raw('SUM(total) as revenue'))
            ->where('paymentStatus', 'Paid')
            ->groupBy('status')
            ->get();
        
        $data['chart_status_labels'] = $revenueByStatus->pluck('status')->toArray();
        $data['chart_status_data'] = $revenueByStatus->pluck('revenue')->toArray();


        return view('admin.dashboard', $data);
    }
}