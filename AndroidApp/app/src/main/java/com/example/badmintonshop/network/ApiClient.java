package com.example.badmintonshop.network;

import com.google.gson.Gson;
import com.google.gson.GsonBuilder;
import okhttp3.OkHttpClient;
import okhttp3.logging.HttpLoggingInterceptor;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;

public class ApiClient {
    // ✅ URL này đã đúng
    private static final String BASE_URL = "https://tanbadminton.id.vn/api/";
    public static final String BASE_STORAGE_URL = "https://tanbadminton.id.vn/admin/public/storage/";
    private static Retrofit retrofit;
    private static ApiService apiService;

    // Phương thức để lấy Retrofit instance (đã có Logging)
    public static Retrofit getRetrofitInstance(){
        if (retrofit == null) {

            // TẠO GSON CÓ CẤU HÌNH LENIENT
            Gson gson = new GsonBuilder()
                    .setLenient()
                    .create();

            // Cấu hình HttpLoggingInterceptor
            HttpLoggingInterceptor log = new HttpLoggingInterceptor();
            log.setLevel(HttpLoggingInterceptor.Level.BODY);

            // ✅ QUAY LẠI PHIÊN BẢN GỐC (AN TOÀN)
            OkHttpClient client = new OkHttpClient.Builder()
                    .addInterceptor(log)
                    .build(); // Xóa .Builder() không an toàn đi

            // Tạo Retrofit instance
            retrofit = new Retrofit.Builder()
                    .baseUrl(BASE_URL)
                    .client(client) // Dùng client an toàn
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

    // ✅ XÓA BỎ HOÀN TOÀN HÀM 'getUnsafeOkHttpClientBuilder()'
}