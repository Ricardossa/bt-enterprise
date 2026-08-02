#define MyAppName "BT Queue Enterprise"
#define MyAppVersion "5.1.0"
#define MyAppPublisher "Brandão Tech"
#define MyAppURL "http://brandaotech.com.br"
#define MyAppExeName "Ligar_Sistema.bat"
#define MyIconName "favicon.ico"

[Setup]
AppId={{B0E4B4B2-8A73-4C62-9D4B-000000000001}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
DefaultDirName={commonpf}\BrandaoTech\BTQueue
DefaultGroupName={#MyAppName}
AllowNoIcons=yes
; Tenta usar o ícone oficial se ele existir na pasta
SetupIconFile={#MyIconName}
; Requer privilégios para instalar serviços do Windows
PrivilegesRequired=admin
OutputDir=output
OutputBaseFilename=BTQueueSetup_v5.1
Compression=lzma
SolidCompression=yes
WizardStyle=modern

[Languages]
Name: "brazilianportuguese"; MessagesFile: "compiler:Languages\BrazilianPortuguese.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked

[Files]
; Copia a estrutura principal
Source: ".\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: "database\banco.db;logs\*;cache\*;99_QUARENTENA\*;BT_Setup_Installer.iss;.git\*"

; Protege arquivos de dados
Source: ".\database\banco.db"; DestDir: "{app}\database"; Flags: ignoreversion onlyifdoesntexist
Source: ".\config\config.json"; DestDir: "{app}\config"; Flags: ignoreversion onlyifdoesntexist

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; IconFilename: "{app}\{#MyIconName}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon; IconFilename: "{app}\{#MyIconName}"

[Run]
; Registra e inicia os serviços do Windows usando o WinSW
Filename: "{app}\service\BTQueueServer.exe"; Parameters: "install"; Flags: runhidden
Filename: "{app}\service\BTQueuePrinter.exe"; Parameters: "install"; Flags: runhidden
Filename: "{app}\service\BTQueueServer.exe"; Parameters: "start"; Flags: runhidden
Filename: "{app}\service\BTQueuePrinter.exe"; Parameters: "start"; Flags: runhidden

; Abre o navegador ao final
Filename: "{app}\{#MyAppExeName}"; Description: "{cm:LaunchProgram,{#StringChange(MyAppName, '&', '&&')}}"; Flags: postinstall skipifsilent

[UninstallRun]
; Remove os serviços na desinstalação
Filename: "{app}\service\BTQueueServer.exe"; Parameters: "stop"; Flags: runhidden
Filename: "{app}\service\BTQueueServer.exe"; Parameters: "uninstall"; Flags: runhidden
Filename: "{app}\service\BTQueuePrinter.exe"; Parameters: "stop"; Flags: runhidden
Filename: "{app}\service\BTQueuePrinter.exe"; Parameters: "uninstall"; Flags: runhidden

[Dirs]
Name: "{app}\database"; Flags: uninsneveruninstall
Name: "{app}\public\uploads"; Flags: uninsneveruninstall
Name: "{app}\logs"; Flags: uninsneveruninstall
Name: "{app}\cache"; Flags: uninsneveruninstall
