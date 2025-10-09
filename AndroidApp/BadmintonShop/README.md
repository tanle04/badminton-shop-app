\# Badminton Shop (Android – Java)



\## Tech

\- Android Studio (Koala/Jellyfish), Gradle wrapper

\- SQLite (seed bằng `BadmintonDb`)

\- Repository pattern (`ProductRepo`)

\- UI: Toolbar + BottomNavigation + 4 RecyclerViews (Coming soon / Featured / Best selling / New arrivals)



\## Run

1\. Open project in Android Studio

2\. Build > Clean Project

3\. Run on emulator (API 30+)

4\. Nếu đổi schema: tăng `DB\_VERSION` (trong `BadmintonDb`) hoặc gỡ app để seed lại DB.



\## Slot 1 (đã xong)

\- Home screen + SQLite seed + repos + adapter

\- `.gitattributes` \& `.gitignore` sạch cho Android



\## Roadmap (Slot 2)

\- Retrofit + OkHttp + Logging Interceptor

\- Bind API thật cho Home (giữ SQLite làm fallback cache)

\- Product Detail

\- Polish UI (empty/error state, spacing, theme)



