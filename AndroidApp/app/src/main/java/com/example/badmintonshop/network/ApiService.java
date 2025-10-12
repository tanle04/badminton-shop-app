package com.example.badmintonshop.network;

import com.example.badmintonshop.network.dto.AuthLoginBody;
import com.example.badmintonshop.network.dto.AuthRegisterBody;
import com.example.badmintonshop.network.dto.AuthResponse;
import com.example.badmintonshop.network.dto.CategoryListResponse;
import com.example.badmintonshop.network.dto.ProductDto;
import com.example.badmintonshop.network.dto.ProductListResponse;
import com.example.badmintonshop.network.dto.SliderDto;

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

}
