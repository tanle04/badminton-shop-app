<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VoucherController extends Controller
{
    // ============================================================================
    // WEB VIEWS
    // ============================================================================
    
    public function index()
    {
        return view('admin.vouchers.index');
    }

    public function create()
    {
        return view('admin.vouchers.create');
    }

    public function edit(Voucher $voucher)
    {
        return view('admin.vouchers.edit', compact('voucher'));
    }

    // ============================================================================
    // API ENDPOINTS (CHO AJAX)
    // ============================================================================
    
    /**
     * 🔍 API: Lấy danh sách vouchers với search & filter
     */
    public function apiIndex(Request $request)
    {
        $query = Voucher::query();

        // === 🔍 SEARCH ===
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('voucherCode', 'LIKE', "%{$search}%")
                  ->orWhere('voucherName', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // === 🎯 FILTERS ===
        
        // Filter theo trạng thái
        if ($status = $request->get('status')) {
            $now = now();
            
            switch ($status) {
                case 'active':
                    $query->where('isActive', true)
                          ->where('startDate', '<=', $now)
                          ->where('endDate', '>=', $now);
                    break;
                    
                case 'inactive':
                    $query->where('isActive', false);
                    break;
                    
                case 'expired':
                    $query->where('endDate', '<', $now);
                    break;
                    
                case 'upcoming':
                    $query->where('startDate', '>', $now);
                    break;
            }
        }

        // Filter theo loại giảm giá
        if ($type = $request->get('type')) {
            $query->where('discountType', $type);
        }

        // Filter theo phạm vi (public/private)
        if ($scope = $request->get('scope')) {
            if ($scope === 'public') {
                $query->where('isPrivate', false);
            } elseif ($scope === 'private') {
                $query->where('isPrivate', true);
            }
        }

        // Filter theo khoảng thời gian
        if ($request->has('date_from')) {
            $query->where('startDate', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('endDate', '<=', $request->date_to);
        }

        // === 📊 SORTING ===
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        
        // Validate sort column
        $allowedSorts = ['voucherCode', 'voucherName', 'startDate', 'endDate', 'created_at', 'discountValue', 'usedCount'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // === 📄 PAGINATION ===
        $perPage = $request->get('per_page', 15);
        $vouchers = $query->paginate($perPage);

        return response()->json($vouchers);
    }

    /**
     * 📊 API: Lấy thống kê vouchers
     */
    public function apiStats()
    {
        $now = now();
        
        $stats = [
            'total' => Voucher::count(),
            
            'active' => Voucher::where('isActive', true)
                              ->where('startDate', '<=', $now)
                              ->where('endDate', '>=', $now)
                              ->count(),
            
            'expired' => Voucher::where('endDate', '<', $now)->count(),
            
            'inactive' => Voucher::where('isActive', false)->count(),
            
            'upcoming' => Voucher::where('startDate', '>', $now)->count(),
        ];

        return response()->json($stats);
    }

    // ============================================================================
    // CRUD OPERATIONS
    // ============================================================================
    
    public function store(Request $request)
    {
        $validated = $request->validate($this->voucherValidationRules($request));

        // Xử lý checkboxes
        $validated['isActive'] = $request->has('isActive');
        $validated['isPrivate'] = $request->has('isPrivate');

        Voucher::create($validated);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Mã giảm giá mới đã được tạo thành công!');
    }

    public function update(Request $request, Voucher $voucher)
    {
        $validated = $request->validate($this->voucherValidationRules($request, $voucher->voucherID));

        // Xử lý checkboxes
        $validated['isActive'] = $request->has('isActive');
        $validated['isPrivate'] = $request->has('isPrivate');

        $voucher->update($validated);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Mã giảm giá đã được cập nhật thành công!');
    }

    public function destroy(Voucher $voucher)
    {
        try {
            $code = $voucher->voucherCode;
            $voucher->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Đã xóa voucher {$code}"
                ]);
            }

            return redirect()
                ->route('admin.vouchers.index')
                ->with('success', 'Mã giảm giá đã được xóa.');
                
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Không thể xóa mã giảm giá: ' . $e->getMessage());
        }
    }

    /**
     * 🔄 Bật/tắt voucher
     */
    public function toggleActive(Voucher $voucher)
    {
        try {
            $voucher->isActive = !$voucher->isActive;
            $voucher->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công.',
                'isActive' => $voucher->isActive
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    // ============================================================================
    // VALIDATION RULES
    // ============================================================================
    
    protected function voucherValidationRules(Request $request, $ignoreId = null)
    {
        return [
            'voucherCode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vouchers', 'voucherCode')->ignore($ignoreId, 'voucherID'),
            ],
            'voucherName' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'discountType' => 'required|in:percentage,fixed',
            'discountValue' => 'required|numeric|min:1',
            'minOrderValue' => 'required|numeric|min:0',
            'maxDiscountAmount' => [
                'nullable',
                'numeric',
                Rule::requiredIf($request->discountType == 'percentage'),
                'gt:0'
            ],
            'maxUsage' => 'required|integer|min:1',
            'startDate' => 'required|date|before_or_equal:endDate',
            'endDate' => 'required|date|after_or_equal:startDate',
        ];
    }
    
    /**
     * 🔧 Override boot để tự động set voucherName nếu chưa có
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($voucher) {
            if (empty($voucher->voucherName)) {
                $voucher->voucherName = $voucher->voucherCode;
            }
        });
    }
}