chcp 65001 >nul

@echo off
echo ==========================================
echo ?? DANG DONG BO TOAN BO DU AN ANDROID (AN TOAN) SANG GIT...
echo ==========================================

:: DINH NGHIA DUONG DAN
:: SOURCE: Thu muc goc cua du an Android (Chua BadmintonShop)
set SOURCE=C:\Users\Acer\AndroidStudioProjects

:: TARGET: Thu muc Dich tren Git (AndroidApp)
set TARGET=C:\BadmintonShop\AndroidApp

echo.
echo ??? Copy tu: %SOURCE%\BadmintonShop
echo ?? Sang: %TARGET%
echo.

:: ------------------------------------------------
:: B1: ROBOCopy TOÀN BỘ VÀ LOẠI TRỪ (Exclude)
:: ------------------------------------------------
:: Robocopy "%SOURCE%\BadmintonShop" "%TARGET%" /E /IS /IT /XF *.iml /XD .gradle .idea build
:: /E: Copy thu muc con (bao gom ca thu muc rong)
:: /IS /IT: Bao gom ca file an/he thong
:: /XF *.iml: Loai tru cac file cau hinh IDEA
:: /XD: Loai tru cac thu muc duoc chi dinh (khong nen duoc push len Git)

robocopy "%SOURCE%\BadmintonShop" "%TARGET%" /E /IS /IT /XF *.iml /XD .gradle .idea build /MT:8 >nul

:: ------------------------------------------------
:: B2: HOAN TAT
:: ------------------------------------------------
if %ERRORLEVEL% LEQ 8 (
    echo.
    echo ? Hoan tat! Code Android DA DUOC GHI DE an toan sang thu muc Git.
) else (
    echo.
    echo ? Loi: khong the dong bo! Kiem tra lai duong dan hoac quyen ghi.
)

pause