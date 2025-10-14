package com.example.badmintonshop.network;

import okhttp3.OkHttpClient;
import okhttp3.logging.HttpLoggingInterceptor;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;

public class ApiClient {
    // 1. Định nghĩa BASE_URL cố định ở đây
    // Sử dụng 10.0.2.2 cho máy ảo Android Studio, hoặc IP của máy tính nếu dùng máy thật
    private static final String BASE_URL = "http://10.0.2.2/api/BadmintonShop/";

    private static Retrofit retrofit;
    private static ApiService apiService;

    // 2. Phương thức không cần tham số nữa
    public static Retrofit getRetrofitInstance(){
        if (retrofit == null) {
            HttpLoggingInterceptor log = new HttpLoggingInterceptor();
            log.setLevel(HttpLoggingInterceptor.Level.BODY);
            OkHttpClient client = new OkHttpClient.Builder()
                    .addInterceptor(log).build();

            retrofit = new Retrofit.Builder()
                    .baseUrl(BASE_URL) // 3. Luôn dùng BASE_URL đã định nghĩa
                    .client(client)
                    .addConverterFactory(GsonConverterFactory.create())
                    .build();
        }
        return retrofit;
    }

    // 4. Tạo một phương thức tiện lợi để lấy thẳng ApiService
    public static ApiService getApiService() {
        if (apiService == null) {
            apiService = getRetrofitInstance().create(ApiService.class);
        }
        return apiService;
    }
}