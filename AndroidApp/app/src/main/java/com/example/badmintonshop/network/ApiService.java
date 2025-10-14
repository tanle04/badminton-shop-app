package com.example.badmintonshop.network;

import com.example.badmintonshop.network.dto.ApiResponse;
import com.example.badmintonshop.network.dto.AuthLoginBody;
import com.example.badmintonshop.network.dto.AuthRegisterBody;
import com.example.badmintonshop.network.dto.AuthResponse;
import com.example.badmintonshop.network.dto.CartResponse;
import com.example.badmintonshop.network.dto.CategoryListResponse;
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.network.dto.ProductListResponse;
import com.example.badmintonshop.network.dto.SliderDto;
import com.example.badmintonshop.network.dto.VariantListResponse;
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
    @GET("products/detail.php")
    Call<ProductDto> getProductDetail(@Query("productID") int productID);


    @GET("products/search.php")
    Call<ProductListResponse> searchProducts(@Query("keyword") String keyword);
    @GET("products/filter.php")
    Call<ProductListResponse> getProductsByCategory(@Query("category") String category);

    @GET("categories/list.php")
    Call<CategoryListResponse> getCategories();

    // 1. Thêm vào Wishlist (SỬA: THÊM THƯ MỤC 'wishlist/')
    @POST("wishlist/add.php")
    Call<ApiResponse> addToWishlist(@Body WishlistAddRequest request);

    // 2. Lấy danh sách Wishlist (SỬA: THÊM THƯ MỤC 'wishlist/')
    @GET("wishlist/get.php")
    Call<WishlistGetResponse> getWishlist(@Query("customerID") int customerId);
    // Thêm phương thức xóa sản phẩm khỏi Wishlist
    @POST("wishlist/remove.php")
    Call<ApiResponse> deleteFromWishlist(@Body WishlistDeleteRequest request);
    // SỬA LẠI ĐƯỜNG DẪN Ở ĐÂY
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

}
