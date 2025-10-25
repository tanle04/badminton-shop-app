<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher; // Model Voucher
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VoucherController extends Controller
{
    // LƯU Ý: Các route này được bảo vệ bởi Gate 'can:marketing' hoặc 'can:admin'

    /**
     * Hiển thị danh sách tất cả Vouchers.
     */
    public function index()
    {
        $vouchers = Voucher::latest('voucherID')->paginate(10);
        return view('admin.vouchers.index', compact('vouchers'));
    }

    /**
     * Hiển thị form tạo Voucher mới.
     */
    public function create()
    {
        return view('admin.vouchers.create');
    }

    /**
     * Lưu trữ Voucher mới vào DB.
     */
    public function store(Request $request)
    {
        $request->validate($this->voucherValidationRules($request));

        // Lấy dữ liệu và xử lý checkbox (isActive, isPrivate)
        $data = $request->all();
        $data['isActive'] = $request->has('isActive');
        $data['isPrivate'] = $request->has('isPrivate');

        Voucher::create($data);

        return redirect()->route('admin.vouchers.index')
                         ->with('success', 'Mã giảm giá mới đã được tạo thành công!');
    }
    
    /**
     * Hiển thị form chỉnh sửa Voucher.
     */
    public function edit(Voucher $voucher)
    {
        return view('admin.vouchers.edit', compact('voucher'));
    }

    /**
     * Cập nhật Voucher đã tồn tại.
     */
    public function update(Request $request, Voucher $voucher)
    {
        // Sử dụng voucherValidationRules và bỏ qua ID của voucher đang sửa
        $request->validate($this->voucherValidationRules($request, $voucher->voucherID));

        // Lấy dữ liệu và xử lý checkbox (isActive, isPrivate)
        $data = $request->all();
        $data['isActive'] = $request->has('isActive');
        $data['isPrivate'] = $request->has('isPrivate');

        $voucher->update($data);

        return redirect()->route('admin.vouchers.index')
                         ->with('success', 'Mã giảm giá đã được cập nhật thành công!');
    }

    /**
     * Xóa Voucher khỏi DB.
     */
    public function destroy(Voucher $voucher)
    {
        try {
            // Xóa Voucher (Order FK sẽ tự động set NULL nhờ DB config)
            $voucher->delete();
            return redirect()->route('admin.vouchers.index')
                             ->with('success', 'Mã giảm giá đã được xóa.');
        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Không thể xóa mã giảm giá này: ' . $e->getMessage());
        }
    }
    
    /**
     * Hàm phụ trợ định nghĩa quy tắc Validation cho Voucher.
     */
    protected function voucherValidationRules(Request $request, $ignoreId = null)
    {
        return [
            'voucherCode' => [
                'required',
                'string',
                'max:50',
                // Đảm bảo voucherCode là duy nhất, loại trừ voucher đang được sửa (nếu có)
                Rule::unique('vouchers', 'voucherCode')->ignore($ignoreId, 'voucherID'),
            ],
            'description' => 'nullable|string|max:255',
            'discountType' => 'required|in:percentage,fixed',
            'discountValue' => 'required|numeric|min:1',
            'minOrderValue' => 'required|numeric|min:0',
            'maxDiscountAmount' => [
                'nullable', 
                'numeric', 
                // Yêu cầu maxDiscountAmount nếu discountType là percentage
                Rule::requiredIf($request->discountType == 'percentage'),
                'gt:0'
            ],
            'maxUsage' => 'required|integer|min:1',
            // Sử dụng định dạng datetime-local (Y-m-d\TH:i)
            'startDate' => 'required|date|before_or_equal:endDate',
            'endDate' => 'required|date|after_or_equal:startDate',
        ];
    }
}