<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Shipping;
use App\Models\RefundRequest; 
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
// use Illuminate\Support\Facades\Mail; // <- KHÔNG CẦN NỮA, VÌ HELPER ĐÃ XỬ LÝ
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with('customer')->orderBy('orderDate', 'desc');

        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(15);
        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Display the specified resource.
     */
    public function create()
    {
        return redirect()->route('admin.orders.index')->with('error', 'Chức năng này không được hỗ trợ.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return redirect()->route('admin.orders.index')->with('error', 'Chức năng này không được hỗ trợ.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load(
            'customer', 
            'address', 
            'shipping', 
            'voucher', 
            'orderDetails.variant.product.images', 
            'orderDetails.variant.attributeValues'
        );

        $refundRequest = null;
        if ($order->status === 'Refund Requested') {
            $refundRequest = RefundRequest::where('orderID', $order->orderID)
                                          ->where('status', 'Pending')
                                          ->with(['items.orderDetail.variant.product', 'media'])
                                          ->first();
        }

        return view('admin.orders.show', compact('order', 'refundRequest'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        return redirect()->route('admin.orders.show', $order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        if (!Gate::allows('admin') && !Gate::allows('staff')) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Bạn không có quyền cập nhật đơn hàng.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Refunded'])],
            'paymentStatus' => ['required', Rule::in(['Unpaid', 'Paid', 'Refunded'])],
            'trackingCode' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $order->status;
            $newStatus = $validated['status'];

            $order->status = $newStatus;
            $order->paymentStatus = $validated['paymentStatus'];
            $order->save();

            if ($request->filled('trackingCode')) {
                $shipping = $order->shipping ?? new Shipping();
                $shipping->orderID = $order->orderID;
                $shipping->trackingCode = $validated['trackingCode'];
                
                if (!$shipping->exists) {
                     $shipping->shippingMethod = $order->shipping->shippingMethod ?? 'N/A'; // Lấy từ đơn hàng nếu có
                     $shipping->shippingFee = $order->shipping->shippingFee ?? 0;
                }
               
                if ($newStatus == 'Shipped' && $oldStatus != 'Shipped') {
                    $shipping->shippedDate = now();
                }
                $shipping->save();
            }
            
            // 3. Xử lý hoàn kho VÀ GỬI EMAIL
            if ($newStatus == 'Cancelled' && $oldStatus != 'Cancelled') {
                 $productsToUpdate = [];
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
                 
                 if (!empty($productsToUpdate)) {
                     $productIDs = array_keys($productsToUpdate);
                     $products = \App\Models\Product::whereIn('productID', $productIDs)->get();
                     foreach ($products as $product) {
                         $product->updateStockAndPriceFromVariants();
                     }
                 }
                 
                 // ⭐ GỌI EMAIL HỦY ĐƠN (TỪ HELPER)
                 if ($order->customer->email) {
                     sendCancellationEmail(
                         $order->customer->email, 
                         $order->customer->fullName, 
                         $order->orderID
                     );
                 }
            }

            // 4. ⭐ GỬI EMAIL GIAO HÀNG THÀNH CÔNG (TỪ HELPER)
            if ($newStatus == 'Delivered' && $oldStatus != 'Delivered') {
                if ($order->customer->email) {
                    sendDeliveryConfirmationEmail(
                        $order->customer->email,
                        $order->customer->fullName,
                        $order->orderID
                    );
                }
            }

            DB::commit();
            return redirect()->route('admin.orders.show', $order)->with('success', 'Cập nhật đơn hàng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order Update Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Lỗi khi cập nhật đơn hàng: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        if (!Gate::allows('admin')) {
             return redirect()->route('admin.orders.index')->with('error', 'Chỉ Admin mới có quyền xóa đơn hàng.');
        }
        
        try {
            $order->delete();
            return redirect()->route('admin.orders.index')->with('success', 'Đã xóa đơn hàng (thao tác này không được khuyến khích).');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Không thể xóa đơn hàng. Lỗi: ' . $e->getMessage());
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CÁC HÀM XỬ LÝ REFUND
    |--------------------------------------------------------------------------
    */

    /**
     * ⭐ APPROVE REFUND REQUEST
     */
    public function approveRefund(Order $order)
    {
        if (!Gate::allows('admin') && !Gate::allows('staff')) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Bạn không có quyền xử lý yêu cầu hoàn tiền.');
        }
        if ($order->status !== 'Refund Requested') {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Đơn hàng này không có yêu cầu hoàn tiền.');
        }
        $refundRequest = RefundRequest::where('orderID', $order->orderID)->where('status', 'Pending')->first();
        if (!$refundRequest) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Không tìm thấy yêu cầu hoàn tiền hợp lệ.');
        }

        try {
            DB::beginTransaction();

            // 1. RESTORE STOCK
            $productsToUpdate = [];
            foreach ($refundRequest->items as $refundItem) {
                $orderDetail = $refundItem->orderDetail;
                $variant = ProductVariant::find($orderDetail->variantID);
                if ($variant) {
                    $quantityToRestore = $refundItem->quantity;
                    $variant->reservedStock -= $quantityToRestore;
                    $variant->stock += $quantityToRestore;
                    if ($variant->reservedStock < 0) $variant->reservedStock = 0;
                    $variant->save();
                    $productsToUpdate[$variant->productID] = true;
                }
            }

            if (!empty($productsToUpdate)) {
                $productIDs = array_keys($productsToUpdate);
                $products = \App\Models\Product::whereIn('productID', $productIDs)->get();
                foreach ($products as $product) {
                    $product->updateStockAndPriceFromVariants();
                }
            }

            // 2. UPDATE ORDER STATUS
            $order->status = 'Refunded';
            $order->paymentStatus = 'Refunded';
            $order->save();

            // 3. UPDATE REFUND REQUEST STATUS
            $refundRequest->status = 'Approved';
            $refundRequest->adminNotes = 'Đã chấp nhận hoàn tiền bởi ' . auth('admin')->user()->fullName;
            $refundRequest->save();

            DB::commit();

            // 4. ⭐ GỌI EMAIL CHẤP NHẬN REFUND (TỪ HELPER)
            if ($order->customer->email) {
                sendRefundApprovedEmail(
                    $order->customer->email,
                    $order->customer->fullName,
                    $order->orderID,
                    $refundRequest->reason ?? 'Không có lý do cụ thể',
                    $order->paymentMethod
                );
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Đã chấp nhận yêu cầu hoàn tiền. Đơn hàng chuyển sang trạng thái Refunded và email đã được gửi cho khách hàng.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approve Refund Error: '." ". $e->getMessage());
            return redirect()->back()->with('error', 'Lỗi xử lý: ' . $e->getMessage());
        }
    }

    /**
     * ⭐ REJECT REFUND REQUEST
     */
    public function rejectRefund(Request $request, Order $order)
    {
        if (!Gate::allows('admin') && !Gate::allows('staff')) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Bạn không có quyền xử lý yêu cầu hoàn tiền.');
        }
        if ($order->status !== 'Refund Requested') {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Đơn hàng này không có yêu cầu hoàn tiền.');
        }
        $refundRequest = RefundRequest::where('orderID', $order->orderID)->where('status', 'Pending')->first();
        if (!$refundRequest) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Không tìm thấy yêu cầu hoàn tiền hợp lệ.');
        }

        try {
            DB::beginTransaction();

            // 1. UPDATE ORDER STATUS
            $order->status = 'Delivered';
            $order->save();

            // 2. UPDATE REFUND REQUEST STATUS
            $refundRequest->status = 'Rejected';
            $rejectReason = $request->input('reject_reason', 'Không đáp ứng điều kiện hoàn trả');
            $refundRequest->adminNotes = $rejectReason . ' - Từ chối bởi ' . auth('admin')->user()->fullName;
            $refundRequest->save();

            DB::commit();

            // 3. ⭐ GỌI EMAIL TỪ CHỐI REFUND (TỪ HELPER)
            if ($order->customer->email) {
                sendRefundRejectedEmail(
                    $order->customer->email,
                    $order->customer->fullName,
                    $order->orderID,
                    $refundRequest->reason ?? 'Không có lý do cụ thể',
                    $refundRequest->adminNotes // Lý do từ chối
                );
            }
            
            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Đã từ chối yêu cầu hoàn tiền. Đơn hàng quay lại trạng thái Delivered và email đã được gửi cho khách hàng.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reject Refund Error: '." ". $e->getMessage());
            return redirect()->back()->with('error', 'Lỗi xử lý: ' . $e->getMessage());
        }
    }
}