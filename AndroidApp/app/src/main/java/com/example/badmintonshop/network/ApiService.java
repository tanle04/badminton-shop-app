package com.example.badmintonshop.network;

import com.example.badmintonshop.model.ShippingRate;
import com.example.badmintonshop.network.dto.AddressListResponse;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.AuthLoginBody;
import com.example.badmintonshop.network.dto.AuthRegisterBody;
import com.example.badmintonshop.network.dto.AuthResponse;
import com.example.badmintonshop.network.dto.BrandListResponse;
import com.example.badmintonshop.network.dto.CartResponse;
import com.example.badmintonshop.network.dto.CategoryListResponse;
// ⭐ SỬA: Import OrderDto
import com.example.badmintonshop.network.dto.OrderDto;
import com.example.badmintonshop.network.dto.OrderDetailsListResponse;
import com.example.badmintonshop.network.dto.OrderListResponse;
import com.example.badmintonshop.network.dto.OrderTrackResponse;
import com.example.badmintonshop.network.dto.ProductDetailResponse;
import com.example.badmintonshop.network.dto.ProductListResponse;
import com.example.badmintonshop.network.dto.RefundRequestBody;
import com.example.badmintonshop.network.dto.ReviewDetailsResponse;
import com.example.badmintonshop.network.dto.ReviewListResponse;
import com.example.badmintonshop.network.dto.ReviewSubmitRequest;
import com.example.badmintonshop.network.dto.ShippingRatesResponse;
import com.example.badmintonshop.network.dto.SliderDto;
import com.example.badmintonshop.network.dto.VariantListResponse;
import com.example.badmintonshop.network.dto.VoucherListResponse;
import com.example.badmintonshop.network.dto.WishlistAddRequest;
import com.example.badmintonshop.network.dto.WishlistDeleteRequest;
import com.example.badmintonshop.network.dto.WishlistGetResponse;

import java.util.List;
import java.util.Map;

import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.http.*;

public interface ApiService {

    @POST("auth/login.php")
    Call<AuthResponse> login(@Body AuthLoginBody body);

    @POST("auth/register.php")
    Call<AuthResponse> register(@Body AuthRegisterBody body);

    @GET("products/list.php")
    Call<ProductListResponse> getProducts(
            @Query("page") Integer page,
            @Query("limit") Integer limit
    );

    @GET("sliders/list.php")
    Call<List<SliderDto>> getSliders();

    @GET("products/detail.php")
    Call<ProductDetailResponse> getProductDetail(@Query("productID") int productID);


    @GET("products/search.php")
    Call<ProductListResponse> searchProducts(@Query("keyword") String keyword);

    @GET("products/filter.php")
    Call<ProductListResponse> getProductsByCategory(@Query("category") String category);

    @GET("products/filter.php")
    Call<ProductListResponse> filterProducts(
            @Query("category") String category,
            @Query("brand") String brand,
            @Query("price_min") Integer priceMin,
            @Query("price_max") Integer priceMax
    );


    @GET("categories/list.php")
    Call<CategoryListResponse> getCategories();
    @GET("brands/list.php")
    Call<BrandListResponse> getBrands();


    // --- WISHLIST API ---

    // 1. Thêm vào Wishlist (Dùng Request Body)
    @POST("wishlist/add.php")
    Call<ApiResponse> addToWishlist(@Body WishlistAddRequest request);

    // 1.1. Thêm vào Wishlist (Dùng tham số trực tiếp - cho ProductDetailActivity)
    @FormUrlEncoded
    @POST("wishlist/add.php")
    Call<ApiResponse> addToWishlist(
            @Field("customerID") int customerId,
            @Field("productID") int productId
    );

    // 2. Lấy danh sách Wishlist
    @GET("wishlist/get.php")
    Call<WishlistGetResponse> getWishlist(@Query("customerID") int customerId);

    // 3. Xóa sản phẩm khỏi Wishlist (Dùng Request Body)
    @POST("wishlist/remove.php")
    Call<ApiResponse> deleteFromWishlist(@Body WishlistDeleteRequest request);

