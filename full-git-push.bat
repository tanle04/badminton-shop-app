chcp 65001 >nul

@echo off
echo ==========================================
echo ???  QUY TRINH DONG BO VA DAY CODE LÊN GIT  ???
echo ==========================================

:: ------------------------------------------------
:: B1: DONG BO CODE API (Tu XAMPP sang Git)
:: ------------------------------------------------
echo.
echo === [1/3] DONG BO BACKEND (API) ===
set SOURCE_API=C:\xampp\htdocs\api\BadmintonShop
set TARGET_API=C:\BadmintonShop\BackendApi\BadmintonShop

xcopy "%SOURCE_API%" "%TARGET_API%" /E /H /Y /I >nul
if %ERRORLEVEL% EQU 0 (echo ? API: DONG BO THANH CONG!) else (echo ? API: LOI DONG BO!)

:: ------------------------------------------------
:: B2: DONG BO CODE ANDROID (Tu Android Studio sang Git)
:: ------------------------------------------------
echo.
echo === [2/3] DONG BO ANDROID ===
set SOURCE_ANDROID=C:\Users\Acer\AndroidStudioProjects\BadmintonShop
set TARGET_ANDROID=C:\BadmintonShop\AndroidApp

xcopy "%SOURCE_ANDROID%" "%TARGET_ANDROID%" /E /H /Y /I >nul
if %ERRORLEVEL% EQU 0 (echo ? Android: DONG BO THANH CONG!) else (echo ? Android: LOI DONG BO!)

:: ------------------------------------------------
:: B3: THUC HIEN LỆNH GIT
:: ------------------------------------------------
echo.
echo === [3/3] CHUAN BI GIT PUSH ===

:: Yeu cau nguoi dung nhap thong diep commit
set /p commit_msg="Nhap noi dung commit: "

:: Thuc hien cac lenh Git
git add .
git commit -m "%commit_msg%"
git push origin main

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ?  TOAN BO DU AN DA DUOC PUSH THANH CONG!
) else (
    echo.
    echo ?  LOI TRONG QUA TRINH GIT PUSH. KIEM TRA LAI!
)

echo.
echo ==========================================
pause