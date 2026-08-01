@echo off
setlocal
pushd "%~dp0"
title BT Queue Enterprise - Ligar Sistema
color 0b

echo ============================================================
echo   🚀 BT QUEUE ENTERPRISE - INICIALIZADOR UNIFICADO
echo ============================================================
echo.

:: 1. REMOVE BLOQUEIOS DO WINDOWS (SOMENTE LEITURA)
echo   [1/3] Preparando arquivos...
attrib -r "*" /s /d > nul 2>&1

:: 2. CRIAÇÃO DE ATALHO NO DESKTOP (Se não existir)
set "LNK_TARGET=%~dp0Ligar_Sistema.bat"
set "LNK_WORKDIR=%~dp0"
powershell -Command "$d=[Environment]::GetFolderPath('Desktop'); $p=\"$d\BT Queue Enterprise.lnk\"; if(!(Test-Path $p)){ $s=(New-Object -COM WScript.Shell).CreateShortcut($p); $s.TargetPath='%LNK_TARGET%'; $s.WorkingDirectory='%LNK_WORKDIR%'; $s.Save(); }"

:: 3. DISPARO DO KERNEL (SILENCIOSO)
echo   [2/3] Ligando Motores (Aplicação + Impressora)...
start /min wscript.exe "BT_Kernel.vbs"

echo   [3/3] Aguardando estabilização...
timeout /t 5 /nobreak > nul

echo.
echo   ✅ TUDO PRONTO!
echo   O sistema e a impressora ja estao ativos em segundo plano.
echo.
echo ============================================================
timeout /t 3
exit
