@echo off
setlocal
pushd "%~dp0"
title BT Queue Enterprise - Manager
color 0b

echo ============================================================
echo   🚀 BT QUEUE ENTERPRISE - GERENCIADOR DO SISTEMA
echo ============================================================
echo.

:: 1. VERIFICA SE O SERVIÇO EXISTE
sc query BTQueueServer > nul 2>&1
if %errorlevel% neq 0 (
    echo [!] O servico BT Queue nao esta instalado.
    echo     Iniciando modo portatil legado...
    start /min wscript.exe "BT_Kernel.vbs"
    goto OPEN_BROWSER
)

:: 2. VERIFICA SE ESTÁ RODANDO
for /f "tokens=4" %%s in ('sc query BTQueueServer ^| findstr STATE') do set "STATE=%%s"

if "%STATE%" neq "RUNNING" (
    echo [!] O sistema esta desligado.
    echo.
    set /p choice="Deseja ligar o sistema agora? (S/N): "
    if /i "%choice%"=="S" (
        echo [ ] Ligando motores...
        net start BTQueueServer
        net start BTQueuePrinter
        timeout /t 5 /nobreak > nul
    ) else (
        exit
    )
)

:OPEN_BROWSER
echo ✅ SISTEMA EM OPERAÇÃO!
echo [ ] Abrindo painel de atendimento...
start http://localhost:8090/index.php

echo.
echo ============================================================
timeout /t 3
exit
