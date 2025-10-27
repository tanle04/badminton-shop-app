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

// ⭐ Giả định các hàm helper PHPMailer sau đã được định nghĩa và có thể truy cập:
// function sendDeliveryConfirmationEmail(string $email, string $name, int $orderID)
// function sendCancellationEmail(string $email, string $name, int $orderID)

class OrderController extends Controller
{
    /**
     * Hiển thị danh sách tất cả đơn hàng.
     */
    public function index()
    {
        $orders = Order::with(['customer', 'address', 'orderDetails.variant.product'])
                        ->latest('orderDate')
                        ->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Hiển thị chi tiết một đơn hàng.
     */
    public function show(Order $order)
    {
        $order->load([
            'customer', 
            'address', 
            'orderDetails.variant.product.images',
            'orderDetails.variant.attributeValues', 
            'voucher',
            'shipping' // Load thông tin vận chuyển
        ]);

        return view('admin.orders.show', compact('order'));
    }
    
    /**
     * Cập nhật trạng thái đơn hàng.
     */
    public function update(Request $request, Order $order)
    {
        // 1. KIỂM TRA QUYỀN
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
        
        // --- LOGIC NGHIỆP VỤ TUYẾN TÍNH & KIỂM TRA CHẶN ---
        
        $finalStates = ['Delivered', 'Cancelled', 'Refunded'];
        $transitionMap = [
            'Pending'    => ['Processing', 'Cancelled'],
            'Processing' => ['Shipped', 'Cancelled'],
            'Shipped'    => ['Delivered', 'Cancelled'],
            'Delivered'  => ['Refunded'],
            'Cancelled'  => ['Refunded'],
            'Refunded'   => ['Cancelled'],
        ];

        // Kiểm tra tất cả chuyển đổi, ngoại trừ khi trạng thái giữ nguyên
        if ($newStatus !== $oldStatus) {
            
            $isFinalStateChange = in_array($oldStatus, $finalStates);
            $allowedTransitions = $transitionMap[$oldStatus] ?? [];
            
            // Nếu không phải là trạng thái cuối cùng HOẶC trạng thái đang bị giới hạn
            if (!$isFinalStateChange) {
                if (!in_array($newStatus, $allowedTransitions)) {
                    return redirect()->back()->with('error', 
                        "Lỗi nghiệp vụ: Không thể chuyển từ '$oldStatus' sang '$newStatus'. Luồng hợp lệ: " . implode(', ', $allowedTransitions)
                    );
                }
            } else {
                // ĐÃ Ở TRẠNG THÁI KẾT THÚC
                $isAllowedFinalTransition = 
                    ($oldStatus === 'Delivered' && $newStatus === 'Refunded') || // Delivered -> Refunded
                    ($oldStatus === 'Cancelled' && $newStatus === 'Refunded') || // Cancelled -> Refunded
                    ($oldStatus === 'Refunded' && $newStatus === 'Cancelled'); // Refunded -> Cancelled (Chỉ đổi nhãn)
                
                if (!$isAllowedFinalTransition) {
                    return redirect()->back()->with('error', 
                        "Lỗi nghiệp vụ: Đơn hàng đã ở trạng thái kết thúc ($oldStatus). Chuyển đổi sang $newStatus không hợp lệ."
                    );
                }
            }
        }
        // --- KẾT THÚC LOGIC NGHIỆP VỤ ---
        
        // Theo dõi các Product ID đã bị thay đổi stock để cập nhật tổng stock 1 lần duy nhất.
        $productsToUpdate = [];
        
        try {
            DB::beginTransaction();

            // 1. LOGIC HOÀN KHO KHI HỦY HOẶC HOÀN TIỀN
            if (($newStatus === 'Cancelled' || $newStatus === 'Refunded') && 
                $oldStatus !== 'Cancelled' && $oldStatus !== 'Refunded') {
                
                foreach ($order->orderDetails as $item) {
                    $variant = ProductVariant::find($item->variantID);
                    if ($variant) {
                        $variant->reservedStock -= $item->quantity;
                        $variant->stock += $item->quantity;
                        if ($variant->reservedStock < 0) $variant->reservedStock = 0; 
                        $variant->save();
                        
                        // ⭐ THÊM ID SẢN PHẨM CẦN CẬP NHẬT TỔNG STOCK
                        $productsToUpdate[$variant->productID] = true;
                    }
                }
            }

            // 2. LOGIC GIẢM RESERVED_STOCK & TT THANH TOÁN KHI GIAO THÀNH CÔNG
            if ($newStatus === 'Delivered' && $oldStatus !== 'Delivered') {
                foreach ($order->orderDetails as $item) {
                    $variant = ProductVariant::find($item->variantID);
                    if ($variant) {
                        $variant->reservedStock -= $item->quantity;
                        if ($variant->reservedStock < 0) $variant->reservedStock = 0; 
                        $variant->save();

                        // ⭐ THÊM ID SẢN PHẨM CẦN CẬP NHẬT TỔNG STOCK
                        $productsToUpdate[$variant->productID] = true;
                    }
                }
                
                // Tự động set Paid cho COD khi Delivered
                if ($order->paymentMethod === 'COD' && $request->paymentStatus === 'Unpaid') {
                    $newPaymentStatus = 'Paid';
                    session()->flash('info', 'Đơn hàng COD đã giao thành công. Trạng thái thanh toán tự động chuyển sang "Paid".');
                }
            }
            
            // ⭐ 3. CẬP NHẬT TỔNG STOCK CHO CÁC SẢN PHẨM LIÊN QUAN
            if (!empty($productsToUpdate)) {
                // Tải lại các Product model cần thiết để gọi hàm update
                $productIDs = array_keys($productsToUpdate);
                $products = \App\Models\Product::whereIn('productID', $productIDs)->get(); 
                
                foreach ($products as $product) {
                    // Gọi hàm đã thêm vào Product Model
                    $product->updateStockAndPriceFromVariants();
                }
            }

            // 4. LƯU MÃ VẬN ĐƠN (TRACKING CODE)
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
            
            // 5. CẬP NHẬT TRẠNG THÁI CUỐI CÙNG
            $order->status = $newStatus;
            $order->paymentStatus = $newPaymentStatus; // Sử dụng biến mới nhất
            $order->save();

            // 6. GỬI EMAIL THÔNG BÁO
            $recipientEmail = $order->customer->email ?? null; 
            $recipientName = $order->customer->fullName ?? 'Quý khách';
            $orderID = $order->orderID;

          if ($recipientEmail && $newStatus !== $oldStatus) {
                if ($newStatus === 'Delivered') {
                    // SỬA: Thêm \ để gọi hàm ở Global Scope
                    \sendDeliveryConfirmationEmail($recipientEmail, $recipientName, $orderID);
                    session()->flash('email_info', 'Email xác nhận giao hàng đã được gửi.');
                } else if ($newStatus === 'Cancelled' || $newStatus === 'Refunded') {
                    // SỬA: Thêm \ để gọi hàm ở Global Scope
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
    
    // ... (Các phương thức resource khác) ...
    public function create() { abort(404); }
    public function store(Request $request) { abort(404); }
    public function edit(Order $order) { return $this->show($order); } 
    public function destroy(Order $order) { abort(404); }
}