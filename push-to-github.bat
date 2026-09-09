@echo off
title Push CarelioEMR to GitHub
cd /d "%~dp0"
echo ====================================================
echo   Pushing CarelioEMR to GitHub (main branch)
echo ====================================================
echo.
git push -u origin main
echo.
echo ====================================================
echo   Process finished. Press any key to close this window.
echo ====================================================
pause

