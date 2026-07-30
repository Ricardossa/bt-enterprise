Set WshShell = CreateObject("WScript.Shell")
strPath = Left(WScript.ScriptFullName, InStrRev(WScript.ScriptFullName, "\"))
phpExe = strPath & "runtime\php\php.exe"
pulseScript = strPath & "www\public\pulse.php"

Do
    ' Executa o script de pulso via CLI de forma oculta
    WshShell.Run """" & phpExe & """ """ & pulseScript & """", 0, True

    WScript.Sleep 300000 ' Pulsa a cada 5 minutos (300.000 ms)
Loop
