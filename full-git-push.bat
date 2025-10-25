chcp 65001 >nul

@echo off
echo ==========================================
echo ???  QUY TRINH DONG BO VA DAY CODE LÊN GIT  ???
echo ==========================================

:: ĐỊNH NGHĨA BIẾN GỐC CỦA DỰ ÁN (THƯ MỤC NƠI CHỨA .git VÀ CÁC THƯ MỤC CODE)
:: Thay thế C:\BadmintonShop bằng đường dẫn thư mục Git Repository thực tế của bạn
set GIT_TARGET_ROOT=C:\BadmintonShop

:: ------------------------------------------------
:: B1: DONG BO CODE API PHP GỐC (BackendApi)
:: ------------------------------------------------
echo.
echo === [1/4] DONG BO BACKEND (API PHP GOC) ===
set SOURCE_API=C:\xampp\htdocs\api\BadmintonShop
set TARGET_API=%GIT_TARGET_ROOT%\BackendApi\BadmintonShop

xcopy "%SOURCE_API%" "%TARGET_API%" /E /H /Y /I >nul
if %ERRORLEVEL% EQU 0 (echo [? API]: DONG BO THANH CONG!) else (echo [? API]: LOI DONG BO!)

:: ------------------------------------------------
:: B2: DONG BO CODE ADMIN LARAVEL (AdminPanel)
:: ------------------------------------------------
echo.
echo === [2/4] DONG BO ADMIN (Laravel) ===
:: Nguồn là thư mục Admin đang chạy trong XAMPP
set SOURCE_ADMIN=C:\xampp\htdocs\badminton_shop_admin
:: Đích là thư mục mới bạn muốn đặt trong Git Repository
set TARGET_ADMIN=%GIT_TARGET_ROOT%\AdminPanel

xcopy "%SOURCE_ADMIN%" "%TARGET_ADMIN%" /E /H /Y /I >nul
if %ERRORLEVEL% EQU 0 (echo [? ADMIN]: DONG BO THANH CONG!) else (echo [? ADMIN]: LOI DONG BO!)

:: ------------------------------------------------
:: B3: DONG BO CODE ANDROID (AndroidApp)
:: ------------------------------------------------
echo.
echo === [3/4] DONG BO ANDROID ===
set SOURCE_ANDROID=C:\Users\Acer\AndroidStudioProjects\BadmintonShop
set TARGET_ANDROID=%GIT_TARGET_ROOT%\AndroidApp

xcopy "%SOURCE_ANDROID%" "%TARGET_ANDROID%" /E /H /Y /I >nul
if %ERRORLEVEL% EQU 0 (echo [? ANDROID]: DONG BO THANH CONG!) else (echo [? ANDROID]: LOI DONG BO!)

:: ------------------------------------------------
:: B4: THUC HIEN LỆNH GIT
:: ------------------------------------------------
echo.
echo === [4/4] CHUAN BI GIT PUSH ===

:: Yeu cau nguoi dung nhap thong diep commit
set /p commit_msg="Nhap noi dung commit: "

:: Chuyển đến thư mục gốc của Git Repository
cd "%GIT_TARGET_ROOT%"

:: Thuc hien cac lenh Git
git add .
git commit -m "%commit_msg%"
git push origin main

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ?  TOAN BO DU AN DA DUOC PUSH THANH CONG!
) else (
    echo.
    echo ?  LOI TRONG QUA TRINH GIT PUSH. KIEM TRA LAI!
)

echo.
echo ==========================================
pause