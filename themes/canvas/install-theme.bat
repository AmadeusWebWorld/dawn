IF EXIST "assets.zip" (
	echo "zip found"
) ELSE (
	powershell -Command "Invoke-WebRequest https://v8.amadeusweb.com/themes/canvas/assets.zip -OutFile assets.zip"
)

IF EXIST "assets" (
	echo "assets folder found - if rerunning, backup (in case of changes) then rerun"
) ELSE (
    mkdir "assets"
    cd "assets"
	tar -xzvf ../assets.zip
)

pause