    // 3.1. Xóa sản phẩm khỏi Wishlist (Dùng tham số trực tiếp - cho ProductDetailActivity)
    @FormUrlEncoded
    @POST("wishlist/remove.php")
    Call<ApiResponse> removeFromWishlist(
            @Field("customerID") int customerId,
            @Field("productID") int productId
    );

    // 4. Kiểm tra Trạng thái Wishlist (Phương thức bị thiếu - cho ProductDetailActivity)
    @GET("wishlist/check.php")
    Call<ApiResponse> checkWishlistStatus(
            @Query("customerID") int customerId,
            @Query("productID") int productId
    );

    // --- GIỎ HÀNG API ---

    @GET("cart/get.php")
    Call<CartResponse> getCartItems(@Query("customerID") int customerId);

    @FormUrlEncoded
    @POST("cart/add.php")
    Call<ApiResponse> addToCart(
            @Field("customerID") int customerId,
            @Field("variantID") int variantId,
            @Field("quantity") int quantity
    );

    // Phương thức mới: Thêm sản phẩm (biến thể) vào giỏ hàng
    @FormUrlEncoded
    @POST("cart/add.php")
    Call<ApiResponse> addVariantToCart(
            @Field("customerID") int customerID,
            @Field("variantID") int variantID,
            @Field("quantity") int quantity
    );

    @FormUrlEncoded
    @POST("cart/update.php")
    Call<ApiResponse> updateCartQuantity(
            @Field("customerID") int customerId,
            @Field("cartID") int cartId,
            @Field("quantity") int quantity // Gửi 0 để xóa
    );

    @GET("cart/get_variants.php")
    Call<VariantListResponse> getProductVariants(@Query("productID") int productId);

    @FormUrlEncoded
    @POST("cart/change_variant.php")
    Call<ApiResponse> changeCartItemVariant(
            @Field("customerID") int customerId,
            @Field("cartID") int cartId,
            @Field("newVariantID") int newVariantId,
            @Field("quantity") int quantity
    );

    // --- ĐỊA CHỈ API ---

    @GET("addresses/get.php")
    Call<AddressListResponse> getAddresses(@Query("customerID") int customerId);

    @FormUrlEncoded
    @POST("addresses/add.php")
    Call<ApiResponse> addAddress(
            @Field("customerID") int customerId,
            @Field("recipientName") String recipientName,
            @Field("phone") String phone,
            @Field("street") String street,
            @Field("city") String city,
            @Field("postalCode") String postalCode,
            @Field("country") String country
    );

    @FormUrlEncoded
    @POST("addresses/update.php")
    Call<ApiResponse> updateAddress(
            @Field("addressID") int addressId,
            @Field("customerID") int customerId,
            @Field("recipientName") String recipientName,
            @Field("phone") String phone,
            @Field("street") String street,
            @Field("city") String city,
            @Field("postalCode") String postalCode,
            @Field("country") String country
    );

    @FormUrlEncoded
    @POST("addresses/delete.php")
    Call<ApiResponse> deleteAddress(
            @Field("addressID") int addressId,
            @Field("customerID") int customerId
    );

    @FormUrlEncoded
    @POST("addresses/set_default.php")
    Call<ApiResponse> setDefaultAddress(
            @Field("addressID") int addressId,
            @Field("customerID") int customerId
    );

    // --- CÁC PHƯƠNG THỨC CHO CHECKOUT & ORDERS ---

    // 1. Lấy danh sách Voucher
    @GET("voucher/get_vouchers.php")
    Call<VoucherListResponse> getApplicableVouchers(
            @Query("customerID") int customerId,
            @Query("subtotal") double subtotal
    );
    @FormUrlEncoded
    @POST("voucher/redeem.php")
    Call<ApiResponse> redeemVoucher(
            @Field("voucherCode") String code,
            @Field("customerID") int customerId
    );

