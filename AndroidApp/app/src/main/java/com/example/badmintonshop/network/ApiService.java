package com.example.badmintonshop.network;

import com.example.badmintonshop.model.ShippingRate;
import com.example.badmintonshop.network.dto.AddressListResponse;
import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.AuthLoginBody;
import com.example.badmintonshop.network.dto.AuthRegisterBody;
import com.example.badmintonshop.network.dto.AuthResponse;
import com.example.badmintonshop.network.dto.CartResponse;
import com.example.badmintonshop.network.dto.CategoryListResponse;
import com.example.badmintonshop.network.dto.OrderListResponse;
import com.example.badmintonshop.network.dto.ProductDetailResponse; // ⭐ IMPORT MỚI
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.network.dto.ProductListResponse;
import com.example.badmintonshop.network.dto.SliderDto;
import com.example.badmintonshop.network.dto.VariantListResponse;
import com.example.badmintonshop.network.dto.VoucherListResponse;
import com.example.badmintonshop.network.dto.WishlistAddRequest;
import com.example.badmintonshop.network.dto.WishlistDeleteRequest;
import com.example.badmintonshop.network.dto.WishlistGetResponse;

import java.util.List;

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

    // ⭐ SỬA LỖI KIỂU DỮ LIỆU: Phải trả về ProductDetailResponse để khớp với Callback trong Activity
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


    // 1. Thêm vào Wishlist
    @POST("wishlist/add.php")
    Call<ApiResponse> addToWishlist(@Body WishlistAddRequest request);

    // 2. Lấy danh sách Wishlist
    @GET("wishlist/get.php")
    Call<WishlistGetResponse> getWishlist(@Query("customerID") int customerId);

    // Thêm phương thức xóa sản phẩm khỏi Wishlist
    @POST("wishlist/remove.php")
    Call<ApiResponse> deleteFromWishlist(@Body WishlistDeleteRequest request);

    // Giỏ hàng
    @GET("cart/get.php")
    Call<CartResponse> getCartItems(@Query("customerID") int customerId);
    @FormUrlEncoded
    @POST("cart/add.php")
    Call<ApiResponse> addToCart(
            @Field("customerID") int customerId,
            @Field("variantID") int variantId,
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

    // Địa chỉ
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

    // --- CÁC PHƯƠNG THỨC CHO CHECKOUT ---

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

    // 2. Lấy danh sách Phương thức Vận chuyển
    @GET("shipping/get_rates.php")
    Call<List<ShippingRate>> getShippingRates();

    // 3. ĐẶT HÀNG
    @FormUrlEncoded
    @POST("orders/create.php")
    Call<ApiResponse> createOrder(
            @Field("customerID") int customerId,
            @Field("addressID") int addressId,
            @Field("paymentMethod") String paymentMethod,
            @Field("total") double total,
            @Field("items") String itemsJson,
            @Field("voucherID") int voucherId
    );
    @GET("orders/get_by_customer.php")
    Call<OrderListResponse> getCustomerOrders(@Query("customerID") int customerId, @Query("status") String statusFilter);

}