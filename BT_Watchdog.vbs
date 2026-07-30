Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
strPath = Left(WScript.ScriptFullName, InStrRev(WScript.ScriptFullName, "\"))
phpExe = strPath & "runtime\php\php.exe"
phpArgs = " -S 0.0.0.0:8090 -t www\public"

Do
    ' Verifica se o php.exe está rodando com os argumentos específicos
    ' Nota: Simplificado para verificar apenas o processo php.exe
    Set objWMIService = GetObject("winmgmts:\\.\root\cimv2")
    Set colProcesses = objWMIService.ExecQuery("Select * from Win32_Process Where Name = 'php.exe'")

    If colProcesses.Count = 0 Then
        ' Se não está rodando, liga o servidor oculto
        WshShell.Run """" & phpExe & """" & phpArgs, 0, False
    End If

    WScript.Sleep 10000 ' Verifica a cada 10 segundos
Loop
