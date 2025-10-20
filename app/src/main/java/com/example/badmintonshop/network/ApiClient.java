package com.example.badmintonshop.network;

import com.google.gson.Gson;
import com.google.gson.GsonBuilder;
import okhttp3.OkHttpClient;
import okhttp3.logging.HttpLoggingInterceptor;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;

public class ApiClient {
    // 1. Định nghĩa BASE_URL cố định ở đây
    private static final String BASE_URL = "http://10.0.2.2/api/BadmintonShop/";

    private static Retrofit retrofit;
    private static ApiService apiService;

    // Phương thức để lấy Retrofit instance (đã có Logging)
    public static Retrofit getRetrofitInstance(){
        if (retrofit == null) {

            // TẠO GSON CÓ CẤU HÌNH LENIENT (KHẮC PHỤC LỖI PHP/BOM)
            Gson gson = new GsonBuilder()
                    .setLenient() // KÍCH HOẠT CHẾ ĐỘ LENIENT
                    .create();

            // Cấu hình HttpLoggingInterceptor
            HttpLoggingInterceptor log = new HttpLoggingInterceptor();
            log.setLevel(HttpLoggingInterceptor.Level.BODY);

            // Tạo OkHttpClient và thêm Interceptor
            OkHttpClient client = new OkHttpClient.Builder()
                    .addInterceptor(log)
                    .build();

            // Tạo Retrofit instance
            retrofit = new Retrofit.Builder()
                    .baseUrl(BASE_URL)
                    .client(client)
                    // SỬ DỤNG GSON CÓ CẤU HÌNH LENIENT ĐÃ ĐƯỢC TẠO
                    .addConverterFactory(GsonConverterFactory.create(gson))
                    .build();
        }
        return retrofit;
    }

    // Phương thức tiện lợi để lấy thẳng ApiService
    public static ApiService getApiService() {
        if (apiService == null) {
            apiService = getRetrofitInstance().create(ApiService.class);
        }
        return apiService;
    }
}
