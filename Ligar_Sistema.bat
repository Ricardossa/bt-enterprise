@echo off
setlocal
pushd "%~dp0"
title BT Queue Enterprise - Manager
color 0b

:: --- VERIFICACAO DE PRIVILEGIOS ADMINISTRATIVOS ---
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [!] O sistema precisa de privilegios de ADMINISTRADOR.
    echo [ ] Tentando elevar privilegios automaticamente...
    powershell -Command "Start-Process '%~f0' -Verb RunAs"
    exit /b
)

echo ============================================================
echo   🚀 BT QUEUE ENTERPRISE - GERENCIADOR DO SISTEMA
echo ============================================================
echo.

:: 1. VERIFICA SE O SERVICO EXISTE
sc query BTQueueServer > nul 2>&1
if %errorlevel% neq 0 (
    echo [!] O servico BT Queue nao esta instalado.
    echo     Iniciando modo portatil legado...
    start /min wscript.exe "BT_Kernel.vbs"
    goto OPEN_BROWSER
)

:: 2. VERIFICA SE ESTA RODANDO
for /f "tokens=4" %%s in ('sc query BTQueueServer ^| findstr STATE') do set "STATE=%%s"

if "%STATE%" neq "RUNNING" (
    echo [!] O sistema esta desligado.
    echo.
    echo [ ] Ligando motores...
    net start BTQueueServer
    if %errorlevel% neq 0 (
        echo.
        echo ❌ ERRO: Nao foi possivel iniciar o servico.
        pause
        exit
    )
    net start BTQueuePrinter
    timeout /t 5 /nobreak > nul
)

:OPEN_BROWSER
:: 3. DISPARA O SINCRONISMO DE FUNDO (Caso nao esteja rodando)
tasklist /fi "imagename eq wscript.exe" | findstr "BT_Sync_Service.vbs" > nul
if %errorlevel% neq 0 (
    echo [ ] Ativando sincronismo inteligente...
    start /min wscript.exe "BT_Sync_Service.vbs"
)

echo ✅ SISTEMA EM OPERACAO!
echo [ ] Abrindo painel de atendimento...
powershell -Command "Start-Process 'http://localhost:8090/index.php'"

echo.
echo ============================================================
timeout /t 3
exit
