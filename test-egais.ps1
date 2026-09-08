$utm = "http://10.0.0.100:8086"

$code = "187331488658160225001HN2DV4MCOKG4BFUGD7IQ2SC45ECG5JN5BCI3CLMG6L7MZB6REGWND2SSVLJRDC44NJIFL7ULT5PYHDWGV7YD75YAFXGVIC5K636U5GYE6LTLS2N6QTHOX65W2FDEKNRSQ"

$xml = "<?xml version=""1.0"" encoding=""UTF-8""?><ns2:QueryBarcode xmlns:ns2=""http://fsrar.ru/WEGAIS/QueryForm""><ns2:QueryBarcode>$code</ns2:QueryBarcode></ns2:QueryBarcode>"

$file = "$env:TEMP\utm-query.xml"

[IO.File]::WriteAllText(
    $file,
    $xml,
    [Text.UTF8Encoding]::new($false)
)

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "       " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "тправляем запрос в Т..." -ForegroundColor Yellow

$r = curl.exe -s `
    "$utm/opt/in/QueryBarcode" `
    -F "xml_file=@$file;type=application/xml"

Write-Host ""
Write-Host "твет Т:" -ForegroundColor Green
Write-Host $r
Write-Host ""

$url = ([regex]::Match($r,'<url>([^<]+)</url>')).Groups[1].Value

if ([string]::IsNullOrWhiteSpace($url)) {
    Write-Host "Ш: Т не вернул ID запроса." -ForegroundColor Red
    exit
}

Write-Host "ID запроса: $url" -ForegroundColor Green
Write-Host ""

for ($i=1; $i -le 30; $i++) {

    Write-Host "роверка $i / 30..." -ForegroundColor Cyan

    $result = curl.exe -s "$utm/opt/out/$url"

    if ($result -match '<ver>1</ver>') {
        Write-Host "твет С ещё не готов." -ForegroundColor Yellow
        Start-Sleep -Seconds 10
        continue
    }

    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "       ТТ С " -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host $result

    $output = "$env:TEMP\egais-result.xml"

    [IO.File]::WriteAllText(
        $output,
        $result,
        [Text.UTF8Encoding]::new($false)
    )

    Write-Host ""
    Write-Host "езультат сохранён:" -ForegroundColor Green
    Write-Host $output

    break
}

