#define MyAppName "BT Queue Enterprise"
#define MyAppVersion "5.2.0"
#define MyAppPublisher "Brandão Tech"
#define MyAppURL "http://brandaotech.com.br"
#define MyAppExeName "Ligar_Sistema.bat"
#define MyIconName "favicon.ico"

[Setup]
AppId={{B0E4B4B2-8A73-4C62-9D4B-000000000001}}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
DefaultDirName={commonpf64}\BrandaoTech\BTQueue
DefaultGroupName={#MyAppName}
AllowNoIcons=yes
; Força o modo de 64 bits para instalar em "Program Files" real
ArchitecturesInstallIn64BitMode=x64
ArchitecturesAllowed=x64
; Usa o ícone oficial
SetupIconFile=favicon.ico
; Requer privilégios para instalar serviços do Windows
PrivilegesRequired=admin
OutputDir=output
OutputBaseFilename=BTQueueSetup_v5.2.0
Compression=lzma
SolidCompression=yes
WizardStyle=modern

[Languages]
Name: "brazilianportuguese"; MessagesFile: "compiler:Languages\BrazilianPortuguese.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked

[Files]
; 1. Bibliotecas de Sistema (Microsoft Redistributables)
Source: "redist\vc_redist.x64.exe"; DestDir: "{tmp}"; Flags: deleteafterinstall
; 2. Copia a estrutura PHP Runtime (VITAL)
Source: "runtime\*"; DestDir: "{app}\runtime"; Flags: ignoreversion recursesubdirs createallsubdirs
; 3. Copia os Motores de Servico
Source: "service\*"; DestDir: "{app}\service"; Flags: ignoreversion recursesubdirs createallsubdirs
; 4. Copia a aplicacao (Excluindo dados locais)
Source: "*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: "runtime\*;service\*;redist\*;database\banco.db;logs\*;cache\*;99_QUARENTENA\*;BT_Setup_Installer.iss;.git\*;output\*"

; Protege arquivos de dados e scripts de API novos
Source: "database\banco_template.db"; DestDir: "{app}\database"; Flags: ignoreversion onlyifdoesntexist
Source: "config\config.json"; DestDir: "{app}\config"; Flags: ignoreversion onlyifdoesntexist
Source: "public\api\*"; DestDir: "{app}\public\api"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; IconFilename: "{app}\favicon.ico"; WorkingDir: "{app}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon; IconFilename: "{app}\favicon.ico"; WorkingDir: "{app}"

[Run]
; Instala o Microsoft Visual C++ Redistributable (Silencioso)
Filename: "{tmp}\vc_redist.x64.exe"; Parameters: "/install /quiet /norestart"; StatusMsg: "Instalando componentes de sistema (Microsoft Visual C++)..."; Check: not IsVCInstalled
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

[Code]
// Funcao para verificar se o Visual C++ ja esta instalado e evitar re-instalacao desnecessaria
function IsVCInstalled: Boolean;
begin
  Result := RegKeyExists(HKEY_LOCAL_MACHINE, 'SOFTWARE\Microsoft\VisualStudio\14.0\VC\Runtimes\x64');
end;
