@echo off
setlocal
pushd "%~dp0"
title BT Print Bridge - Servidor de Impressao
echo ======================================================
echo           BT QUEUE - PRINT BRIDGE v1.0
echo ======================================================
echo.

:: Tenta localizar o PHP em diferentes locais (Raiz ou pasta superior)
set "PHP_BIN=runtime\php\php.exe"
if not exist "%PHP_BIN%" set "PHP_BIN=..\runtime\php\php.exe"

if not exist "%PHP_BIN%" (
    echo.
    echo ❌ ERRO: Nao foi possivel localizar o motor PHP.
    echo Por favor, verifique se a pasta 'runtime' existe.
    pause
    exit /b
)

echo Iniciando servidor de impressao local na porta 8001...
echo Mantenha esta janela aberta para as impressoes funcionarem.
echo.

:: Inicia o Servidor de Impressão
"%PHP_BIN%" -S 0.0.0.0:8001 print_bridge.php

if %errorlevel% neq 0 (
    echo.
    echo ❌ ERRO: O servidor de impressao falhou ao iniciar.
    echo Verifique se a porta 8001 ja esta sendo usada.
    pause
)

popd
endlocal
