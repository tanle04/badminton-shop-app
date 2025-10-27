<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingCarrier;
use Illuminate\Http\Request;

class ShippingCarrierController extends Controller
{
    /** Hiển thị danh sách các Đơn vị Vận chuyển. */
    public function index()
    {
        $carriers = ShippingCarrier::orderBy('carrierID', 'desc')->paginate(10);
        return view('admin.carriers.index', compact('carriers'));
    }

    /** Hiển thị form tạo mới. */
    public function create()
    {
        return view('admin.carriers.create');
    }

    /** Lưu bản ghi mới vào DB. */
    public function store(Request $request)
    {
        $request->validate([
            'carrierName' => 'required|string|max:255|unique:shipping_carriers,carrierName',
            'isActive' => 'boolean',
        ]);

        ShippingCarrier::create([
            'carrierName' => $request->carrierName,
            'isActive' => $request->isActive ?? 0,
        ]);

        // ⭐ SỬA LỖI: Thêm tiền tố 'admin.' vào tên route
        return redirect()->route('admin.carriers.index')->with('success', 'Đã thêm Đơn vị Vận chuyển thành công.');
    }

    /** Hiển thị chi tiết (Có thể không cần). */
    public function show(ShippingCarrier $carrier)
    {
        return view('admin.carriers.show', compact('carrier'));
    }

    /** Hiển thị form chỉnh sửa. */
    public function edit(ShippingCarrier $carrier)
    {
        return view('admin.carriers.edit', compact('carrier'));
    }

    /** Cập nhật bản ghi trong DB. */
    public function update(Request $request, ShippingCarrier $carrier)
    {
        $request->validate([
            'carrierName' => 'required|string|max:255|unique:shipping_carriers,carrierName,' . $carrier->carrierID . ',carrierID',
            'isActive' => 'boolean',
        ]);

        $carrier->update([
            'carrierName' => $request->carrierName,
            'isActive' => $request->isActive ?? 0,
        ]);

        // ⭐ SỬA LỖI: Thêm tiền tố 'admin.' vào tên route
        return redirect()->route('admin.carriers.index')->with('success', 'Cập nhật Đơn vị Vận chuyển thành công.');
    }

    /** Xóa bản ghi. */
    public function destroy(ShippingCarrier $carrier)
    {
        try {
            $carrier->delete();
            // ⭐ SỬA LỖI: Thêm tiền tố 'admin.' vào tên route
            return redirect()->route('admin.carriers.index')->with('success', 'Đã xóa Đơn vị Vận chuyển thành công.');
        } catch (\Exception $e) {
            // ⭐ SỬA LỖI: Thêm tiền tố 'admin.' vào tên route
            return redirect()->route('admin.carriers.index')->with('error', 'Không thể xóa do ràng buộc dữ liệu (rates).');
        }
    }
}