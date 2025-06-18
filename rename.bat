@echo off
for %%f in (*.php) do (
    ren "%%f" "%%~nf.php"
)
echo All files renamed successfully! 