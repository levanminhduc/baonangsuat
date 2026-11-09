@echo off
echo Building Tailwind CSS...

echo [1/2] Building main.css...
tools\tailwindcss.exe -i assets/tailwind/src/main.css -o assets/tailwind/dist/main.css --minify

echo [2/2] Building no-line.css...
tools\tailwindcss.exe -i assets/tailwind/src/no-line.css -o assets/tailwind/dist/no-line.css --minify

echo.
echo Done! CSS files generated:
dir /b assets\tailwind\dist\*.css