    // 2. Lấy danh sách Phương thức Vận chuyển (ĐÃ CHUẨN HÓA)
    @GET("shipping/get_rates.php")
    Call<ShippingRatesResponse> getShippingRates(@Query("itemsJSON") String itemsJson, @Query("addressID") int addressID);

    // 3. ĐẶT HÀNG (ĐÃ SỬA LỖI: Cập nhật chữ ký lên 8 tham số)
    @FormUrlEncoded
    @POST("orders/create.php")
    Call<ApiResponse> createOrder(
            @Field("customerID") int customerId,
            @Field("addressID") int addressId,
            @Field("paymentMethod") String paymentMethod,
            @Field("total") double total,
            @Field("items") String itemsJson,
            @Field("voucherID") int voucherId,
            @Field("selectedRateID") int selectedRateID, // ⭐ THAM SỐ THỨ 7
            @Field("shippingFee") double shippingFee // ⭐ THAM SỐ THỨ 8
    );

    @FormUrlEncoded
    @POST("orders/repay.php")
    Call<ApiResponse> repayOrder(
            @Field("customerID") int customerId,
            @Field("orderID") int orderId
    );

    @GET("orders/get_by_customer.php")
    Call<OrderListResponse> getCustomerOrders(@Query("customerID") int customerId, @Query("status") String statusFilter);

    // ⭐ SỬA: Trả về OrderDto (cả đơn hàng) thay vì OrderDetailsListResponse (chỉ danh sách item)
    // ⭐ SỬA: Thêm customerID để khớp với lệnh gọi
    @GET("orders/get_details.php")
    Call<OrderDto> getOrderDetails(
            @Query("orderID") int orderId,
            @Query("customerID") int customerId
    );
    @GET("orders/get_details.php")
    Call<ReviewDetailsResponse> getOrderDetailsReview(
            @Query("orderID") int orderId,
            @Query("customerID") int customerId
    );
    @GET("orders/get_details.php")
    Call<OrderDetailsListResponse> getOrderDetailsForReview(
            @Query("orderID") int orderId,
            @Query("customerID") int customerId
    );
    // (Thêm vào bên trong interface ApiService)

    @GET("orders/track.php")
    Call<OrderTrackResponse> trackOrder(
            @Query("orderID") int orderID,
            @Query("customerID") int customerID
    );

    /* * ⭐ MỚI: API để gửi yêu cầu trả hàng
     */
    @Multipart
    @POST("refunds/request_refund.php")
    Call<ApiResponse> submitRefundRequestMultipart(
            @Part("refund_data") RequestBody refundDataJson, // Dữ liệu JSON (text)
            @Part List<MultipartBody.Part> photos,           // Danh sách file ảnh
            @Part List<MultipartBody.Part> videos            // Danh sách file video
    );
    // Phương thức HỦY đơn hàng
    @FormUrlEncoded
    @POST("orders/cancel.php")
    Call<ApiResponse> cancelOrder(
            @Field("customerID") int customerId,
            @Field("orderID") int orderId
    );


    // --- REVIEW API ---

    @Multipart
    @POST("reviews/submit_reviews.php")
    Call<ApiResponse> submitReviewsMultipart(
            @Part("review_data") RequestBody reviewDataJson,
            @Part List<MultipartBody.Part> photos,
            @Part List<MultipartBody.Part> videos
    );

    @GET("reviews/get_by_product.php")
    Call<ReviewListResponse> getReviewsByProduct(
            @Query("productID") int productID,
            @Query("rating") int ratingFilter // ratingFilter = 0 cho tất cả
    );

    /**
     * GĐ 1: Yêu cầu gửi mã OTP đến email
     */
    @POST("auth/password-request-otp.php")
    Call<ApiResponse> requestPasswordOtp(@Body Map<String, String> body);

    /**
     * GĐ 2: Đặt lại mật khẩu bằng OTP
     */
    @POST("auth/password-reset.php")
    Call<ApiResponse> resetPassword(@Body Map<String, String> body);
}