Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
strPath = Left(WScript.ScriptFullName, InStrRev(WScript.ScriptFullName, "\"))
phpExe = strPath & "runtime\php\php.exe"

Do
    Set objWMIService = GetObject("winmgmts:\\.\root\cimv2")

    ' 1. VIGIA DA APLICAÇÃO (Porta 8090)
    Set colApps = objWMIService.ExecQuery("Select * from Win32_Process Where Name = 'php.exe' AND CommandLine Like '%0.0.0.0:8090%'")
    If colApps.Count = 0 Then
        WshShell.Run """" & phpExe & """ -S 0.0.0.0:8090 -t www\public", 0, False
    End If

    ' 2. VIGIA DA IMPRESSORA (Porta 8001)
    Set colPrinters = objWMIService.ExecQuery("Select * from Win32_Process Where Name = 'php.exe' AND CommandLine Like '%0.0.0.0:8001%'")
    If colPrinters.Count = 0 Then
        ' Nota: O script de impressao agora vive na raiz
        WshShell.Run """" & phpExe & """ -S 0.0.0.0:8001 print_bridge.php", 0, False
    End If

    WScript.Sleep 10000 ' Verifica a cada 10 segundos
Loop
