# click-save-password.ps1
# Bring Chrome to foreground and press Enter to accept "Update password?" bubble
Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName Microsoft.VisualBasic

$chrome = Get-Process chrome -ErrorAction SilentlyContinue | 
    Where-Object { $_.MainWindowHandle -ne 0 } | 
    Select-Object -First 1

if ($chrome) {
    [Microsoft.VisualBasic.Interaction]::AppActivate($chrome.Id)
    Start-Sleep -Milliseconds 500
    [System.Windows.Forms.SendKeys]::SendWait("{ENTER}")
}
