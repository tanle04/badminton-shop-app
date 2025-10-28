<?php
// utils/price_calculator.php

/**
 * Tính toán giá bán cuối cùng của một biến thể sản phẩm, áp dụng sale tốt nhất.
 * @param mysqli $mysqli Connection object.
 * @param int $variantID ID của biến thể sản phẩm.
 * @return array ['salePrice', 'originalPrice', 'isDiscounted', 'discountID']
 */
function get_best_sale_price($mysqli, $variantID) {
    
    // 1. Lấy giá gốc và thông tin sản phẩm liên quan (productID, categoryID, brandID)
    $sql_info = "SELECT 
        pv.price AS originalPrice,
        p.productID, 
        p.categoryID, 
        p.brandID
    FROM product_variants pv
    JOIN products p ON pv.productID = p.productID
    WHERE pv.variantID = ?";

    $stmt_info = $mysqli->prepare($sql_info);
    if (!$stmt_info) {
        // Trả về giá gốc nếu truy vấn thất bại
        return ['salePrice' => 0, 'originalPrice' => 0, 'isDiscounted' => false, 'discountID' => null];
    }
    
    $stmt_info->bind_param("i", $variantID);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();
    $info = $result_info->fetch_assoc();
    $stmt_info->close();

    if (!$info) {
        return ['salePrice' => 0, 'originalPrice' => 0, 'isDiscounted' => false, 'discountID' => null];
    }

    $originalPrice = (float)$info['originalPrice'];
    $salePrice = $originalPrice;
    $discountID = null;
    $isDiscounted = false;
    
    // ⭐ SỬA LỖI TIMEZONE: BẮT BUỘC SET MÚI GIỜ Ở ĐÂY
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $now = date('Y-m-d H:i:s'); // Bây giờ $now sẽ là 14:52 (chính xác)
    
    // 2. TÌM KIẾM CÁC CHƯƠNG TRÌNH SALE ĐANG HOẠT ĐỘNG
    // (Ưu tiên: VariantID, ProductID, BrandID, CategoryID)
    $sql_discounts = "SELECT * FROM product_discounts 
        WHERE isActive = 1 
        AND startDate <= ? AND endDate >= ?
        AND (
            (appliedToType = 'variant' AND appliedToID = ?) OR
            (appliedToType = 'product' AND appliedToID = ?) OR
            (appliedToType = 'brand' AND appliedToID = ?) OR
            (appliedToType = 'category' AND appliedToID = ?)
        )
        ORDER BY discountValue DESC"; // Order by để lấy mức giảm lớn nhất trước

    $stmt_disc = $mysqli->prepare($sql_discounts);
    $stmt_disc->bind_param("ssiiii", 
        $now, 
        $now, 
        $variantID, 
        $info['productID'], 
        $info['brandID'], 
        $info['categoryID']
    );
    $stmt_disc->execute();
    $result_disc = $stmt_disc->get_result();
    
    // Phép so sánh bây giờ sẽ là:
    // endDate ('13:57') >= $now ('14:52')
    // -> FALSE (KHÔNG CÓ SALE)
    
    $lowestPrice = $originalPrice;
    $bestDiscount = null;

    while ($discount = $result_disc->fetch_assoc()) {
        $currentPrice = $originalPrice;
        $discountValue = (float)$discount['discountValue'];
        $maxDiscountAmount = (float)$discount['maxDiscountAmount'];

        if ($discount['discountType'] === 'percentage') {
            $discountAmount = $originalPrice * ($discountValue / 100);
            
            if ($maxDiscountAmount > 0 && $discountAmount > $maxDiscountAmount) {
                $discountAmount = $maxDiscountAmount;
            }
            $currentPrice = $originalPrice - $discountAmount;

        } elseif ($discount['discountType'] === 'fixed') {
            $currentPrice = $originalPrice - $discountValue;
        }

        // Chọn mức giá thấp nhất (Sale tốt nhất)
        if ($currentPrice < $lowestPrice) {
            $lowestPrice = $currentPrice;
            $bestDiscount = $discount;
        }
    }
    $stmt_disc->close();

    if ($bestDiscount) {
        $salePrice = max(0, $lowestPrice); // Đảm bảo giá không âm
        $discountID = (int)$bestDiscount['discountID'];
        $isDiscounted = true;
    }
    
    // Vì $bestDiscount sẽ là NULL, $salePrice sẽ giữ nguyên là $originalPrice
    return [
        'salePrice' => round($salePrice, 2),
        'originalPrice' => $originalPrice,
        'isDiscounted' => $isDiscounted,
        'discountID' => $discountID
    ];
}


/**
 * Tìm Variant có giá bán thấp nhất sau khi áp dụng sale cho một Product.
 * Hàm này dùng cho API Danh sách sản phẩm (list.php).
 * @param mysqli $mysqli Connection object.
 * @param int $productID ID của sản phẩm.
 * @return array ['salePrice', 'originalPrice', 'isDiscounted']
 */
function get_best_sale_price_for_product_list($mysqli, $productID) {
    // 1. Lấy tất cả Variants của Product đó
    $sql_variants = "
        SELECT variantID, price 
        FROM product_variants 
        WHERE productID = ? 
        ORDER BY price ASC";

    $stmt_variants = $mysqli->prepare($sql_variants);
    if (!$stmt_variants) {
        return ['salePrice' => 0, 'originalPrice' => 0, 'isDiscounted' => false];
    }
    
    $stmt_variants->bind_param("i", $productID);
    $stmt_variants->execute();
    $result_variants = $stmt_variants->get_result();
    
    $lowestSalePrice = PHP_FLOAT_MAX;
    $lowestOriginalPrice = PHP_FLOAT_MAX;
    $anyDiscounted = false;
    
    // 2. Vòng lặp qua từng Variant và tính giá sale tốt nhất của nó
    while ($variant = $result_variants->fetch_assoc()) {
        $variantID = (int)$variant['variantID'];
        $originalPrice = (float)$variant['price']; // Giá gốc của variant
        
        // Dùng hàm tính giá sale đã định nghĩa ở trên
        // Hàm này (ở trên) đã được sửa lỗi timezone
        $price_details = get_best_sale_price($mysqli, $variantID);
        
        $currentSalePrice = $price_details['salePrice'];
        
        // Cập nhật giá thấp nhất sau sale
        // Mục đích: Tìm Variant có salePrice thấp nhất trong tất cả Variants.
        if ($currentSalePrice < $lowestSalePrice) {
            $lowestSalePrice = $currentSalePrice;
            // Lấy giá gốc tương ứng với variant có salePrice thấp nhất
            $lowestOriginalPrice = $originalPrice; 
        }
        
        // Ghi lại nếu bất kỳ variant nào có sale
        if ($price_details['isDiscounted']) {
            $anyDiscounted = true;
        }
    }
    
    $stmt_variants->close();

    if ($lowestSalePrice === PHP_FLOAT_MAX) {
        return ['salePrice' => 0, 'originalPrice' => 0, 'isDiscounted' => false];
    }
    
    return [
        'salePrice' => round($lowestSalePrice, 2),
        'originalPrice' => round($lowestOriginalPrice, 2), 
        'isDiscounted' => $anyDiscounted
    ];
}
?>