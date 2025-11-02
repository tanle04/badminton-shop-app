import org.gradle.kotlin.dsl.implementation

plugins {
    // Plugin Android Application
    alias(libs.plugins.android.application)
    // THÊM: Cần thiết cho các dự án Android/Kotlin
    alias(libs.plugins.kotlin.android)
    // THÊM: Plugin KSP (Kotlin Symbol Processing) nếu bạn dùng Room/Hilt/v.v.
    alias(libs.plugins.ksp)
}

android {
    namespace = "com.example.badmintonshop"
    compileSdk = 36

    defaultConfig {
        applicationId = "com.example.badmintonshop"
        minSdk = 30
        targetSdk = 36
        versionCode = 1
        versionName = "1.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }

    // Nâng cấp lên Java 17, vì AGP 8.13.0 và Gradle 8.x yêu cầu/khuyến nghị.
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    // THÊM: Cấu hình Kotlin
    kotlinOptions {
        jvmTarget = "17"
    }
}

dependencies {
    // SỬ DỤNG HOÀN TOÀN VERSION CATALOG (libs.)

    // AndroidX Core/UI
    implementation(libs.appcompat)
    implementation(libs.material)
    implementation(libs.activity)
    implementation(libs.constraintlayout)
    implementation(libs.recyclerview) // Bổ sung từ các dependencies trước đó

    // Retrofit & OkHttp
    implementation(libs.retrofit)
    implementation(libs.retrofit.gson)
    implementation(libs.okhttp.logging) // Sử dụng 4.12.0 (hoặc phiên bản được định nghĩa trong libs.versions.toml)

    // Image Loading & UI Components
    implementation(libs.glide)
    implementation(libs.circleindicator)
    implementation(libs.pusher) // Websocket

    // Coroutines và Lifecycle (Bổ sung từ các dependencies trước đó)
    implementation(libs.coroutines.android)
    implementation(libs.lifecycle.viewmodel)
    implementation(libs.lifecycle.livedata)

    // Room Database
    implementation(libs.room.runtime)
    implementation(libs.room.ktx)
    ksp(libs.room.compiler) // Dùng ksp() cho compiler

    // Testing
    testImplementation(libs.junit)
    androidTestImplementation(libs.ext.junit)
    androidTestImplementation(libs.espresso.core)
}
