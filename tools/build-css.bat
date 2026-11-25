@echo off
rem Chạy từ gốc repo dù gọi file này từ đâu
cd /d "%~dp0.."
echo Building Tailwind CSS...
tools\tailwindcss.exe -i public/assets/tailwind/src/app.css -o public/assets/tailwind/dist/app.css --minify
if errorlevel 1 exit /b 1
echo Done: public\assets\tailwind\dist\app.css (commit file nay cung thay doi view/JS)
