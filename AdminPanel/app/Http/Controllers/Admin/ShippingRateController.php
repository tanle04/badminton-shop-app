<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use App\Models\ShippingCarrier; // Cần dùng để lấy danh sách Carrier
use Illuminate\Http\Request;

class ShippingRateController extends Controller
{
    /** Hiển thị danh sách các Mức phí Vận chuyển. */
    public function index()
    {
        $rates = ShippingRate::with('carrier')->orderBy('rateID', 'desc')->paginate(10);
        return view('admin.rates.index', compact('rates'));
    }

    /** Hiển thị form tạo mới. */
    public function create()
    {
        // Lấy danh sách Carriers đang hoạt động cho dropdown
        $carriers = ShippingCarrier::where('isActive', 1)->get();
        return view('admin.rates.create', compact('carriers'));
    }

    /** Lưu bản ghi mới vào DB. */
    public function store(Request $request)
    {
        $request->validate([
            'carrierID' => 'required|exists:shipping_carriers,carrierID',
            'serviceName' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'estimatedDelivery' => 'required|string|max:100',
        ]);

        ShippingRate::create($request->only([
            'carrierID', 'serviceName', 'price', 'estimatedDelivery'
        ]));

        // ⭐ SỬA LỖI: Thêm tiền tố 'admin.'
        return redirect()->route('admin.rates.index')->with('success', 'Đã thêm Mức phí Vận chuyển thành công.');
    }

    /** Hiển thị form chỉnh sửa. */
    public function edit(ShippingRate $rate)
    {
        $carriers = ShippingCarrier::where('isActive', 1)->get();
        return view('admin.rates.edit', compact('rate', 'carriers'));
    }

    /** Cập nhật bản ghi trong DB. */
    public function update(Request $request, ShippingRate $rate)
    {
        $request->validate([
            'carrierID' => 'required|exists:shipping_carriers,carrierID',
            'serviceName' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'estimatedDelivery' => 'required|string|max:100',
        ]);

        $rate->update($request->only([
            'carrierID', 'serviceName', 'price', 'estimatedDelivery'
        ]));

        // ⭐ SỬA LỖI: Thêm tiền tố 'admin.'
        return redirect()->route('admin.rates.index')->with('success', 'Cập nhật Mức phí Vận chuyển thành công.');
    }

    /** Xóa bản ghi. */
    public function destroy(ShippingRate $rate)
    {
        $rate->delete();
        // ⭐ SỬA LỖI: Thêm tiền tố 'admin.'
        return redirect()->route('admin.rates.index')->with('success', 'Đã xóa Mức phí Vận chuyển thành công.');
    }
}