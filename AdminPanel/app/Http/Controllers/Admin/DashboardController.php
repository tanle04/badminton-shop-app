<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with statistics
     */
    public function index()
    {
        // ====================================================================
        // 1. MAIN STATISTICS
        // ====================================================================
        
        // Total Revenue (Paid orders)
        $totalRevenue = DB::table('orders')
            ->where('paymentStatus', 'Paid')
            ->sum('total');
        
        // New Orders (Pending)
        $newOrders = DB::table('orders')
            ->where('status', 'Pending')
            ->count();
        
        // Low Stock Products (stock < 5)
        $lowStockProducts = DB::table('product_variants')
            ->where('stock', '<', 5)
            ->count();
        
        // Pending Reviews
        $pendingReviews = DB::table('reviews')
            ->where('status', 'pending')
            ->count();
        
        // ====================================================================
        // 2. ADDITIONAL STATISTICS
        // ====================================================================
        
        // Total Products
        $totalProducts = DB::table('products')->count();
        $activeProducts = DB::table('products')->where('is_active', 1)->count();
        
        // Orders This Month
        $ordersThisMonth = DB::table('orders')
            ->whereYear('orderDate', Carbon::now()->year)
            ->whereMonth('orderDate', Carbon::now()->month)
            ->count();
        
        // Orders Last Month (for growth calculation)
        $ordersLastMonth = DB::table('orders')
            ->whereYear('orderDate', Carbon::now()->subMonth()->year)
            ->whereMonth('orderDate', Carbon::now()->subMonth()->month)
            ->count();
        
        // Calculate Growth
        $ordersGrowth = 0;
        if ($ordersLastMonth > 0) {
            $ordersGrowth = round((($ordersThisMonth - $ordersLastMonth) / $ordersLastMonth) * 100, 1);
        }
        
        // Total Customers
        $totalCustomers = DB::table('customers')->count();
        
        // New Customers This Month - FIX: Use createdDate instead of created_at
        $newCustomersThisMonth = DB::table('customers')
            ->whereYear('createdDate', Carbon::now()->year)
            ->whereMonth('createdDate', Carbon::now()->month)
            ->count();
        
        // Average Order Value
        $avgOrderValue = DB::table('orders')
            ->where('paymentStatus', 'Paid')
            ->avg('total');
        
        // ====================================================================
        // 3. CHART DATA - TOP SELLING PRODUCTS
        // ====================================================================
        
        $topSellingProducts = DB::table('orderdetails')
            ->join('product_variants', 'orderdetails.variantID', '=', 'product_variants.variantID')
            ->join('products', 'product_variants.productID', '=', 'products.productID')
            ->join('orders', 'orderdetails.orderID', '=', 'orders.orderID')
            ->where('orders.status', '!=', 'Cancelled')
            ->whereDate('orders.orderDate', '>=', Carbon::now()->subDays(30))
            ->select(
                'products.productName',
                DB::raw('SUM(orderdetails.quantity) as total_sold')
            )
            ->groupBy('products.productID', 'products.productName')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();
        
        $chart_top_labels = $topSellingProducts->pluck('productName')->toArray();
        $chart_top_data = $topSellingProducts->pluck('total_sold')->toArray();
        
        // Default values if no data
        if (empty($chart_top_labels)) {
            $chart_top_labels = ['Chưa có dữ liệu'];
            $chart_top_data = [0];
        }
        
        // ====================================================================
        // 4. CHART DATA - REVENUE BY ORDER STATUS
        // ====================================================================
        
        $revenueByStatus = DB::table('orders')
            ->select(
                'status',
                DB::raw('SUM(total) as total_revenue')
            )
            ->where('paymentStatus', 'Paid')
            ->groupBy('status')
            ->get();
        
        // Map status to Vietnamese labels
        $statusMapping = [
            'Pending' => 'Chờ xử lý',
            'Processing' => 'Đang xử lý',
            'Shipping' => 'Đang giao',
            'Completed' => 'Hoàn thành',
            'Cancelled' => 'Đã hủy'
        ];
        
        $chart_status_labels = $revenueByStatus->map(function($item) use ($statusMapping) {
            return $statusMapping[$item->status] ?? $item->status;
        })->toArray();
        
        $chart_status_data = $revenueByStatus->pluck('total_revenue')->toArray();
        
        // Default values if no data
        if (empty($chart_status_labels)) {
            $chart_status_labels = ['Chưa có dữ liệu'];
            $chart_status_data = [0];
        }
        
        // ====================================================================
        // 5. CHART DATA - REVENUE TREND (Last 7 days)
        // ====================================================================
        
        $revenueTrend = [];
        $dates = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dates[] = $date->format('d/m');
            
            $revenue = DB::table('orders')
                ->whereDate('orderDate', $date->format('Y-m-d'))
                ->where('status', '!=', 'Cancelled')
                ->where('paymentStatus', 'Paid')
                ->sum('total');
            
            $revenueTrend[] = $revenue ?? 0;
        }
        
        $chart_revenue_dates = $dates;
        $chart_revenue_data = $revenueTrend;
        
        // ====================================================================
        // 6. RECENT ACTIVITIES
        // ====================================================================
        
        $recentActivities = [];
        
        // Recent Orders
        $recentOrders = DB::table('orders')
            ->orderBy('orderDate', 'desc')
            ->limit(3)
            ->get();
        
        foreach ($recentOrders as $order) {
            $statusColors = [
                'Pending' => 'info',
                'Processing' => 'primary',
                'Shipping' => 'warning',
                'Completed' => 'success',
                'Cancelled' => 'danger'
            ];
            
            $recentActivities[] = [
                'icon' => 'shopping-cart',
                'color' => $statusColors[$order->status] ?? 'info',
                'title' => 'Đơn hàng #' . $order->orderID . ' - ' . $order->status,
                'time' => Carbon::parse($order->orderDate)->diffForHumans(),
                'timestamp' => Carbon::parse($order->orderDate)->timestamp
            ];
        }
        
        // Recent Reviews
        $recentReviews = DB::table('reviews')
            ->join('customers', 'reviews.customerID', '=', 'customers.customerID')
            ->join('products', 'reviews.productID', '=', 'products.productID')
            ->select(
                'reviews.*',
                'customers.fullName',
                'products.productName'
            )
            ->orderBy('reviews.reviewDate', 'desc')
            ->limit(2)
            ->get();
        
        foreach ($recentReviews as $review) {
            $statusColors = [
                'published' => 'success',
                'pending' => 'warning',
                'hidden' => 'danger'
            ];
            
            $recentActivities[] = [
                'icon' => 'star',
                'color' => $statusColors[$review->status] ?? 'warning',
                'title' => $review->fullName . ' đánh giá ' . $review->rating . '⭐ - ' . $review->productName,
                'time' => Carbon::parse($review->reviewDate)->diffForHumans(),
                'timestamp' => Carbon::parse($review->reviewDate)->timestamp
            ];
        }
        
        // Sort by timestamp (most recent first)
        usort($recentActivities, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Limit to 5 activities
        $recentActivities = array_slice($recentActivities, 0, 5);
        
        // ====================================================================
        // 7. RETURN VIEW WITH ALL DATA
        // ====================================================================
        
        return view('admin.dashboard', compact(
            // Main Stats
            'totalRevenue',
            'newOrders',
            'lowStockProducts',
            'pendingReviews',
            
            // Additional Stats
            'totalProducts',
            'activeProducts',
            'ordersThisMonth',
            'ordersGrowth',
            'totalCustomers',
            'newCustomersThisMonth',
            'avgOrderValue',
            
            // Chart Data - Top Products
            'chart_top_labels',
            'chart_top_data',
            
            // Chart Data - Revenue by Status
            'chart_status_labels',
            'chart_status_data',
            
            // Chart Data - Revenue Trend
            'chart_revenue_dates',
            'chart_revenue_data',
            
            // Recent Activities
            'recentActivities'
        ));
    }
}