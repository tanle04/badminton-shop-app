chcp 65001 >nul

@echo off
echo ==========================================
echo ??  DANG DONG BO BACKEND TU XAMPP SANG GIT...
echo ==========================================

:: Nguon: Thu muc XAMPP - noi ban code truc tiep
set SOURCE=C:\xampp\htdocs\api\BadmintonShop

:: Dich: Thu muc Git - noi can de thuc hien git push
set TARGET=C:\BadmintonShop\BackendApi\BadmintonShop

echo.
echo ???  Copy tu: %SOURCE%
echo ??  Sang:    %TARGET%
echo.

:: Thuc hien copy (bao gom ca thu muc con)
:: /E: Copy thu muc va thu muc con, ke ca thu muc rong
:: /H: Copy ca file an/he thong
:: /Y: Khong hoi xac nhan ghi de
xcopy "%SOURCE%" "%TARGET%" /E /H /Y >nul

if %ERRORLEVEL% EQU 0 (
    echo ?  Hoan tat! Code da duoc cap nhat sang thu muc Git.
    echo ?  BAY GIO BAN CO THE THUC HIEN GIT PUSH.
) else (
    echo ?  Loi: khong the dong bo! Kiem tra lai duong dan hoac quyen ghi.
)

echo ==========================================
pause