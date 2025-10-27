<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\File; // Không cần dùng File nữa

// ⭐ THÊM CÁC MODEL CẦN THIẾT HOẶC FACADE DB
use Illuminate\Support\Facades\DB; 

class ShippingConfigController extends Controller
{
    // ⭐ Khóa DB sẽ được sử dụng
    private $freeShipKey = 'free_ship_threshold'; 

    /**
     * Hàm helper để đọc cấu hình từ DB.
     */
    private function getSettingValue(string $key, float $default)
    {
        $setting = DB::table('app_settings')->where('key', $key)->first();
        return $setting ? (float)$setting->value : $default;
    }

    /**
     * Hàm helper để lưu hoặc cập nhật cấu hình vào DB.
     */
    private function updateSettingValue(string $key, float $value)
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'type' => 'numeric']
        );
    }

    /**
     * Hiển thị form cấu hình ngưỡng Free Ship.
     */
    public function edit()
    {
        // ⭐ SỬA: Đọc giá trị hiện tại từ DB
        $freeShipThreshold = $this->getSettingValue($this->freeShipKey, 2000000.00); 

        // Truyền giá trị sang view (Giả định views/admin/shipping/config.blade.php tồn tại)
        return view('admin.shipping.config', compact('freeShipThreshold'));
    }

    /**
     * Xử lý và lưu cấu hình Free Ship.
     */
    public function update(Request $request)
    {
        $request->validate([
            'free_ship_threshold' => 'required|numeric|min:0',
        ]);
        
        $newThreshold = (float)$request->free_ship_threshold;

        try {
            // ⭐ SỬA: Ghi trực tiếp giá trị mới vào bảng app_settings
            $this->updateSettingValue($this->freeShipKey, $newThreshold);

        } catch (\Throwable $e) {
             return redirect()->back()->withInput()
                              ->with('error', 'Lỗi server khi lưu cấu hình: ' . $e->getMessage());
        }

        return redirect()->route('admin.shipping.config.edit')
                         ->with('success', 'Đã cập nhật Ngưỡng Free Ship thành công (' . number_format($newThreshold) . 'đ).');
    }
}
