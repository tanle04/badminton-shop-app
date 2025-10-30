<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order; 
use App\Models\ProductVariant;
use App\Models\Shipping; 
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class OrderController extends Controller
{
    /**
     * Display a listing of orders with advanced filters
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $statusFilter = $request->get('status', 'all');
        $paymentFilter = $request->get('payment', 'all');
        $search = $request->get('search', '');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        // Start query with relationships
        $query = Order::with(['customer', 'address']);
        
        // Apply status filter
        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }
        
        // Apply payment status filter
        if ($paymentFilter && $paymentFilter !== 'all') {
            $query->where('paymentStatus', $paymentFilter);
        }
        
        // Apply search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('orderID', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($subQ) use ($search) {
                      $subQ->where('fullName', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        
        // Apply date range filter
        if ($dateFrom) {
            $query->whereDate('orderDate', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('orderDate', '<=', $dateTo);
        }
        
        // Order by latest
        $query->orderBy('orderDate', 'desc');
        
        // Paginate
        $orders = $query->paginate(15);
        
        // Calculate statistics
        $stats = $this->calculateStats();
        
        return view('admin.orders.index', compact('orders', 'stats'));
    }
    
    /**
     * Calculate order statistics for all statuses
     */
    private function calculateStats()
    {
        return [
            'total' => Order::count(),
            'pending' => Order::where('status', 'Pending')->count(),
            'processing' => Order::where('status', 'Processing')->count(),
            'shipped' => Order::where('status', 'Shipped')->count(),
            'delivered' => Order::where('status', 'Delivered')->count(),
            'cancelled' => Order::where('status', 'Cancelled')->count(),
            'refunded' => Order::where('status', 'Refunded')->count(),
            
            // Payment statistics
            'paid' => Order::where('paymentStatus', 'Paid')->count(),
            'unpaid' => Order::where('paymentStatus', 'Unpaid')->count(),
            
            // Revenue
            'total_revenue' => Order::where('paymentStatus', 'Paid')->sum('total'),
        ];
    }

    /**
     * Display order details
     */
    public function show(Order $order)
    {
        $order->load([
            'customer', 
            'address', 
            'orderDetails.variant.product.images',
            'orderDetails.variant.attributeValues', 
            'voucher',
            'shipping'
        ]);

        return view('admin.orders.show', compact('order'));
    }
    
    /**
     * Update order status with business logic validation
     */
    public function update(Request $request, Order $order)
    {
        // 1. CHECK PERMISSIONS
        if (!Gate::allows('admin') && !Gate::allows('staff')) {
            return redirect()->route('admin.orders.show', $order)
                             ->with('error', 'Bạn không có quyền cập nhật trạng thái đơn hàng.');
        }

        $request->validate([
            'status' => ['required', Rule::in(['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Refunded'])],
            'paymentStatus' => ['required', Rule::in(['Unpaid', 'Paid', 'Refunded'])],
            'trackingCode' => 'nullable|string|max:255'
        ]);
        
        $oldStatus = $order->status;
        $newStatus = $request->status;
        $newPaymentStatus = $request->paymentStatus;
        
        // --- BUSINESS LOGIC: LINEAR WORKFLOW & VALIDATION ---
        
        $finalStates = ['Delivered', 'Cancelled', 'Refunded'];
        $transitionMap = [
            'Pending'    => ['Processing', 'Cancelled'],
            'Processing' => ['Shipped', 'Cancelled'],
            'Shipped'    => ['Delivered', 'Cancelled'],
            'Delivered'  => ['Refunded'],
            'Cancelled'  => ['Refunded'],
            'Refunded'   => ['Cancelled'],
        ];

        // Validate state transitions (except when status stays the same)
        if ($newStatus !== $oldStatus) {
            
            $isFinalStateChange = in_array($oldStatus, $finalStates);
            $allowedTransitions = $transitionMap[$oldStatus] ?? [];
            
            // If not in final state, validate allowed transitions
            if (!$isFinalStateChange) {
                if (!in_array($newStatus, $allowedTransitions)) {
                    return redirect()->back()->with('error', 
                        "Lỗi nghiệp vụ: Không thể chuyển từ '$oldStatus' sang '$newStatus'. Luồng hợp lệ: " . implode(', ', $allowedTransitions)
                    );
                }
            } else {
                // ALREADY IN FINAL STATE
                $isAllowedFinalTransition = 
                    ($oldStatus === 'Delivered' && $newStatus === 'Refunded') || 
                    ($oldStatus === 'Cancelled' && $newStatus === 'Refunded') || 
                    ($oldStatus === 'Refunded' && $newStatus === 'Cancelled');
                
                if (!$isAllowedFinalTransition) {
                    return redirect()->back()->with('error', 
                        "Lỗi nghiệp vụ: Đơn hàng đã ở trạng thái kết thúc ($oldStatus). Chuyển đổi sang $newStatus không hợp lệ."
                    );
                }
            }
        }
        // --- END BUSINESS LOGIC ---
        
        // Track products that need stock update
        $productsToUpdate = [];
        
        try {
            DB::beginTransaction();

            // 1. STOCK RESTORATION LOGIC (Cancel or Refund)
            if (($newStatus === 'Cancelled' || $newStatus === 'Refunded') && 
                $oldStatus !== 'Cancelled' && $oldStatus !== 'Refunded') {
                
                foreach ($order->orderDetails as $item) {
                    $variant = ProductVariant::find($item->variantID);
                    if ($variant) {
                        $variant->reservedStock -= $item->quantity;
                        $variant->stock += $item->quantity;
                        if ($variant->reservedStock < 0) $variant->reservedStock = 0; 
                        $variant->save();
                        
                        $productsToUpdate[$variant->productID] = true;
                    }
                }
            }

            // 2. RESERVED STOCK REDUCTION LOGIC (Delivered)
            if ($newStatus === 'Delivered' && $oldStatus !== 'Delivered') {
                foreach ($order->orderDetails as $item) {
                    $variant = ProductVariant::find($item->variantID);
                    if ($variant) {
                        $variant->reservedStock -= $item->quantity;
                        if ($variant->reservedStock < 0) $variant->reservedStock = 0; 
                        $variant->save();

                        $productsToUpdate[$variant->productID] = true;
                    }
                }
                
                // Auto-set Paid for COD when Delivered
                if ($order->paymentMethod === 'COD' && $request->paymentStatus === 'Unpaid') {
                    $newPaymentStatus = 'Paid';
                    session()->flash('info', 'Đơn hàng COD đã giao thành công. Trạng thái thanh toán tự động chuyển sang "Paid".');
                }
            }
            
            // 3. UPDATE TOTAL STOCK FOR AFFECTED PRODUCTS
            if (!empty($productsToUpdate)) {
                $productIDs = array_keys($productsToUpdate);
                $products = \App\Models\Product::whereIn('productID', $productIDs)->get(); 
                
                foreach ($products as $product) {
                    $product->updateStockAndPriceFromVariants();
                }
            }

            // 4. SAVE TRACKING CODE
            if ($request->has('trackingCode') || optional($order->shipping)->exists) {
                $trackingCode = trim($request->trackingCode);
                
                $shipping = $order->shipping ?? new Shipping(['orderID' => $order->orderID]);
                $shipping->shippingMethod = $shipping->shippingMethod ?? 'Standard'; 
                $shipping->shippingFee = $shipping->shippingFee ?? 0.00; 
                $shipping->trackingCode = $trackingCode;
                
                if ($newStatus === 'Shipped' && !$shipping->shippedDate) {
                    $shipping->shippedDate = now();
                }
                
                $shipping->save();
            }
            
            // 5. UPDATE ORDER STATUS
            $order->status = $newStatus;
            $order->paymentStatus = $newPaymentStatus;
            $order->save();

            // 6. SEND EMAIL NOTIFICATIONS (if functions exist)
            $recipientEmail = $order->customer->email ?? null; 
            $recipientName = $order->customer->fullName ?? 'Quý khách';
            $orderID = $order->orderID;

            if ($recipientEmail && $newStatus !== $oldStatus) {
                if ($newStatus === 'Delivered' && function_exists('sendDeliveryConfirmationEmail')) {
                    \sendDeliveryConfirmationEmail($recipientEmail, $recipientName, $orderID);
                    session()->flash('email_info', 'Email xác nhận giao hàng đã được gửi.');
                } else if (($newStatus === 'Cancelled' || $newStatus === 'Refunded') && function_exists('sendCancellationEmail')) {
                    \sendCancellationEmail($recipientEmail, $recipientName, $orderID);
                    session()->flash('email_info', 'Email thông báo hủy/hoàn tiền đã được gửi.');
                }
            }
            
            DB::commit();

            return redirect()->route('admin.orders.show', $order)
                             ->with('success', 'Đã cập nhật trạng thái đơn hàng thành ' . $newStatus . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Lỗi xử lý nghiệp vụ: ' . $e->getMessage());
        }
    }
    
    /**
     * Export orders to Excel (future feature)
     */
    public function export(Request $request)
    {
        return redirect()->back()->with('info', 'Chức năng xuất Excel đang được phát triển');
    }
    
    /**
     * Bulk update orders (future feature)
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,orderID',
            'action' => 'required|in:mark_paid,mark_shipped,mark_delivered,cancel'
        ]);
        
        $orderIds = $request->order_ids;
        $action = $request->action;
        
        $updated = 0;
        
        DB::beginTransaction();
        try {
            foreach ($orderIds as $orderId) {
                $order = Order::find($orderId);
                if (!$order) continue;
                
                switch ($action) {
                    case 'mark_paid':
                        $order->paymentStatus = 'Paid';
                        $order->save();
                        $updated++;
                        break;
                        
                    case 'mark_shipped':
                        if ($order->status === 'Processing') {
                            $order->status = 'Shipped';
                            $order->save();
                            $updated++;
                        }
                        break;
                        
                    case 'mark_delivered':
                        if ($order->status === 'Shipped') {
                            $order->status = 'Delivered';
                            $order->save();
                            $updated++;
                        }
                        break;
                        
                    case 'cancel':
                        if (!in_array($order->status, ['Delivered', 'Cancelled', 'Refunded'])) {
                            // Restore stock
                            foreach ($order->orderDetails as $item) {
                                $variant = ProductVariant::find($item->variantID);
                                if ($variant) {
                                    $variant->reservedStock -= $item->quantity;
                                    $variant->stock += $item->quantity;
                                    if ($variant->reservedStock < 0) $variant->reservedStock = 0;
                                    $variant->save();
                                }
                            }
                            
                            $order->status = 'Cancelled';
                            $order->save();
                            $updated++;
                        }
                        break;
                }
            }
            
            DB::commit();
            return redirect()->back()->with('success', "Đã cập nhật $updated đơn hàng thành công!");
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }
    
    // Resource methods
    public function create() { abort(404); }
    public function store(Request $request) { abort(404); }
    public function edit(Order $order) { return $this->show($order); } 
    public function destroy(Order $order) { abort(404); }
}